<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\UnidadOperativa;

// Excel / PDF
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportesExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
    public function index(Request $request)
    {
        // =========================
        // 1) FILTROS
        // =========================
        $unidades = UnidadOperativa::orderBy('nombre')->get();

        $unidadOperativaId = $request->get('unidad_operativa_id');
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        // default: mes actual
        $hoy = Carbon::today();
        if (!$desde) $desde = $hoy->copy()->startOfMonth()->toDateString();
        if (!$hasta) $hasta = $hoy->copy()->toDateString();

        // normaliza fechas
        $desdeC = Carbon::parse($desde)->startOfDay();
        $hastaC = Carbon::parse($hasta)->endOfDay();

        $desdeStr = $desdeC->toDateString();
        $hastaStr = $hastaC->toDateString();

        // ¿Cómo filtramos por unidad en pedidos?
        // pedidos NO tiene unidad_id, entonces filtramos por users:
        $usersTieneUnidadOperativa = Schema::hasColumn('users', 'unidad_operativa_id');
        $usersTieneUnidad = Schema::hasColumn('users', 'unidad_id');

        $filtroUnidadDisponible = $usersTieneUnidadOperativa || $usersTieneUnidad;

        // =========================
        // 2) A) PRODUCTOS PEDIDOS
        // =========================
        // Tablas reales:
        // pedidos (codigo PK, user_id, created_at)
        // detalle_pedidos (codigo, producto_proveedor_id, cantidad_solicitada, cantidad_aprobada, precio_unitario, subtotal)
        // producto_proveedor (id, producto_id)
        // productos (id, nombre)
        $productosQuery = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->join('producto_proveedor as pp', 'pp.id', '=', 'd.producto_proveedor_id')
            ->join('productos as pr', 'pr.id', '=', 'pp.producto_id')
            ->selectRaw("
                DATE(p.created_at) as fecha,
                pr.nombre as producto,
                SUM(COALESCE(d.cantidad_aprobada, d.cantidad_solicitada, 0)) as cantidad_total,
                AVG(COALESCE(d.precio_unitario, 0)) as precio_promedio,
                SUM(COALESCE(d.subtotal, 0)) as total
            ")
            ->whereBetween(DB::raw('DATE(p.created_at)'), [$desdeStr, $hastaStr]);

        // filtro unidad (si se puede)
        $mensajeFiltroUnidad = null;

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $productosQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                // Si tu sistema usa "unidades" (no unidades_operativas) en users.unidad_id,
                // igual filtramos con el valor seleccionado.
                $productosQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            } else {
                $mensajeFiltroUnidad = "No se pudo filtrar pedidos por unidad porque la tabla 'users' no tiene 'unidad_operativa_id' ni 'unidad_id'.";
            }
        }

        $productosQuery
            ->groupBy(DB::raw('DATE(p.created_at)'), 'pr.nombre')
            ->orderBy(DB::raw('DATE(p.created_at)'), 'desc')
            ->orderBy('pr.nombre', 'asc');

        // paginación propia para esta tabla
        $productosPag = $productosQuery->paginate(10, ['*'], 'productos_page')->withQueryString();

        // =========================
        // 3) B) GASTOS TOTALES (RESUMEN)
        // =========================
        // Tomamos como "gasto" la suma de subtotales del detalle_pedidos
        $gastosQuery = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->selectRaw("
                DATE(p.created_at) as fecha,
                SUM(COALESCE(d.subtotal, 0)) as total_gasto
            ")
            ->whereBetween(DB::raw('DATE(p.created_at)'), [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $gastosQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $gastosQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            }
        }

        $gastosQuery
            ->groupBy(DB::raw('DATE(p.created_at)'))
            ->orderBy(DB::raw('DATE(p.created_at)'), 'desc');

        $gastosPag = $gastosQuery->paginate(10, ['*'], 'gastos_page')->withQueryString();

        // Total del periodo (gastos)
        $gastoTotalPeriodoQuery = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->whereBetween(DB::raw('DATE(p.created_at)'), [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $gastoTotalPeriodoQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $gastoTotalPeriodoQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            }
        }

        $gastoTotalPeriodo = (float) $gastoTotalPeriodoQuery->sum(DB::raw('COALESCE(d.subtotal,0)'));

        // =========================
        // 4) C) COMENSALES REGISTROS
        // =========================
        $comensalesQuery = DB::table('comensales_registros as cr')
            ->selectRaw("
                cr.fecha,
                SUM(COALESCE(cr.cantidad,0)) as total_comensales
            ")
            ->whereBetween('cr.fecha', [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            $comensalesQuery->where('cr.unidad_operativa_id', (int)$unidadOperativaId);
        }

        $comensalesQuery
            ->groupBy('cr.fecha')
            ->orderBy('cr.fecha', 'desc');

        $comensalesPag = $comensalesQuery->paginate(10, ['*'], 'comensales_page')->withQueryString();

        $comensalesTotal = (int) DB::table('comensales_registros as cr')
            ->whereBetween('cr.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, fn($q) => $q->where('cr.unidad_operativa_id', (int)$unidadOperativaId))
            ->sum(DB::raw('COALESCE(cr.cantidad,0)'));

        // =========================
        // 5) D) CORTE DE CAJA
        // =========================
        // corte_caja usa unidad_id (tabla "unidades") no "unidades_operativas"
        // Aquí filtramos por el mismo valor que el usuario eligió.
        $corteQuery = DB::table('corte_caja as cc')
            ->select([
                'cc.fecha',
                'cc.cantidad_efectivo',
                'cc.cantidad_credito',
                'cc.total',
                'cc.semana',
                'cc.mes',
                'cc.anio',
                'cc.unidad_id'
            ])
            ->whereBetween('cc.fecha', [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            $corteQuery->where('cc.unidad_id', (int)$unidadOperativaId);
        }

        $corteQuery->orderBy('cc.fecha', 'desc');

        $cortePag = $corteQuery->paginate(10, ['*'], 'corte_page')->withQueryString();

        $corteTotales = DB::table('corte_caja as cc')
            ->whereBetween('cc.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, fn($q) => $q->where('cc.unidad_id', (int)$unidadOperativaId))
            ->selectRaw("
                SUM(COALESCE(cc.cantidad_efectivo,0)) as total_efectivo,
                SUM(COALESCE(cc.cantidad_credito,0)) as total_credito,
                SUM(COALESCE(cc.total,0)) as total_general
            ")
            ->first();

        // =========================
        // Render
        // =========================
        return view('dashboard.reportes', compact(
            'unidades',
            'unidadOperativaId',
            'desdeStr',
            'hastaStr',
            'productosPag',
            'gastosPag',
            'gastoTotalPeriodo',
            'comensalesPag',
            'comensalesTotal',
            'cortePag',
            'corteTotales',
            'filtroUnidadDisponible',
            'mensajeFiltroUnidad'
        ));
    }

    public function exportExcel(Request $request)
    {
        $unidadOperativaId = $request->get('unidad_operativa_id');
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $hoy = Carbon::today();
        if (!$desde) $desde = $hoy->copy()->startOfMonth()->toDateString();
        if (!$hasta) $hasta = $hoy->copy()->toDateString();

        $file = 'reportes_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ReportesExport($unidadOperativaId, $desde, $hasta), $file);
    }

    public function exportPDF(Request $request)
    {
        $unidadOperativaId = $request->get('unidad_operativa_id');
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $hoy = Carbon::today();
        if (!$desde) $desde = $hoy->copy()->startOfMonth()->toDateString();
        if (!$hasta) $hasta = $hoy->copy()->toDateString();

        // Reutilizamos la misma lógica del index pero sin paginar: sacamos "top" razonable
        $data = $this->buildReportData($unidadOperativaId, $desde, $hasta);

        $pdf = Pdf::loadView('dashboard.reportes_pdf', $data)->setPaper('letter', 'landscape');

        return $pdf->download('reportes_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Helper para PDF/Excel: arma datasets completos (sin paginate).
     */
    private function buildReportData($unidadOperativaId, $desde, $hasta): array
    {
        $unidades = UnidadOperativa::orderBy('nombre')->get();

        $desdeC = Carbon::parse($desde)->startOfDay();
        $hastaC = Carbon::parse($hasta)->endOfDay();
        $desdeStr = $desdeC->toDateString();
        $hastaStr = $hastaC->toDateString();

        $usersTieneUnidadOperativa = Schema::hasColumn('users', 'unidad_operativa_id');
        $usersTieneUnidad = Schema::hasColumn('users', 'unidad_id');

        // Productos
        $productosQ = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->join('producto_proveedor as pp', 'pp.id', '=', 'd.producto_proveedor_id')
            ->join('productos as pr', 'pr.id', '=', 'pp.producto_id')
            ->selectRaw("
                DATE(p.created_at) as fecha,
                pr.nombre as producto,
                SUM(COALESCE(d.cantidad_aprobada, d.cantidad_solicitada, 0)) as cantidad_total,
                AVG(COALESCE(d.precio_unitario, 0)) as precio_promedio,
                SUM(COALESCE(d.subtotal, 0)) as total
            ")
            ->whereBetween(DB::raw('DATE(p.created_at)'), [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $productosQ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $productosQ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            }
        }

        $productos = $productosQ
            ->groupBy(DB::raw('DATE(p.created_at)'), 'pr.nombre')
            ->orderBy(DB::raw('DATE(p.created_at)'), 'desc')
            ->orderBy('pr.nombre', 'asc')
            ->limit(5000)
            ->get();

        // Gastos por día
        $gastosQ = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->selectRaw("DATE(p.created_at) as fecha, SUM(COALESCE(d.subtotal,0)) as total_gasto")
            ->whereBetween(DB::raw('DATE(p.created_at)'), [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $gastosQ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $gastosQ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            }
        }

        $gastos = $gastosQ
            ->groupBy(DB::raw('DATE(p.created_at)'))
            ->orderBy(DB::raw('DATE(p.created_at)'), 'desc')
            ->limit(5000)
            ->get();

        $gastoTotalPeriodoQ = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->whereBetween(DB::raw('DATE(p.created_at)'), [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $gastoTotalPeriodoQ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $gastoTotalPeriodoQ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            }
        }
        $gastoTotalPeriodo = (float) $gastoTotalPeriodoQ->sum(DB::raw('COALESCE(d.subtotal,0)'));

        // Comensales
        $comensales = DB::table('comensales_registros as cr')
            ->selectRaw("cr.fecha, SUM(COALESCE(cr.cantidad,0)) as total_comensales")
            ->whereBetween('cr.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, fn($q) => $q->where('cr.unidad_operativa_id', (int)$unidadOperativaId))
            ->groupBy('cr.fecha')
            ->orderBy('cr.fecha', 'desc')
            ->limit(5000)
            ->get();

        $comensalesTotal = (int) DB::table('comensales_registros as cr')
            ->whereBetween('cr.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, fn($q) => $q->where('cr.unidad_operativa_id', (int)$unidadOperativaId))
            ->sum(DB::raw('COALESCE(cr.cantidad,0)'));

        // Corte caja
        $cortes = DB::table('corte_caja as cc')
            ->select([
                'cc.fecha',
                'cc.cantidad_efectivo',
                'cc.cantidad_credito',
                'cc.total',
                'cc.semana',
                'cc.mes',
                'cc.anio',
                'cc.unidad_id'
            ])
            ->whereBetween('cc.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, fn($q) => $q->where('cc.unidad_id', (int)$unidadOperativaId))
            ->orderBy('cc.fecha', 'desc')
            ->limit(5000)
            ->get();

        $corteTotales = DB::table('corte_caja as cc')
            ->whereBetween('cc.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, fn($q) => $q->where('cc.unidad_id', (int)$unidadOperativaId))
            ->selectRaw("
                SUM(COALESCE(cc.cantidad_efectivo,0)) as total_efectivo,
                SUM(COALESCE(cc.cantidad_credito,0)) as total_credito,
                SUM(COALESCE(cc.total,0)) as total_general
            ")
            ->first();

        // Nombre unidad seleccionada
        $unidadNombre = null;
        if ($unidadOperativaId) {
            $uo = $unidades->firstWhere('id', (int)$unidadOperativaId);
            $unidadNombre = $uo?->nombre;
        }

        return compact(
            'unidades',
            'unidadOperativaId',
            'unidadNombre',
            'desdeStr',
            'hastaStr',
            'productos',
            'gastos',
            'gastoTotalPeriodo',
            'comensales',
            'comensalesTotal',
            'cortes',
            'corteTotales'
        );
    }
}
