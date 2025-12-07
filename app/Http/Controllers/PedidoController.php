<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductoProveedor;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    // ===== Mostrar formulario para crear pedido =====
    public function crear()
{
    // Productos con proveedores y categoría
    $productos = Producto::with([
        'categoria',
        'proveedores' => function ($q) {
            $q->select('proveedores.id', 'nombre')
              ->withPivot('id', 'precio');
        }
    ])->get();

    // 🔥 Necesario para el filtro en la vista
    $proveedores = Proveedor::orderBy('nombre')->get();

    return view('dashboard.crear_pedido', compact('productos', 'proveedores'));
}



    public function solicitar()
    {
        return $this->crear();
    }

    // ===== Guardar pedido (CREACIÓN) =====
    public function guardar(Request $request)
    {
        try {
            $data = $request->json()->all();

            Log::info("Datos recibidos desde el frontend:", $data);

            if (!$data || empty($data['productos'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibieron productos válidos.'
                ], 400);
            }

            if (empty($data['fecha_solicitud']) || empty($data['fecha_entrega'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las fechas son obligatorias.'
                ], 422);
            }

            // Crear pedido
            $pedido = Pedido::create([
                'codigo'          => 'dec' . date("md") . rand(1000, 9999),
                'fecha_solicitud' => $data['fecha_solicitud'],
                'fecha_entrega'   => $data['fecha_entrega'],
                'user_id'         => Auth::id() ?? 1,
                'total'           => 0,
                'estado'          => 'Pendiente',
            ]);

            $total = 0;

            // Guardar productos del pedido
            foreach ($data['productos'] as $p) {

                $pp = ProductoProveedor::with(['producto', 'proveedor'])
                    ->find($p['producto_proveedor_id']);

                if (!$pp) {
                    Log::warning("ID inválido de producto_proveedor", $p);
                    continue;
                }

                $cantidad = floatval($p['cantidad']);
                $precio   = floatval($p['precio']);
                $subtotal = $cantidad * $precio;

                $total += $subtotal;

                DetallePedido::create([
                    'codigo'               => $pedido->codigo,
                    'producto_proveedor_id'=> $pp->id,
                    'cantidad_solicitada'  => $cantidad,
                    'precio_unitario'      => $precio,
                    'subtotal'             => $subtotal,
                ]);
            }

            // Actualizar total
            $pedido->update(['total' => $total]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado correctamente',
                'codigo'  => $pedido->codigo,
            ]);

        } catch (\Throwable $e) {

            Log::error("Error al guardar pedido: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => "Error al guardar pedido: " . $e->getMessage(),
            ], 500);
        }
    }

    // =====================
    //   PREVISUALIZACIÓN
    // =====================
    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido');
    }

    // =====================
    //   CONSULTAR PEDIDOS
    // =====================
    public function consultar(Request $request)
{
    $tipo = $request->get('tipo', 'todos'); // valores: todos, normales, especiales

    $query = Pedido::with('usuario');

    if ($tipo === 'normales') {
        $query->where('es_especial', 0);
    } elseif ($tipo === 'especiales') {
        $query->where('es_especial', 1);
    }

    $pedidos = $query->orderBy('fecha_solicitud', 'desc')->get();

    return view('dashboard.consultar_pedidos', compact('pedidos', 'tipo'));
}


    // =====================
    //   DETALLE DEL PEDIDO
    // =====================
    public function detalle($codigo)
    {
        $pedido = Pedido::with([
            'usuario',
            'detalles.productoProveedor.producto.categoria',
            'detalles.productoProveedor.proveedor'
        ])
        ->where('codigo', $codigo)
        ->firstOrFail();

        return view('dashboard.detalle_pedido', compact('pedido'));
    }

    // =====================
    //   EDITAR PEDIDO
    // =====================
    public function editar($codigo)
    {
        $pedido = Pedido::with([
            'detalles.productoProveedor.producto.categoria',
            'detalles.productoProveedor.proveedor'
        ])
        ->where('codigo', $codigo)
        ->firstOrFail();

        $productos = Producto::with('categoria','proveedores')->get();

        // --- ARMAR ITEMS COMPLETOS PARA JS ---
        $itemsPedido = [];

        foreach ($pedido->detalles as $item) {

            $itemsPedido[] = [
                'producto_proveedor_id' => $item->producto_proveedor_id,
                'producto_id'           => $item->productoProveedor->producto->id,
                'proveedor_id'          => $item->productoProveedor->proveedor->id,
                'proveedor'             => $item->productoProveedor->proveedor->nombre,
                'nombre'                => $item->productoProveedor->producto->nombre,
                'categoria'             => $item->productoProveedor->producto->categoria->nombre ?? '',
                'unidad'                => $item->productoProveedor->producto->unidad_medida ?? '',
                'cantidad'              => floatval($item->cantidad_solicitada),
                'precio'                => floatval($item->precio_unitario),
                'subtotal'              => floatval($item->subtotal),
            ];
        }

        return view('dashboard.editar_admin_pedido', [
            'pedido'      => $pedido,
            'productos'   => $productos,
            'itemsPedido' => $itemsPedido
        ]);
    }

    // =====================
    //   ACTUALIZAR PEDIDO
    // =====================
    public function actualizar(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $items  = json_decode($request->items_json, true);

        if (!$items || count($items) == 0) {
            return back()->with('error', 'Debe agregar al menos un producto.');
        }

        // Borrar items anteriores
        DetallePedido::where('codigo', $codigo)->delete();

        $total = 0;

        foreach ($items as $it) {

            $cantidad = floatval($it['cantidad']);
            $precio   = floatval($it['precio']);
            $subtotal = $cantidad * $precio;

            $total += $subtotal;

            DetallePedido::create([
                'codigo'               => $codigo,
                'producto_proveedor_id'=> $it['producto_proveedor_id'],
                'cantidad_solicitada'  => $cantidad,
                'precio_unitario'      => $precio,
                'subtotal'             => $subtotal,
            ]);
        }

        $pedido->update(['total' => $total]);

        return redirect()->route('dashboard.pedidos.admin')
            ->with('success', 'Pedido actualizado correctamente');
    }
}
