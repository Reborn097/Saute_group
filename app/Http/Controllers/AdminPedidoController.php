<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\PedidoEspecial;

use Barryvdh\DomPDF\Facade\Pdf;

class AdminPedidoController extends Controller
{
    /**
     * Listado administrativo de pedidos
     * - Admin: todos
     * - CEO: solo Preaprobado / En revision
     */
    public function index(Request $request)
    {
        $role = Auth::user()->role;

        // ✅ Defaults: últimos 7 días hasta hoy
        $hoy   = now()->toDateString();
        $desde = $request->get('desde', now()->subDays(7)->toDateString());
        $hasta = $request->get('hasta', $hoy);

        // ✅ Buscar por código
        $codigo = trim((string) $request->get('codigo', ''));

        $query = Pedido::with('usuario');

        // ✅ CEO: solo ciertos estados
        if ($role === 'ceo') {
            $query->whereIn('estado', ['Preaprobado', 'En revision']);
        }

        // ✅ Filtro por estado (si viene)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // ✅ Rango de fechas (incluyente)
        // Usamos fecha_solicitud (porque es lo que manejas en pedidos)
        // Si prefieres created_at, cambia 'fecha_solicitud' por 'created_at'
        $query->whereDate('fecha_solicitud', '>=', $desde)
            ->whereDate('fecha_solicitud', '<=', $hasta);

        // ✅ Buscador por código (parcial)
        if ($codigo !== '') {
            $query->where('codigo', 'like', "%{$codigo}%");
        }

        // ✅ Orden + paginación 10
        $pedidos = $query
            ->orderBy('fecha_solicitud', 'desc')
            ->paginate(10)
            ->appends($request->query());

        return view('dashboard.administrar_pedidos', compact('pedidos', 'desde', 'hasta', 'codigo'));
    }


    /**
     * Detalle (solo lectura)
     */
    public function detalle($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->with([
                'usuario',
                'detalles.productoProveedor.producto.categoria',
                'detalles.productoProveedor.proveedor'
            ])
            ->firstOrFail();

        $pedidoEspecial = PedidoEspecial::where('codigo', $codigo)->first();

        return view('dashboard.detalle_pedido', compact('pedido', 'pedidoEspecial'));
    }

