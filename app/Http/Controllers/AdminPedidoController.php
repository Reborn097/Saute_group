<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminPedidoController extends Controller
{
    /**
     * Listado administrativo de pedidos
     */
    public function index()
    {
        $pedidos = Pedido::orderBy('created_at', 'desc')->get();

        return view('dashboard.administrar_pedidos', compact('pedidos'));
    }

    /**
     * Detalle sólo lectura (como lo tienes)
     */
    public function detalle($codigo)
    {
        $pedido   = Pedido::where('codigo', $codigo)->firstOrFail();
        $detalles = DetallePedido::where('codigo', $codigo)->get();

        return view('dashboard.detalle_admin_pedido', compact('pedido', 'detalles'));
    }

    /**
     * Vista para EDITAR el pedido con pantalla tipo "Crear pedido"
     */
    public function editar($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        // detalles actuales del pedido
        $detalles = DetallePedido::with([
            'productoProveedor.producto.categoria',
            'productoProveedor.proveedor'
        ])->where('codigo', $codigo)->get();

        // productos disponibles (con todos sus proveedores)
        $productos = Producto::with([
            'categoria',
            'proveedores' => function ($q) {
                $q->select('proveedores.id', 'nombre')
                  ->withPivot('id', 'precio');
            }
        ])->get();

        // Formateamos los detalles para JS
        $itemsPedido = $detalles->map(function ($d) {
            $pp   = $d->productoProveedor;
            $prod = $pp->producto;
            $prov = $pp->proveedor;

            return [
                'producto_proveedor_id' => $pp->id,
                'producto_id'           => $prod->id,
                'proveedor_id'          => $prov->id,
                'nombre'                => $prod->nombre,
                'categoria'             => $prod->categoria->nombre ?? '',
                'unidad'                => $prod->unidad_medida ?? '',
                'proveedor'             => $prov->nombre,
                'precio'                => (float) $d->precio_unitario,
                'cantidad'              => (float) $d->cantidad_solicitada,
                'subtotal'              => (float) $d->subtotal,
            ];
        });

        return view('dashboard.editar_admin_pedido', compact(
            'pedido',
            'productos',
            'itemsPedido'
        ));
    }

    /**
     * Guarda TODOS los cambios del pedido
     * - Reemplaza los detalles existentes por los del formulario
     * - Actualiza el total del pedido
     */
    public function actualizar(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        $itemsJson = $request->input('items_json');

        if (!$itemsJson) {
            return back()->with('error', 'No se recibieron productos para el pedido.');
        }

        $items = json_decode($itemsJson, true);

        if (!is_array($items) || empty($items)) {
            return back()->with('error', 'El formato de los productos es inválido.');
        }

        DB::transaction(function () use ($items, $pedido, $codigo) {
            // Eliminamos los detalles anteriores del pedido
            DetallePedido::where('codigo', $codigo)->delete();

            $total = 0;

            foreach ($items as $item) {
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precio   = (float) ($item['precio'] ?? 0);
                $ppId     = $item['producto_proveedor_id'] ?? null;

                if ($cantidad <= 0 || !$ppId) {
                    continue;
                }

                $subtotal = $cantidad * $precio;

                DetallePedido::create([
                    'codigo'              => $codigo,
                    'producto_proveedor_id' => $ppId,
                    'precio_unitario'     => $precio,
                    'cantidad_solicitada' => $cantidad,
                    'subtotal'            => $subtotal,
                ]);

                $total += $subtotal;
            }

            $pedido->total = $total;
            $pedido->save();
        });

        return redirect()
            ->route('dashboard.pedidos.admin')
            ->with('success', 'Pedido actualizado correctamente.');
    }

    /**
     * Cambiar solamente el estado del pedido
     */
    public function cambiarEstado(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $pedido->estado = $request->estado;
        $pedido->save();

        return redirect()->back()->with('success', 'Estado actualizado');
    }

    /**
     * Generar PDF del pedido
     */
    public function generarPDF($codigo)
    {
        $pedido   = Pedido::where('codigo', $codigo)->firstOrFail();
        $detalles = DetallePedido::where('codigo', $codigo)->get();

        $pdf = Pdf::loadView('dashboard.pedido_pdf', [
            'pedido'   => $pedido,
            'detalles' => $detalles
        ]);

        return $pdf->stream("Pedido_{$pedido->codigo}.pdf");
    }
}
