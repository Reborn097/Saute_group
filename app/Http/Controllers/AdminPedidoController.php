<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\DetallePedido;

class AdminPedidoController extends Controller
{
    /**
     * Mostrar listado de pedidos para administración
     */
    public function index()
    {
        $pedidos = Pedido::orderBy('created_at', 'desc')->get();

        return view('dashboard.administrar_pedidos', compact('pedidos'));
    }

    /**
     * Mostrar detalle completo de un pedido
     */
    public function detalle($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $detalles = DetallePedido::where('codigo', $codigo)->get();

        return view('dashboard.detalle_admin_pedido', compact('pedido', 'detalles'));
    }

    /**
     * Vista para editar cantidades
     */
    public function editar($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $detalles = DetallePedido::where('codigo', $codigo)->get();

        return view('dashboard.editar_admin_pedido', compact('pedido', 'detalles'));
    }

    /**
     * Guardar cambios de cantidades
     */
    public function actualizar(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        foreach ($request->cantidad as $idDetalle => $cantidad) {
            $detalle = DetallePedido::find($idDetalle);

            if ($detalle) {
                $detalle->cantidad_solicitada = $cantidad;
                $detalle->save();
            }
        }

        // Recalcular total
        $total = DetallePedido::where('codigo', $codigo)
            ->get()
            ->sum(fn($d) => $d->cantidad_solicitada * $d->precio_unitario);

        $pedido->total = $total;
        $pedido->save();

        return redirect()->route('dashboard.pedidos.admin')
            ->with('success', 'Pedido actualizado correctamente');
    }

    /**
     * Cambiar estado del pedido
     */
    public function cambiarEstado(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        $pedido->estado = $request->estado;
        $pedido->save();

        return redirect()->back()->with('success', 'Estado actualizado');
    }
}
