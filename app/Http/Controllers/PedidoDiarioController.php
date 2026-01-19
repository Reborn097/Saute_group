<?php

namespace App\Http\Controllers;

use App\Models\PedidoDiario;
use App\Models\PedidoDiarioDetalle;
use App\Models\Producto;
use App\Models\UnidadOperativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class PedidoDiarioController extends Controller
{
    
    private const CATEGORIA_PAN_ID = 8;      // <-- CAMBIAR
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
            // Trae el pivot más “reciente” (por fecha de inicio o por id)
            $q->orderByDesc('producto_proveedor.fecha_vigencia_inicio')
              ->orderByDesc('producto_proveedor.id');
        }])
        ->whereHas('proveedores') // con que tenga proveedor, se muestra
        ->orderBy('nombre')
        ->get()
        ->map(function ($p) {
            // Nos quedamos con el primer proveedor (porque dices que solo hay 1)
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
    // Editar (común)
    // =========================

    public function index()
    {
        $pedidos = \App\Models\PedidoDiario::with(['unidadOperativa','detalles'])
            ->orderByDesc('semana_inicio')
            ->orderByDesc('id')
            ->get();

        return view('dashboard.pedidos_diarios.index', compact('pedidos'));
    }

    public function show($id)
    {
        // ============================
        // 1) Cargar pedido principal
        // ============================
        $pedido = PedidoDiario::with([
            'unidadOperativa',
            'usuario',      // quien creó el pedido
            'detalles'      // detalles por día
        ])->findOrFail($id);

        // ============================
        // 2) Calcular rango de días (lunes a domingo)
        // ============================
        $inicio = Carbon::parse($pedido->semana_inicio);
        $fin    = Carbon::parse($pedido->semana_fin);

        $days = [];
        $cursor = $inicio->copy();
        while ($cursor->lte($fin)) {
            $days[] = $cursor->toDateString(); // Y-m-d
            $cursor->addDay();
        }

        // ============================
        // 3) Recuperar productos del pedido
        // ============================
        // Traemos SOLO los productos que existen en los detalles
        $productoIds = $pedido->detalles->pluck('producto_id')->unique();

        $productos = Producto::with(['proveedores' => function ($q) {
                // Tomamos el proveedor más reciente (solo hay uno en pan/tortilla)
                $q->orderByPivot('fecha_vigencia_inicio', 'desc')
                ->orderByPivot('id', 'desc');
            }])
            ->whereIn('id', $productoIds)
            ->orderBy('nombre')
            ->get();

        // ============================
        // 4) Armar arreglo cantidades[producto_id][fecha]
        // ============================
        $cantidades = [];

        foreach ($pedido->detalles as $det) {

            // Convertir fecha (puede venir como Carbon)
            $fechaKey = $det->fecha instanceof Carbon
                ? $det->fecha->toDateString()
                : (string) $det->fecha;

            $cantidades[$det->producto_id][$fechaKey] = (float) $det->cantidad;
        }

        // ============================
        // 5) Retornar vista
        // ============================
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

        // días (lunes-domingo según lo guardado)
        $inicio = Carbon::parse($pedido->semana_inicio);
        $fin    = Carbon::parse($pedido->semana_fin);

        $days = [];
        $c = $inicio->copy();
        while ($c->lte($fin)) {
            $days[] = $c->toDateString();
            $c->addDay();
        }

        // productos del pedido
        $productoIds = $pedido->detalles->pluck('producto_id')->unique()->values();

        $productos = Producto::whereIn('id', $productoIds)
            ->orderBy('nombre')
            ->get();

        // cantidades[producto_id][fecha]
        $cantidades = [];
        foreach ($pedido->detalles as $det) {
            $fechaKey = $det->fecha instanceof Carbon ? $det->fecha->toDateString() : (string)$det->fecha;
            $cantidades[$det->producto_id][$fechaKey] = (float)$det->cantidad;
        }

        // precios por producto desde producto_proveedor:
        // (1 proveedor por producto en pan/tortilla, tomamos el primero)
        $precios = [];
        $pp = \DB::table('producto_proveedor')
            ->select('producto_id', 'precio')
            ->whereIn('producto_id', $productoIds)
            ->where('estado', 1) // si manejas estado
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
        ))->setPaper('a4', 'landscape'); // landscape por la tabla

        return $pdf->stream($nombre);
    }

    public function edit($id)
    {
        $pedido = PedidoDiario::with(['detalles.producto', 'unidadOperativa'])->findOrFail($id);

        $this->assertTipo($pedido->tipo);

        $unidades = UnidadOperativa::orderBy('nombre')->get();
        $productos = $this->productosPorTipo($pedido->tipo);
        $days = $this->daysOfWeek($pedido->semana_inicio);

        // Mapa para pintar la tabla: [producto_id][fecha] => cantidad
        $cantidades = [];
        foreach ($pedido->detalles as $det) {
            $fechaKey = $det->fecha instanceof \Carbon\Carbon ? $det->fecha->toDateString() : (string)$det->fecha;
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

        // Para que el usuario seleccione una fecha y con eso calculamos lunes-domingo
        $fechaReferencia = $request->get('fecha', now()->toDateString());
        [$semanaInicio, $semanaFin] = $this->weekRangeFromAnyDate($fechaReferencia);

        $unidades = UnidadOperativa::orderBy('nombre')->get();
        $productos = $this->productosPorTipo($tipo);
        $days = $this->daysOfWeek($semanaInicio);

        // Si ya existe pedido de esa semana/unidad/tipo (cuando ya eligió unidad), lo cargamos
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
                    $fechaKey = $det->fecha instanceof \Carbon\Carbon ? $det->fecha->toDateString() : (string)$det->fecha;
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

        // Si ya existe, lo actualizamos (para evitar duplicados)
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

        // Productos permitidos para el tipo (evita que te manden ids raros)
        $productos = $this->productosPorTipo($tipo);
        $productosById = $productos->keyBy('id');

        DB::transaction(function () use ($request, $tipo, $pedido, $semanaInicio, $semanaFin, $days, $productosById) {

            if (!$pedido) {
                $pedido = new PedidoDiario();
                $pedido->tipo = $tipo;
                $pedido->estado = 'Solicitado';
            }

            $pedido->unidad_operativa_id = $request->unidad_operativa_id;
            $pedido->semana_inicio = $semanaInicio;
            $pedido->semana_fin = $semanaFin;
            $pedido->observaciones = $request->observaciones;

            if (auth()->check()) {
                $pedido->user_id = auth()->id();
            }

            // Si quieres generar codigo/folio:
            if (!$pedido->codigo) {
                $pedido->codigo = $tipo . '-' . $semanaInicio . '-' . $request->unidad_operativa_id;
            }

            $pedido->save();

            // Para simplificar: borramos detalles de esa semana y los regeneramos
            PedidoDiarioDetalle::where('pedido_diario_id', $pedido->id)->delete();

            $cantidadesInput = $request->input('cantidades', []);
            $inserts = [];

            foreach ($cantidadesInput as $productoId => $porFecha) {
                $productoId = (int)$productoId;

                // Solo aceptar productos del tipo correcto
                if (!$productosById->has($productoId)) {
                    continue;
                }

                foreach ($porFecha as $fecha => $cantidad) {
                    if (!in_array($fecha, $days, true)) {
                        continue;
                    }

                    $cantidad = is_null($cantidad) || $cantidad === '' ? 0 : (float)$cantidad;

                    // Si es PAN, no permitir decimales
                    if ($tipo === 'PAN' && floor($cantidad) != $cantidad) {
                        // aquí puedes lanzar excepción o redondear
                        throw new \RuntimeException("En PAN no se permiten decimales (producto {$productoId} en {$fecha}).");
                    }

                    if ($cantidad <= 0) {
                        continue; // no guardamos ceros
                    }

                    $producto = $productosById->get($productoId);
                    $prov = $producto->proveedores->first(); // solo hay uno
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

        

        // Redirección sugerida (ajusta rutas)
        return redirect()
            ->back()
            ->with('success', "Pedido diario {$tipo} guardado correctamente.");
    }
}
