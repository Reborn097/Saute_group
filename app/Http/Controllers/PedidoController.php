<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Pedido;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    // ===== Mostrar formulario para crear pedido =====
    public function crear()
    {
        // Trae productos con su categoría y proveedores (incluye pivote: precio y id)
        $productos = Producto::with(['categoria', 'proveedores'])->get();
        $proveedores = Proveedor::all();

        return view('dashboard.crear_pedido', compact('productos', 'proveedores'));
    }

    // Alias por compatibilidad
    public function solicitar()
    {
        return $this->crear();
    }

    // ===== Guardar pedido confirmado desde previsualización =====
    public function guardar(Request $request)
    {
        try {
            // ✅ Leer correctamente los datos JSON enviados por fetch()
            $data = $request->json()->all();

            if (!$data || !isset($data['productos']) || empty($data['productos'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibieron productos válidos.'
                ], 400);
            }

            // 🔍 Log para depuración (ver en storage/logs/laravel.log)
            Log::info('Datos recibidos desde el frontend:', $data);

            // ✅ Validación manual
            if (empty($data['fecha_solicitud']) || empty($data['fecha_entrega'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las fechas son obligatorias.'
                ], 422);
            }

            // ===== Crear pedido principal =====
            $pedido = Pedido::create([
                'codigo' => 'SPJ' . rand(1000, 9999),
                'fecha_solicitud' => $data['fecha_solicitud'],
                'fecha_entrega' => $data['fecha_entrega'],
                'user_id' => Auth::id() ?? 1,
                'total' => 0,
                'estado' => 'En revisión',
            ]);

            $total = 0;

            // ===== Recorrer productos recibidos =====
            foreach ($data['productos'] as $p) {
                // Validar existencia del producto y pivote
                if (empty($p['id']) || empty($p['producto_proveedor_id'])) {
                    Log::warning("Producto inválido recibido:", $p);
                    continue; // saltar si no tiene pivote
                }

                $cantidad = $p['cantidad'] ?? 1;
                $precio = $p['precio'] ?? 0;
                $subtotal = $cantidad * $precio;
                $total += $subtotal;

                // Crear detalle
                DetallePedido::create([
                    'codigo' => $pedido->codigo,
                    'producto_proveedor_id' => $p['producto_proveedor_id'],
                    'precio_unitario' => $precio,
                    'cantidad_solicitada' => $cantidad,
                    'subtotal' => $subtotal,
                ]);
            }

            // Actualizar total
            $pedido->update(['total' => $total]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado correctamente',
                'codigo' => $pedido->codigo,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al guardar pedido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===== Vista de previsualización del pedido =====
    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido');
    }

    // ===== Consultar pedidos registrados =====
    public function consultar()
    {
        $pedidos = Pedido::with('usuario')
            ->orderBy('fecha_solicitud', 'desc')
            ->get();

        return view('dashboard.consultar_pedidos', compact('pedidos'));
    }

    // ===== Visualizar pedido específico =====
    public function visualizar($id)
    {
        $pedido = Pedido::with(['usuario', 'detalles.producto'])->findOrFail($id);
        return view('dashboard.visualizar_pedido', compact('pedido'));
    }

    // ===== Mostrar detalle por código de pedido =====
    public function detalle($codigo)
    {
        $pedido = Pedido::with([
            'usuario',
            'detalles.productoProveedor.producto.categoria'
        ])->where('codigo', $codigo)->firstOrFail();

        return view('dashboard.detalle_pedido', compact('pedido'));
    }
}