    /**
     * Editar (pantalla tipo crear)
     * - Solo si NO está Preaprobado/Aprobado
     */
    public function editar(Request $request, $codigo)
{
    $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

    if (in_array($pedido->estado, ['Preaprobado', 'Aprobado'])) {
        return redirect()
            ->route('dashboard.pedidos.admin')
            ->with('warning', 'Este pedido ya no puede editarse porque está Preaprobado o Aprobado.');
    }

    $detalles = DetallePedido::with([
        'productoProveedor.producto.categoria',
        'productoProveedor.proveedor'
    ])->where('codigo', $codigo)->get();

    // ✅ Items del pedido (para JS)
    // ✅ Items del pedido (para JS) - incluir activos e inactivos
$itemsPedido = $detalles
    ->map(function ($d) {
        $pp   = $d->productoProveedor;
        $prod = $pp->producto;
        $prov = $pp->proveedor;

        return [
            'producto_proveedor_id' => $pp->id,
            'producto_id'           => $prod->id,
            'proveedor_id'          => $prov->id,
            'nombre'                => $prod->nombre,
            'marca'                 => $prod->marca ?? '',
            'categoria'             => $prod->categoria->nombre ?? '',
            'unidad'                => $prod->unidad_medida ?? '',
            'proveedor'             => $prov->nombre,
            'precio'                => (float) $d->precio_unitario,

            'cantidad_solicitada'   => (float) $d->cantidad_solicitada,
            'cantidad_aprobada'     => (float) ($d->cantidad_aprobada ?? $d->cantidad_solicitada),

            // 👇 IMPORTANTE: mandar activo tal cual venga de BD
            'activo'                => (int) ($d->activo ?? 1),

            // subtotal como venga (tu front lo recalcula igual)
            'subtotal'              => (float) $d->subtotal,
        ];
    })
    ->values();


    // ===========================
    // ✅ FILTROS / BUSCADOR
    // ===========================
    $q            = trim((string) $request->get('q', ''));
    $categoriaId  = $request->get('categoria_id');
    $proveedorId  = $request->get('proveedor_id');

    // listas para selects
    $categorias  = \App\Models\Categoria::orderBy('nombre')->get();
    $proveedores = \App\Models\Proveedor::orderBy('nombre')->get();

    // catálogo paginado
    $productosQuery = Producto::query()
        ->with([
            'categoria',
            'proveedores' => function ($q) {
                $q->select('proveedores.id', 'nombre')
                  ->withPivot('id', 'precio');
            }
        ])
        ->orderBy('nombre');

    if ($q !== '') {
        $productosQuery->where(function ($sub) use ($q) {
            $sub->where('nombre', 'like', "%{$q}%")
                ->orWhere('marca', 'like', "%{$q}%");
        });
    }

    if (!empty($categoriaId)) {
        $productosQuery->where('categoria_id', $categoriaId);
    }

    if (!empty($proveedorId)) {
        $productosQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
            $sub->where('proveedores.id', $proveedorId);
        });
    }

    $productos = $productosQuery->paginate(10)->withQueryString();

    return view('dashboard.editar_admin_pedido', compact(
        'pedido',
        'productos',
        'itemsPedido',
        'categorias',
        'proveedores'
    ));
}


    /**
     * Guardar cambios del pedido SIN borrar detalles:
     * - Primero marca todos los detalles del pedido como inactivos
     * - Luego "reactiva" o crea solo los que vienen en items_json
     * - Guarda cantidad_aprobada y subtotal (si está activo)
     *
     * ✅ NO usa cantidad_delta en BD (se calcula en el front).
     */
    public function actualizar(Request $request, $codigo)
{
    $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

    // Bloquear edición si ya está en estados finales
    if (in_array($pedido->estado, ['Preaprobado', 'Aprobado'])) {
        return redirect()
            ->route('dashboard.pedidos.admin')
            ->with('warning', 'Este pedido ya no puede editarse porque está Preaprobado o Aprobado.');
    }

    $itemsJson = $request->input('items_json');
    $items = $itemsJson ? json_decode($itemsJson, true) : [];

    if (!is_array($items)) {
        return back()->with('error', 'El formato de los productos es inválido.');
    }

    DB::transaction(function () use ($pedido, $codigo, $items) {

        // IDs que vienen del front (los que deben quedarse)
        $ppIds = collect($items)
            ->pluck('producto_proveedor_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // 🔥 SOLO inactiva los que NO vienen (NO apagues todo)
        if (!empty($ppIds)) {
            DetallePedido::where('codigo', $codigo)
                ->whereNotIn('producto_proveedor_id', $ppIds)
                ->update(['activo' => 0]); // 0 = inactivo
        } else {
            // si viene vacío, no hagas nada destructivo
            return;
        }

        $total = 0;

        foreach ($items as $it) {

            $ppId = $it['producto_proveedor_id'] ?? null;
            if (!$ppId) continue;

            // front manda estado_detalle (1 activo, 0 inactivo) o activo
            $activo = isset($it['activo'])
                ? (int)$it['activo']
                : (isset($it['estado_detalle']) ? (int)$it['estado_detalle'] : 1);

            // cantidades
            $cantSol = isset($it['cantidad_solicitada']) ? (float)$it['cantidad_solicitada'] : null;
            $cantApr = isset($it['cantidad_aprobada']) ? (float)$it['cantidad_aprobada'] : null;

            // compat: si tu front aún manda "cantidad" úsala como aprobada
            if ($cantApr === null && isset($it['cantidad'])) {
                $cantApr = (float)$it['cantidad'];
            }

            $precio = isset($it['precio']) ? (float)$it['precio'] : 0;

            $detalle = DetallePedido::where('codigo', $codigo)
                ->where('producto_proveedor_id', $ppId)
                ->first();

            if (!$detalle) {
                // nuevo renglón agregado
                $detalle = new DetallePedido();
                $detalle->codigo = $codigo;
                $detalle->producto_proveedor_id = $ppId;
                $detalle->cantidad_solicitada = $cantSol ?? 0; // nuevo: sí guarda solicitada si viene
            } else {
                // existente: NO pises la solicitada si el front no la manda
                if ($cantSol !== null) {
                    $detalle->cantidad_solicitada = $cantSol;
                }
            }

            $detalle->precio_unitario = $precio;
            $detalle->cantidad_aprobada = $cantApr ?? ($detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada);

            // ✅ 1 = activo, 0 = inactivo (así lo tienes en BD)
            $detalle->activo = $activo;

            // subtotal solo si activo
            $detalle->subtotal = ($detalle->activo == 1)
                ? ($detalle->cantidad_aprobada * $detalle->precio_unitario)
                : 0;

            $detalle->save();

            if ($detalle->activo == 1) {
                $total += $detalle->subtotal;
            }
        }

        $pedido->total = $total;
        $pedido->save();
    });

    return redirect()
        ->route('dashboard.pedidos.admin')
        ->with('success', 'Pedido actualizado correctamente.');
}




    /**
     * Cambiar estado del pedido (Admin/CEO)
     * Nota: si NO tienes columna "observaciones" en pedidos, NO intentes guardarla.
     */
    public function cambiarEstado(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $role   = Auth::user()->role;

        $estado = $request->input('estado');
        $obs    = $request->input('observaciones'); // solo si tienes columna en BD

        $estadosAdminPermitidos = [
            'Pendiente',
            'Visto',
            'Preaprobado',
            'Cancelado',
            'En revision',
        ];

        $estadosCeoPermitidos = [
            'Aprobado',
            'En revision',
        ];

        if ($role === 'admin') {
            if (!in_array($estado, $estadosAdminPermitidos)) {
                return back()->with('error', 'Estado no permitido para admin.');
            }

            $pedido->estado = $estado;

            // ⚠️ Solo si existe columna observaciones en la tabla pedidos:
            if ($estado === 'En revision' && $obs && \Schema::hasColumn('pedidos', 'observaciones')) {
                $pedido->observaciones = $obs;
            }

            $pedido->save();
            return back()->with('success', 'Estado actualizado.');
        }

        if ($role === 'ceo') {
            if (!in_array($estado, $estadosCeoPermitidos)) {
                return back()->with('error', 'El CEO solo puede aprobar o mandar a revisión.');
            }

            $pedido->estado = $estado;

            if ($estado === 'En revision') {
                if (!$obs) {
                    return back()->with('error', 'Debes escribir observaciones para mandar a revisión.');
                }
                if (\Schema::hasColumn('pedidos', 'observaciones')) {
                    $pedido->observaciones = $obs;
                }
            }

            if ($estado === 'Aprobado' && \Schema::hasColumn('pedidos', 'observaciones')) {
                $pedido->observaciones = null;
            }

            $pedido->save();
            return back()->with('success', 'Estado actualizado.');
        }

        return redirect($request->input('redirect_to', route('dashboard.pedidos.admin')))
            ->with('success', 'Estado actualizado.');

    }

    /**
     * PDF del pedido
     */
    public function generarPDF($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->with([
                'usuario',
                'detalles.productoProveedor.producto.categoria',
                'detalles.productoProveedor.proveedor'
            ])
            ->firstOrFail();

        $pdf = Pdf::loadView('dashboard.pedido_pdf', [
            'pedido'   => $pedido,
            'detalles' => $pedido->detalles
        ]);

        return $pdf->stream("Pedido_{$pedido->codigo}.pdf");
    }
}
