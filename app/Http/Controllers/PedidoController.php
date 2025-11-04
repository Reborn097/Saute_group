<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Pedido;

class PedidoController extends Controller
{
    // Mostrar la vista para crear pedido
    public function crear()
    {
        $productos = Producto::with(['categoria', 'proveedores'])->get();
        $proveedores = Proveedor::all();

        return view('dashboard.crear_pedido', compact('productos', 'proveedores'));
    }

    public function solicitar()
{
    $productos = Producto::with(['categoria', 'proveedores'])->get();
    $proveedores = Proveedor::all();
    return view('dashboard.crear_pedido', compact('productos', 'proveedores'));
}

public function guardar(Request $request)
{
    $pedido = Pedido::create([
        'codigo' => Pedido::generarCodigo(),
        'fecha_solicitud' => now(),
        'user_id' => auth()->id(),
        'total' => $request->total ?? 0,
        'estado' => 'Pendiente'
    ]);

    return redirect()->route('dashboard.pedidos.solicitar')
        ->with('success', 'Pedido creado correctamente con código: ' . $pedido->codigo);
}

    // Mostrar la vista para previsualizar pedido
    public function previsualizar(Request $request)
    {
        return view('dashboard.previsualizar_pedido');
    }
}
