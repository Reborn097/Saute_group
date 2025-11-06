<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Pedido;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\Auth;

class PedidoController extends Controller
{
    // Mostrar formulario para crear pedido
    public function crear()
    {
        $productos = Producto::with(['categoria', 'proveedores'])->get();
        $proveedores = Proveedor::all();
        return view('dashboard.crear_pedido', compact('productos', 'proveedores'));
    }

    // Alias de crear (por compatibilidad)
    public function solicitar()
    {
        return $this->crear();
    }

    // Guardar pedido confirmado desde la previsualización
    public function guardar(Request $request)
{
    // Validar los datos que llegan desde la previsualización
    $data = $request->validate([
        'fecha_solicitud' => 'required|date',
        'fecha_entrega' => 'required|date',
        'productos' => 'required|array',
        'productos.*.id' => 'required|integer',
        'productos.*.cantidad' => 'required|numeric|min:1',
        'productos.*.precio' => 'required|numeric|min:0',
    ]);

    // Crear pedido principal
    $pedido = Pedido::create([
        'codigo' => 'SPJ' . rand(1000, 9999),
        'fecha_solicitud' => $data['fecha_solicitud'],
        'fecha_entrega' => $data['fecha_entrega'],
        'user_id' => auth()->id() ?? 1, // si no hay login
        'total' => 0,
        'estado' => 'En revisión',
    ]);

    $total = 0;

    // Recorrer los productos enviados desde la vista
    foreach ($data['productos'] as $p) {
        $subtotal = $p['precio'] * $p['cantidad'];
        $total += $subtotal;

        DetallePedido::create([
            'codigo' => $pedido->codigo,
            'producto_proveedor_id' => $p['id'], // usa tu columna real
            'precio_unitario' => $p['precio'],
            'cantidad_solicitada' => $p['cantidad'],
        ]);
    }

    // Actualiza el total en el pedido
    $pedido->update(['total' => $total]);

    return response()->json([
        'success' => true,
        'message' => 'Pedido guardado correctamente',
        'codigo' => $pedido->codigo,
    ]);
}


    // Vista de previsualización del pedido
    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido');
    }

    // Consultar pedidos registrados
    public function consultar()
    {
        $pedidos = Pedido::with('usuario')->orderBy('fecha_solicitud', 'desc')->get();
        return view('dashboard.consultar_pedidos', compact('pedidos'));
    }

    // Visualizar pedido específico
    public function visualizar($id)
    {
        $pedido = Pedido::with(['usuario', 'detalles.producto'])->findOrFail($id);
        return view('dashboard.visualizar_pedido', compact('pedido'));
    }

    public function detalle($codigo)
{
    $pedido = Pedido::with([
        'usuario',
        'detalles.productoProveedor.producto.categoria'
    ])->where('codigo', $codigo)->firstOrFail();

    return view('dashboard.detalle_pedido', compact('pedido'));
}


}
