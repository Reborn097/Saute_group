<?php

namespace App\Http\Controllers;

use App\Models\PedidoDiario;
use App\Models\PedidoDiarioDetalle;
use App\Models\Producto;
use App\Models\UnidadOperativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Barryvdh\DomPDF\Facade\Pdf;

class PedidoDiarioController extends Controller
{
    private const CATEGORIA_PAN_ID = 8;       // <-- CAMBIAR
    private const CATEGORIA_TORTILLA_ID = 10; // <-- CAMBIAR

    // =========================
    // Helpers
    // =========================

    private function weekRangeFromAnyDate(string $date): array
    {
        $d = Carbon::parse($date)->startOfDay();
        $inicio = $d->copy()->startOfWeek(Carbon::MONDAY);
        $fin = $inicio->copy()->endOfWeek(Carbon::SUNDAY);

        return [$inicio->toDateString(), $fin->toDateString()];
    }

    private function daysOfWeek(string $semanaInicio): array
    {
        $start = Carbon::parse($semanaInicio)->startOfDay();
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->copy()->addDays($i)->toDateString();
        }
        return $days;
    }

    private function tipoToCategoriaId(string $tipo): int
    {
        return $tipo === 'PAN' ? self::CATEGORIA_PAN_ID : self::CATEGORIA_TORTILLA_ID;
    }

    private function assertTipo(string $tipo): void
    {
        if (!in_array($tipo, ['PAN', 'TORTILLA'], true)) {
            abort(404);
        }
    }

    private function productosPorTipo(string $tipo)
    {
        $categoriaId = $this->tipoToCategoriaId($tipo);

        return Producto::query()
            ->where('categoria_id', $categoriaId)
            ->where(function ($q) {
                $q->where('estado', 1)
                  ->orWhere('estado', 'Activo')
                  ->orWhere('estado', 'ACTIVO');
            })
            ->with(['proveedores' => function ($q) {
                $q->orderByDesc('producto_proveedor.fecha_vigencia_inicio')
                  ->orderByDesc('producto_proveedor.id');
            }])
            ->whereHas('proveedores')
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                $p->setRelation('proveedores', $p->proveedores->take(1));
                return $p;
            });
    }

    
    // =========================
    // Crear (PAN)
    // =========================

    public function createPan(Request $request)
    {
        return $this->createByTipo($request, 'PAN');
    }

    public function storePan(Request $request)
    {
        return $this->storeByTipo($request, 'PAN');
    }

    // =========================
    // Crear (TORTILLA)
    // =========================

    public function createTortilla(Request $request)
    {
        return $this->createByTipo($request, 'TORTILLA');
    }

    public function storeTortilla(Request $request)
    {
        return $this->storeByTipo($request, 'TORTILLA');
    }

    // =========================
    // Listado (Index) con filtros + paginación
    // =========================

    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->role ?? '';

        // ✅ Solo estos roles pueden CREAR pedidos diarios (CEO NO)
        $puedeCrear = in_array($role, [
            'admin',
            'encargado_pedidos',
            'encargado_cocina',
            'encargado_cafeteria',
            'encargado_comedor',
        ], true);

        // Roles
        $esCeo = ($role === 'ceo');

        $esEncargadoUnidad = in_array($role, [
            'encargado_cocina',
            'encargado_cafeteria',
            'encargado_comedor',
        ], true);

        $esAdminPedidos = in_array($role, [
            'admin',
            'encargado_pedidos',
        ], true);

        // ============================
        // QUERY BASE
        // ============================
        $query = PedidoDiario::with(['unidadOperativa'])
            ->orderByDesc('semana_inicio')
            ->orderByDesc('id');

        // ✅ CEO: SOLO preaprobados (puedes agregar 'en revision' si aplica)
        if ($esCeo) {
            $query->whereIn('estado', [
                'Preaprobado',
                // 'En revision', // <- si quieres que también vea estos
            ]);
        }

        // ✅ Encargados: SOLO su unidad
        if ($esEncargadoUnidad) {
            $uoId = $user->unidad_operativa_id ?? null;

            // Si el usuario no tiene unidad asignada, mejor no mostrar nada
            if (!$uoId) {
                $query->whereRaw('1=0');
            } else {
                $query->where('unidad_operativa_id', $uoId);
            }
        }

        // (Admin/encargado_pedidos no se filtra: ven todo)

        $pedidos = $query->get()->map(function ($p) use ($role) {

            $estadoRaw  = trim((string)($p->estado ?? ''));
            $estadoNorm = mb_strtolower($estadoRaw);

            // ✅ Bloqueado por flujo
            $bloqueado = in_array($estadoNorm, [
                'preaprobado',
                'aprobado',
                'rechazado',
                'cancelado',
                'borrador', // legacy por si hay registros viejos
            ], true);

            $esEncargadoUnidad = in_array($role, [
                'encargado_cocina',
                'encargado_cafeteria',
                'encargado_comedor',
            ], true);

            $esAdminPedidos = in_array($role, [
                'admin',
                'encargado_pedidos',
            ], true);

            // Defaults
            $p->puedeEditar = false;
            $p->puedePdf    = true;
            $p->puedeVer    = true;

            if (!$bloqueado) {
                if ($esEncargadoUnidad) {
                    // ✅ Encargado: SOLO Pendiente (NO Visto)
                    $p->puedeEditar = ($estadoNorm === 'pendiente');
                } elseif ($esAdminPedidos) {
                    // ✅ Admin/encargado_pedidos: Pendiente o Visto
                    $p->puedeEditar = in_array($estadoNorm, ['pendiente', 'visto'], true);
                } else {
                    // CEO u otros: no editan
                    $p->puedeEditar = false;
                }
            }

            return $p;
        });

        return view('dashboard.pedidos_diarios.index', compact('pedidos', 'puedeCrear'));
    }





    // =========================
    // Ver detalle por ID (se mantiene igual)
    // =========================

    public function show($id)
    {
        $pedido = PedidoDiario::with([
            'unidadOperativa',
            'usuario',
            'detalles'
        ])->findOrFail($id);

        $role = auth()->user()->role ?? '';
        $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos'], true);

        if ($esAdminPedidos && in_array($pedido->estado, ['Pendiente', 'Solicitado'], true)) {
            $pedido->estado = 'Visto';
            $pedido->save();
        }

        $inicio = Carbon::parse($pedido->semana_inicio);
        $fin    = Carbon::parse($pedido->semana_fin);

        $days = [];
        $cursor = $inicio->copy();
        while ($cursor->lte($fin)) {
            $days[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $productoIds = $pedido->detalles->pluck('producto_id')->unique();

        $productos = Producto::with(['proveedores' => function ($q) {
                $q->orderByPivot('fecha_vigencia_inicio', 'desc')
                  ->orderByPivot('id', 'desc');
            }])
            ->whereIn('id', $productoIds)
            ->orderBy('nombre')
            ->get();

        $cantidades = [];
        foreach ($pedido->detalles as $det) {
            $fechaKey = $det->fecha instanceof Carbon
                ? $det->fecha->toDateString()
                : (string) $det->fecha;

            $cantidades[$det->producto_id][$fechaKey] = (float) $det->cantidad;
        }

        return view('dashboard.pedidos_diarios.show', [
            'pedido'     => $pedido,
            'productos'  => $productos,
            'days'       => $days,
            'cantidades' => $cantidades,
        ]);
    }

    public function pdf($id)
    {
        $pedido = PedidoDiario::with(['unidadOperativa','usuario','detalles'])
            ->findOrFail($id);

        $inicio = Carbon::parse($pedido->semana_inicio);
        $fin    = Carbon::parse($pedido->semana_fin);

        $days = [];
        $c = $inicio->copy();
        while ($c->lte($fin)) {
            $days[] = $c->toDateString();
            $c->addDay();
        }

        $productoIds = $pedido->detalles->pluck('producto_id')->unique()->values();

        $productos = Producto::whereIn('id', $productoIds)
            ->orderBy('nombre')
            ->get();

        $cantidades = [];
        foreach ($pedido->detalles as $det) {
            $fechaKey = $det->fecha instanceof Carbon ? $det->fecha->toDateString() : (string)$det->fecha;
            $cantidades[$det->producto_id][$fechaKey] = (float)$det->cantidad;
        }

        $precios = [];
        $pp = DB::table('producto_proveedor')
            ->select('producto_id', 'precio')
            ->whereIn('producto_id', $productoIds)
            ->where('estado', 1)
            ->orderByDesc('fecha_vigencia_inicio')
            ->orderByDesc('id')
            ->get()
            ->groupBy('producto_id');

        foreach ($productoIds as $pid) {
            $precios[$pid] = isset($pp[$pid]) ? (float)($pp[$pid][0]->precio ?? 0) : 0;
        }

        $nombre = 'pedido_diario_' . strtolower($pedido->tipo) . '_semana_' .
            Carbon::parse($pedido->semana_inicio)->format('Ymd') . '_' .
            Carbon::parse($pedido->semana_fin)->format('Ymd') . '.pdf';

        $pdf = Pdf::loadView('dashboard.pedidos_diarios.pdf', compact(
            'pedido','productos','days','cantidades','precios'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream($nombre);
    }

    public function edit($id)
    {
        $pedido = PedidoDiario::with(['detalles.producto', 'unidadOperativa'])->findOrFail($id);

        $this->assertTipo($pedido->tipo);

        $unidades = UnidadOperativa::orderBy('nombre')->get();
        $productos = $this->productosPorTipo($pedido->tipo);
        $days = $this->daysOfWeek($pedido->semana_inicio);

        $cantidades = [];
        foreach ($pedido->detalles as $det) {
            $fechaKey = $det->fecha instanceof Carbon ? $det->fecha->toDateString() : (string)$det->fecha;
            $cantidades[$det->producto_id][$fechaKey] = (float)$det->cantidad;
        }

        return view('dashboard.pedidos_diarios.create', [
            'modo' => 'edit',
            'pedido' => $pedido,
            'tipo' => $pedido->tipo,
            'unidades' => $unidades,
            'productos' => $productos,
            'days' => $days,
            'cantidades' => $cantidades,
        ]);
    }

    public function update(Request $request, $id)
    {
        $pedido = PedidoDiario::with('detalles')->findOrFail($id);
        $this->assertTipo($pedido->tipo);

        return $this->savePedido($request, $pedido->tipo, $pedido);
    }

    // =========================
    // Internals
    // =========================

    private function createByTipo(Request $request, string $tipo)
    {
        $this->assertTipo($tipo);

        $fechaReferencia = $request->get('fecha', now()->toDateString());
        [$semanaInicio, $semanaFin] = $this->weekRangeFromAnyDate($fechaReferencia);

        $unidades = UnidadOperativa::orderBy('nombre')->get();
        $productos = $this->productosPorTipo($tipo);
        $days = $this->daysOfWeek($semanaInicio);

        $pedidoExistente = null;
        $cantidades = [];

        if ($request->filled('unidad_operativa_id')) {
            $pedidoExistente = PedidoDiario::with('detalles')
                ->where('unidad_operativa_id', $request->unidad_operativa_id)
                ->where('tipo', $tipo)
                ->where('semana_inicio', $semanaInicio)
                ->first();

            if ($pedidoExistente) {
                foreach ($pedidoExistente->detalles as $det) {
                    $fechaKey = $det->fecha instanceof Carbon ? $det->fecha->toDateString() : (string)$det->fecha;
                    $cantidades[$det->producto_id][$fechaKey] = (float)$det->cantidad;
                }
            }
        }

        return view('dashboard.pedidos_diarios.create', [
            'modo' => $pedidoExistente ? 'edit' : 'create',
            'pedido' => $pedidoExistente,
            'tipo' => $tipo,
            'unidades' => $unidades,
            'productos' => $productos,
            'days' => $days,
            'semana_inicio' => $semanaInicio,
            'semana_fin' => $semanaFin,
            'cantidades' => $cantidades,
        ]);
    }

    private function storeByTipo(Request $request, string $tipo)
    {
        $this->assertTipo($tipo);

        [$semanaInicio, $semanaFin] = $this->weekRangeFromAnyDate($request->fecha_referencia);

        $pedido = PedidoDiario::where('unidad_operativa_id', $request->unidad_operativa_id)
            ->where('tipo', $tipo)
            ->where('semana_inicio', $semanaInicio)
            ->first();

        return $this->savePedido($request, $tipo, $pedido, $semanaInicio, $semanaFin);
    }

    /**
     * Guarda/actualiza un pedido diario (PAN/TORTILLA).
     */
    private function savePedido(
        Request $request,
        string $tipo,
        ?PedidoDiario $pedido = null,
        ?string $semanaInicioOverride = null,
        ?string $semanaFinOverride = null
    ) {
        $this->assertTipo($tipo);

        $request->validate([
            'unidad_operativa_id' => ['required', 'exists:unidades_operativas,id'],
            'fecha_referencia'    => ['required', 'date'],
            'observaciones'       => ['nullable', 'string'],
            'cantidades'          => ['nullable', 'array'],
        ]);

        [$semanaInicio, $semanaFin] = $this->weekRangeFromAnyDate($request->fecha_referencia);
        $semanaInicio = $semanaInicioOverride ?? $semanaInicio;
        $semanaFin = $semanaFinOverride ?? $semanaFin;

        $days = $this->daysOfWeek($semanaInicio);

        $productos = $this->productosPorTipo($tipo);
        $productosById = $productos->keyBy('id');

        DB::transaction(function () use ($request, $tipo, &$pedido, $semanaInicio, $semanaFin, $days, $productosById) {

            if (!$pedido) {
                $pedido = new PedidoDiario();
                $pedido->tipo = $tipo;
                $pedido->estado = 'Pendiente';
            }

            $pedido->unidad_operativa_id = $request->unidad_operativa_id;
            $pedido->semana_inicio = $semanaInicio;
            $pedido->semana_fin = $semanaFin;
            $pedido->observaciones = $request->observaciones;

            if (auth()->check()) {
                $pedido->user_id = auth()->id();
            }

            // ✅ Generar código tipo pedido normal (con retry)
            if (!$pedido->codigo) {
                $intentos = 0;

                
                while (true) {
                    $intentos++;

                    try {
                        if (!$pedido->codigo) {
                            $pedido->codigo = PedidoDiario::generarCodigo(); // ✅ desde el modelo
                        }

                        $pedido->save(); // ✅ un solo save
                        break;

                    } catch (QueryException $e) {
                        $sqlState    = $e->errorInfo[0] ?? null;
                        $driverCode  = $e->errorInfo[1] ?? null;
                        $esDuplicado = ($sqlState === '23000' && (int)$driverCode === 1062);

                        // ✅ si chocó el UNIQUE(codigo), limpiamos y reintentamos
                        if ($esDuplicado && $intentos < 5) {
                            $pedido->codigo = null;
                            continue;
                        }

                        throw $e;
                    }
                }
            } else {
                $pedido->save();
            }

            // Regenerar detalles
            PedidoDiarioDetalle::where('pedido_diario_id', $pedido->id)->delete();

            $cantidadesInput = $request->input('cantidades', []);
            $inserts = [];

            foreach ($cantidadesInput as $productoId => $porFecha) {
                $productoId = (int)$productoId;

                if (!$productosById->has($productoId)) {
                    continue;
                }

                foreach ($porFecha as $fecha => $cantidad) {
                    if (!in_array($fecha, $days, true)) {
                        continue;
                    }

                    $cantidad = is_null($cantidad) || $cantidad === '' ? 0 : (float)$cantidad;

                    if ($tipo === 'PAN' && floor($cantidad) != $cantidad) {
                        throw new \RuntimeException("En PAN no se permiten decimales (producto {$productoId} en {$fecha}).");
                    }

                    if ($cantidad <= 0) {
                        continue;
                    }

                    $producto = $productosById->get($productoId);
                    $prov = $producto->proveedores->first();
                    $precio = $prov ? (float)($prov->pivot->precio ?? 0) : 0;
                    $subtotal = $cantidad * $precio;

                    $inserts[] = [
                        'pedido_diario_id' => $pedido->id,
                        'fecha' => $fecha,
                        'producto_id' => $productoId,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($inserts)) {
                PedidoDiarioDetalle::insert($inserts);
            }
        });

        return redirect()
            ->back()
            ->with('success', "Pedido diario {$tipo} guardado correctamente.");
    }

        private function role(): string
    {
        return Auth::user()->role ?? '';
    }

    private function esAdminPedidos(): bool
    {
        return in_array($this->role(), ['admin', 'encargado_pedidos'], true);
    }

    private function esCEO(): bool
    {
        return $this->role() === 'ceo';
    }

    public function marcarVisto($id)
    {
        abort_unless($this->esAdminPedidos(), 403);

        $pedido = PedidoDiario::findOrFail($id);

        // Recomendado: unificar a Pendiente en tu savePedido()
        if ($pedido->estado === 'Pendiente' || $pedido->estado === 'Solicitado') {
            $pedido->update(['estado' => 'Visto']);
        }

            return redirect()
                ->route('dashboard.pedidos_diarios.show', $pedido->id)
                ->with('success', 'Estado actualizado.');

    }

    public function preaprobar($id)
    {
        abort_unless($this->esAdminPedidos(), 403);

        $pedido = PedidoDiario::findOrFail($id);

        abort_if(!in_array($pedido->estado, ['Visto', 'En revision'], true), 403,
            'Solo puedes preaprobar pedidos vistos o en revisión.'
        );

        $pedido->update([
            'estado' => 'Preaprobado',
            // Si tienes columna preaprobado_por en pedidos_diarios, descomenta:
            // 'preaprobado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('dashboard.pedidos_diarios.show', $pedido->id)
            ->with('success', 'Estado actualizado.');
;
    }

    public function ceoAprobar($id)
    {
        abort_unless($this->esCEO(), 403);

        $pedido = PedidoDiario::findOrFail($id);

        abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes aprobar pedidos diarios preaprobados.');

        $pedido->update(['estado' => 'Aprobado']);

        return redirect()
                ->route('dashboard.pedidos_diarios.show', $pedido->id)
                ->with('success', 'Estado actualizado.');
    }

    public function ceoEnviarRevision(Request $request, $id)
    {
        abort_unless($this->esCEO(), 403);

        $pedido = PedidoDiario::findOrFail($id);

        abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes enviar a revisión pedidos diarios preaprobados.');

        $request->validate([
            'observacion' => ['required', 'string', 'min:3'],
        ]);

        $data = [
            'estado' => 'En revision',
        ];

        // Si tienes columna observaciones_ceo en pedidos_diarios, guardamos ahí.
        if (\Schema::hasColumn('pedidos_diarios', 'observaciones_ceo')) {
            $data['observaciones_ceo'] = $request->observacion;
        } else {
            // Si NO existe, lo guardo en "observaciones" (si existe).
            if (\Schema::hasColumn('pedidos_diarios', 'observaciones')) {
                $data['observaciones'] = $request->observacion;
            }
        }

        $pedido->update($data);

        return redirect()
                ->route('dashboard.pedidos_diarios.show', $pedido->id)
                ->with('success', 'Estado actualizado.');
    }

    public function regresarAVisto(Request $request, $id)
    {
        $pedido = PedidoDiario::findOrFail($id);

        $role = auth()->user()->role ?? '';
        $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos'], true);

        abort_unless($esAdminPedidos, 403);

        // Solo tiene sentido si estaba preaprobado
        if ($pedido->estado !== 'Preaprobado') {
            return redirect()
                ->route('dashboard.pedidos_diarios.show', $pedido->id)
                ->with('warning', 'Solo puedes regresar a visto un pedido Preaprobado.');
        }

        // Observación opcional (por auditoría)
        $obs = trim((string)$request->input('observacion', ''));

        $pedido->estado = 'Visto';
        if ($obs !== '') {
            $pedido->observaciones = $obs; // o concatena si prefieres histórico
        }
        $pedido->save();

        return redirect()
            ->route('dashboard.pedidos_diarios.show', $pedido->id)
            ->with('success', 'Pedido regresado a Visto correctamente.');
    }

    public function rechazar(Request $request, $id)
    {
        $pedido = PedidoDiario::findOrFail($id);

        $role = auth()->user()->role ?? '';
        $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos'], true);

        abort_unless($esAdminPedidos, 403);

        // Desde qué estados permites rechazar
        $permitidos = ['Visto', 'En revision', 'Preaprobado'];

        if (!in_array($pedido->estado, $permitidos, true)) {
            return redirect()
                ->route('dashboard.pedidos_diarios.show', $pedido->id)
                ->with('warning', 'Este pedido no puede rechazarse en su estado actual.');
        }

        $request->validate([
            'observacion' => ['required', 'string', 'min:3'],
        ]);

        $pedido->estado = 'Rechazado';
        $pedido->observaciones = trim((string)$request->observacion);
        $pedido->save();

        return redirect()
            ->route('dashboard.pedidos_diarios.show', $pedido->id)
            ->with('success', 'Pedido rechazado correctamente.');
    }

}
