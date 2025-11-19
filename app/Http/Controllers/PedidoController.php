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
        $productos = Producto::with([
            'categoria',
            'proveedores' => function($q){
                $q->select('proveedores.id', 'nombre')
                  ->withPivot('id', 'precio'); // 🔥 FIX IMPORTANTE
            }
        ])->get();

        $proveedores = Proveedor::all();

        return view('dashboard.crear_pedido', compact('productos', 'proveedores'));
    }

    public function solicitar()
    {
        return $this->crear();
    }

    // ===== Guardar pedido =====
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

            // Crear pedido general
            $pedido = Pedido::create([
                'codigo'          => 'SPJ' . rand(1000, 9999),
                'fecha_solicitud' => $data['fecha_solicitud'],
                'fecha_entrega'   => $data['fecha_entrega'],
                'user_id'         => Auth::id() ?? 1,
                'total'           => 0,
                'estado'          => 'En revisión',
            ]);

            $total = 0;

            // ===== Guardar cada producto seleccionado =====
            foreach ($data['productos'] as $p) {

                $pp = ProductoProveedor::with(['producto', 'proveedor'])
                        ->find($p['producto_proveedor_id']);

                if (!$pp) {
                    Log::warning("ID inválido de producto_proveedor", $p);
                    continue;
                }

                $cantidad = $p['cantidad'] ?? 1;
                $precio   = $p['precio'];  
                $subtotal = $cantidad * $precio;
                $total   += $subtotal;

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

    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido');
    }

    public function consultar()
    {
        $pedidos = Pedido::with('usuario')
            ->orderBy('fecha_solicitud', 'desc')
            ->get();

        return view('dashboard.consultar_pedidos', compact('pedidos'));
    }

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
}
