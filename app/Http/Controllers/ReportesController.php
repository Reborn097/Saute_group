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
        $unidades = UnidadOperativa::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

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
        $usersUnidadColumn = $this->usersUnidadColumn();
        $usersTieneUnidadOperativa = $usersUnidadColumn === 'unidad_operativa_id';
        $usersTieneUnidad = $usersUnidadColumn === 'unidad_id';

        $filtroUnidadDisponible = !is_null($usersUnidadColumn);

        // =========================
        // 2) A) PRODUCTOS PEDIDOS (TABLA)
        // =========================
        $productosQuery = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->join('presentacion_proveedor as pp', 'pp.id', '=', 'd.producto_proveedor_id')
            ->join('producto_presentaciones as pres', 'pres.id', '=', 'pp.presentacion_id')
            ->join('productos as pr', 'pr.id', '=', 'pres.producto_id')
            ->selectRaw("
                DATE(p.created_at) as fecha,
                pr.nombre as producto,
                pres.descripcion as presentacion,
                SUM(COALESCE(d.cantidad_aprobada, d.cantidad_solicitada, 0)) as cantidad_total,
                AVG(COALESCE(d.precio_unitario, 0)) as precio_promedio,
                SUM(COALESCE(d.subtotal, 0)) as total
            ")
            ->whereBetween('p.created_at', [$desdeC, $hastaC]);

        // filtro unidad (si se puede)
        $mensajeFiltroUnidad = null;

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $productosQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $productosQuery
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            } else {
                $mensajeFiltroUnidad = "No se pudo filtrar pedidos por unidad porque la tabla 'users' no tiene 'unidad_operativa_id' ni 'unidad_id'.";
            }
        }

        $productosQuery
            ->groupBy(DB::raw('DATE(p.created_at)'), 'pr.nombre', 'pres.descripcion')
            ->orderBy(DB::raw('DATE(p.created_at)'), 'desc')
            ->orderBy('pr.nombre', 'asc');

        $productosPag = $productosQuery->paginate(10, ['*'], 'productos_page')->withQueryString();

        // =========================
        // 3) B) GASTOS (TABLA)  [lo dejas, pero no lo graficamos]
        // =========================
        $gastosQuery = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->selectRaw("
                YEARWEEK(p.created_at, 1) as semana,
                MIN(DATE(p.created_at)) as semana_inicio,
                MAX(DATE(p.created_at)) as semana_fin,
                SUM(COALESCE(d.subtotal, 0)) as total_gasto
            ")
            ->whereBetween('p.created_at', [$desdeC, $hastaC]);

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
            ->groupBy(DB::raw('YEARWEEK(p.created_at, 1)'))
            ->orderBy(DB::raw('YEARWEEK(p.created_at, 1)'), 'desc');

        $gastosPag = $gastosQuery->paginate(10, ['*'], 'gastos_page')->withQueryString();

        // Total del periodo (gastos)
        $gastoTotalPeriodoQuery = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->whereBetween('p.created_at', [$desdeC, $hastaC]);

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
        // 4) C) COMENSALES (TABLA + TOTAL)
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
        // 5) D) CORTE DE CAJA (TABLA + TOTALES)
        // =========================
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
        // 6) E) TRANSFERENCIAS DE ALMACÉN (TABLA + TOTAL)
        // =========================
        $transferenciasQuery = DB::table('movimientos_inventario as mi')
            ->leftJoin('almacenes as ao', 'ao.id', '=', 'mi.almacen_id')
            ->leftJoin('almacenes as ad', 'ad.id', '=', 'mi.almacen_destino_id')
            ->leftJoin('producto_presentaciones as pres', 'pres.id', '=', 'mi.presentacion_id')
            ->leftJoin('productos as pr', 'pr.id', '=', 'mi.producto_id')
            ->leftJoin('users as u', 'u.id', '=', 'mi.usuario_id')
            ->selectRaw("
                mi.fecha,
                mi.referencia,
                ao.nombre as almacen_origen,
                ad.nombre as almacen_destino,
                pr.nombre as producto,
                pres.descripcion as presentacion,
                mi.cantidad,
                mi.costo_unitario,
                mi.costo_total,
                mi.motivo,
                COALESCE(u.name, u.username) as usuario
            ")
            ->where('mi.tipo', 'transferencia')
            ->whereBetween('mi.fecha', [$desdeStr, $hastaStr]);

        if ($unidadOperativaId) {
            $transferenciasQuery->where(function ($q) use ($unidadOperativaId) {
                $q->where('ao.unidad_id', (int)$unidadOperativaId)
                  ->orWhere('ad.unidad_id', (int)$unidadOperativaId);
            });
        }

        $transferenciasPag = $transferenciasQuery
            ->orderBy('mi.fecha', 'desc')
            ->orderBy('mi.id', 'desc')
            ->paginate(10, ['*'], 'transferencias_page')
            ->withQueryString();

        $transferenciasTotalCosto = (float) DB::table('movimientos_inventario as mi')
            ->leftJoin('almacenes as ao', 'ao.id', '=', 'mi.almacen_id')
            ->leftJoin('almacenes as ad', 'ad.id', '=', 'mi.almacen_destino_id')
            ->where('mi.tipo', 'transferencia')
            ->whereBetween('mi.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, function ($q) use ($unidadOperativaId) {
                $q->where(function ($sub) use ($unidadOperativaId) {
                    $sub->where('ao.unidad_id', (int)$unidadOperativaId)
                        ->orWhere('ad.unidad_id', (int)$unidadOperativaId);
                });
            })
            ->sum(DB::raw('COALESCE(mi.costo_total,0)'));

        // ==========================================================
        // ✅ DATASETS PARA GRÁFICAS (SIN GASTO DIARIO)
        // ==========================================================

        // CHART 1) Comensales por día (ASC)
        $comensalesChart = DB::table('comensales_registros as cr')
            ->selectRaw("cr.fecha, SUM(COALESCE(cr.cantidad,0)) as total_comensales")
            ->whereBetween('cr.fecha', [$desdeStr, $hastaStr])
            ->when($unidadOperativaId, fn($q) => $q->where('cr.unidad_operativa_id', (int)$unidadOperativaId))
            ->groupBy('cr.fecha')
            ->orderBy('cr.fecha', 'asc')
            ->get();

        $chartComensalesLabels = $comensalesChart->pluck('fecha')->values();
        $chartComensalesValues = $comensalesChart->pluck('total_comensales')->map(fn($v) => (int)$v)->values();

        // CHART 2) Top 10 productos por total ($)
        $topProductosQuery = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->join('presentacion_proveedor as pp', 'pp.id', '=', 'd.producto_proveedor_id')
            ->join('producto_presentaciones as pres', 'pres.id', '=', 'pp.presentacion_id')
            ->join('productos as pr', 'pr.id', '=', 'pres.producto_id')
            ->selectRaw("CONCAT(pr.nombre, ' - ', pres.descripcion) as producto, SUM(COALESCE(d.subtotal, 0)) as total_sum")
            ->whereBetween('p.created_at', [$desdeC, $hastaC]);

        if ($unidadOperativaId) {
            if ($usersTieneUnidadOperativa) {
                $topProductosQuery->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_operativa_id', (int)$unidadOperativaId);
            } elseif ($usersTieneUnidad) {
                $topProductosQuery->join('users as u', 'u.id', '=', 'p.user_id')
                    ->where('u.unidad_id', (int)$unidadOperativaId);
            }
        }

        $topProductos = $topProductosQuery
            ->groupBy('pr.nombre', 'pres.descripcion')
            ->orderByDesc('total_sum')
            ->limit(10)
            ->get();

        $chartTopProductosLabels = $topProductos->pluck('producto')->values();
        $chartTopProductosValues = $topProductos->pluck('total_sum')->map(fn($v) => (float)$v)->values();

        // CHART 3) Corte (donut)
        $chartCorte = [
            'efectivo' => (float) ($corteTotales->total_efectivo ?? 0),
            'credito'  => (float) ($corteTotales->total_credito ?? 0),
        ];

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
            'transferenciasPag',
            'transferenciasTotalCosto',
            'filtroUnidadDisponible',
            'mensajeFiltroUnidad',
            'chartComensalesLabels',
            'chartComensalesValues',
            'chartTopProductosLabels',
            'chartTopProductosValues',
            'chartCorte'
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

        $data = $this->buildReportData($unidadOperativaId, $desde, $hasta);

        $pdf = Pdf::loadView('dashboard.reportes_pdf', $data)->setPaper('letter', 'landscape');

        return $pdf->download('reportes_' . now()->format('Ymd_His') . '.pdf');
    }

    private function usersUnidadColumn(): ?string
    {
        static $resolved = false;
        static $column = null;

        if ($resolved) {
            return $column;
        }

        if (Schema::hasColumn('users', 'unidad_operativa_id')) {
            $column = 'unidad_operativa_id';
        } elseif (Schema::hasColumn('users', 'unidad_id')) {
            $column = 'unidad_id';
        }

        $resolved = true;

        return $column;
    }

    /**
     * Helper para PDF/Excel: arma datasets completos (sin paginate).
     */
    private function buildReportData($unidadOperativaId, $desde, $hasta): array
    {
        $unidades = UnidadOperativa::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

        $desdeC = Carbon::parse($desde)->startOfDay();
        $hastaC = Carbon::parse($hasta)->endOfDay();
        $desdeStr = $desdeC->toDateString();
        $hastaStr = $hastaC->toDateString();

        $usersUnidadColumn = $this->usersUnidadColumn();
        $usersTieneUnidadOperativa = $usersUnidadColumn === 'unidad_operativa_id';
        $usersTieneUnidad = $usersUnidadColumn === 'unidad_id';

        // Productos
        $productosQ = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->join('presentacion_proveedor as pp', 'pp.id', '=', 'd.producto_proveedor_id')
            ->join('producto_presentaciones as pres', 'pres.id', '=', 'pp.presentacion_id')
            ->join('productos as pr', 'pr.id', '=', 'pres.producto_id')
            ->selectRaw("
                DATE(p.created_at) as fecha,
                pr.nombre as producto,
                pres.descripcion as presentacion,
                SUM(COALESCE(d.cantidad_aprobada, d.cantidad_solicitada, 0)) as cantidad_total,
                AVG(COALESCE(d.precio_unitario, 0)) as precio_promedio,
                SUM(COALESCE(d.subtotal, 0)) as total
            ")
            ->whereBetween('p.created_at', [$desdeC, $hastaC]);

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
            ->groupBy(DB::raw('DATE(p.created_at)'), 'pr.nombre', 'pres.descripcion')
            ->orderBy(DB::raw('DATE(p.created_at)'), 'desc')
            ->orderBy('pr.nombre', 'asc')
            ->limit(5000)
            ->get();

        // Gastos por día
        $gastosQ = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->selectRaw("
                YEARWEEK(p.created_at, 1) as semana,
                MIN(DATE(p.created_at)) as semana_inicio,
                MAX(DATE(p.created_at)) as semana_fin,
                SUM(COALESCE(d.subtotal,0)) as total_gasto
            ")
            ->whereBetween('p.created_at', [$desdeC, $hastaC]);

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
            ->groupBy(DB::raw('YEARWEEK(p.created_at, 1)'))
            ->orderBy(DB::raw('YEARWEEK(p.created_at, 1)'), 'desc')
            ->limit(5000)
            ->get();

        $gastoTotalPeriodoQ = DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.codigo', '=', 'd.codigo')
            ->whereBetween('p.created_at', [$desdeC, $hastaC]);

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
