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
    // ==========================
    // Helpers de permisos/estado
    // ==========================
    private function role(): string
    {
        return Auth::user()->role ?? '';
    }

    private function esAdmin(): bool
    {
        return in_array($this->role(), ['admin', 'encargado_pedidos']);
    }

    private function esCEO(): bool
    {
        return $this->role() === 'ceo';
    }

    private function esSolicitante(Pedido $pedido): bool
    {
        return (int)$pedido->user_id === (int)Auth::id();
    }

    /**
     * Estados compatibles con tu base actual (dejas "Pendiente" inicial).
     */
    private function estadoInicial(): string
    {
        return 'Pendiente'; // lo dejas así por compatibilidad
    }

    // =====================
    // FORM CREAR PEDIDO
    // =====================
    public function crear()
    {
        $productos = Producto::with([
            'categoria',
            'proveedores' => function ($q) {
                $q->select('proveedores.id', 'nombre')
                    ->withPivot('id', 'precio');
            }
        ])->get();

        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('dashboard.crear_pedido', compact('productos', 'proveedores'));
    }

    public function solicitar()
    {
        return $this->crear();
    }

    // =====================
    // GUARDAR PEDIDO (AJAX)
    // =====================
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

            $pedido = Pedido::create([
                'codigo'          => 'dec' . date("md") . rand(1000, 9999),
                'fecha_solicitud' => $data['fecha_solicitud'],
                'fecha_entrega'   => $data['fecha_entrega'],
                'user_id'         => Auth::id() ?? 1, // solicitante
                'total'           => 0,
                'estado'          => $this->estadoInicial(), // "Pendiente"
                // 'es_especial'   => 0, // si lo manejas aquí, define por defecto
            ]);

            $total = 0;

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
                    'codigo'                => $pedido->codigo,
                    'producto_proveedor_id' => $pp->id,
                    'cantidad_solicitada'   => $cantidad,
                    'precio_unitario'       => $precio,
                    'subtotal'              => $subtotal,
                ]);
            }

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
    // PREVISUALIZACIÓN
    // =====================
    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido');
    }

    // =====================
    // CONSULTAR PEDIDOS
    // =====================
    public function consultar(Request $request)
    {
        $tipo = $request->get('tipo', 'todos'); // todos, normales, especiales

        $query = Pedido::with('usuario');

        // Encargados / no admin / no ceo: solo sus pedidos
        if (!$this->esAdmin() && !$this->esCEO()) {
            $query->where('user_id', Auth::id());
        }

        if ($tipo === 'normales') {
            $query->where('es_especial', 0);
        } elseif ($tipo === 'especiales') {
            $query->where('es_especial', 1);
        }

        // CEO: solo ve preaprobados
        if ($this->esCEO()) {
            $query->where('estado', 'Preaprobado');
        }

        $pedidos = $query->orderBy('fecha_solicitud', 'desc')->get();

        return view('dashboard.consultar_pedidos', compact('pedidos', 'tipo'));
    }

    // =====================
    // DETALLE DEL PEDIDO
    // =====================
    public function detalle($codigo)
    {
        $pedido = Pedido::with([
            'usuario',
            'detalles.productoProveedor.producto.categoria',
            'detalles.productoProveedor.proveedor'
        ])->where('codigo', $codigo)->firstOrFail();

        // Encargado: solo puede ver los suyos
        if (!$this->esAdmin() && !$this->esCEO()) {
            abort_if(!$this->esSolicitante($pedido), 403, 'No tienes permiso para ver este pedido.');
        }

        // CEO: solo preaprobados (o los aprobados si luego quieres)
        if ($this->esCEO()) {
            abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes ver pedidos preaprobados.');
        }

        return view('dashboard.detalle_pedido', compact('pedido'));
    }

    // =====================
    // EDITAR PEDIDO
    // =====================
    public function editar($codigo)
    {
        $pedido = Pedido::with([
            'detalles.productoProveedor.producto.categoria',
            'detalles.productoProveedor.proveedor'
        ])->where('codigo', $codigo)->firstOrFail();

        // ✅ Reglas:
        // - Encargado/cafetería/cocina: solo si es suyo y estado = Pendiente
        // - Admin: puede si Pendiente / Visto / En revisión
        // - CEO: no edita
        if ($this->esCEO()) {
            abort(403, 'El CEO no edita pedidos.');
        }

        if (!$this->esAdmin()) {
            abort_if(!$this->esSolicitante($pedido), 403, 'No tienes permiso para editar este pedido.');
            abort_if($pedido->estado !== 'Pendiente', 403, 'Solo puedes editar pedidos pendientes.');
        } else {
            abort_if(!in_array($pedido->estado, ['Pendiente', 'Visto', 'En revisión']), 403, 'Este pedido ya no se puede editar en este estado.');
        }

        $productos = Producto::with('categoria', 'proveedores')->get();

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

        // ✅ Si es admin usa tu vista admin, si no, la del solicitante (si tienes)
        $vista = $this->esAdmin()
            ? 'dashboard.editar_admin_pedido'
            : 'dashboard.editar_pedido'; // si no existe, cámbiala a la que uses

        return view($vista, [
            'pedido'      => $pedido,
            'productos'   => $productos,
            'itemsPedido' => $itemsPedido
        ]);
    }

    // =====================
    // ACTUALIZAR PEDIDO
    // =====================
    public function actualizar(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        if ($this->esCEO()) {
            abort(403, 'El CEO no edita pedidos.');
        }

        // Permisos iguales que editar
        if (!$this->esAdmin()) {
            abort_if(!$this->esSolicitante($pedido), 403, 'No tienes permiso para editar este pedido.');
            abort_if($pedido->estado !== 'Pendiente', 403, 'Solo puedes editar pedidos pendientes.');
        } else {
            abort_if(!in_array($pedido->estado, ['Pendiente', 'Visto', 'En revisión']), 403, 'Este pedido ya no se puede editar en este estado.');
        }

        $items  = json_decode($request->items_json, true);

        if (!$items || count($items) === 0) {
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
                'codigo'                => $codigo,
                'producto_proveedor_id' => $it['producto_proveedor_id'],
                'cantidad_solicitada'   => $cantidad,
                'precio_unitario'       => $precio,
                'subtotal'              => $subtotal,
            ]);
        }

        $pedido->update(['total' => $total]);

        // Redirect según rol
        $ruta = $this->esAdmin()
            ? route('dashboard.pedidos.admin')
            : route('dashboard.pedidos.consultar');

        return redirect($ruta)->with('success', 'Pedido actualizado correctamente');
    }

    // ==================================================
    // ========= ACCIONES DE FLUJO POR ESTADO ============
    // ==================================================

    /**
     * Admin: marcar como visto (solo si está Pendiente).
     */
    public function marcarVisto($codigo)
    {
        abort_unless($this->esAdmin(), 403);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        if ($pedido->estado === 'Pendiente') {
            $pedido->update(['estado' => 'Visto']);
        }

        return back()->with('success', 'Pedido marcado como visto.');
    }

    /**
     * Admin: preaprobar (solo si Visto o En revisión).
     * Guarda quién lo preaprueba.
     */
    public function preaprobar($codigo)
    {
        abort_unless($this->esAdmin(), 403);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        abort_if(!in_array($pedido->estado, ['Visto', 'En revisión']), 403, 'Solo puedes preaprobar pedidos vistos o en revisión.');

        $pedido->update([
            'estado' => 'Preaprobado',
            'preaprobado_por' => Auth::id(),
        ]);

        return redirect()->route('dashboard.pedidos.admin')->with('success', 'Pedido preaprobado y enviado al CEO.');
    }

    /**
     * CEO: aprobar (solo si Preaprobado).
     */
    public function ceoAprobar($codigo)
    {
        abort_unless($this->esCEO(), 403);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes aprobar pedidos preaprobados.');

        $pedido->update(['estado' => 'Aprobado']);

        return redirect()->route('dashboard.pedidos.consultar')->with('success', 'Pedido aprobado.');
    }

    /**
     * CEO: mandar a revisión (solo si Preaprobado). Guarda observación.
     */
    public function ceoEnviarRevision(Request $request, $codigo)
    {
        abort_unless($this->esCEO(), 403);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes enviar a revisión pedidos preaprobados.');

        $request->validate([
            'observacion' => 'required|string|min:3',
        ]);

        $pedido->update([
            'estado' => 'En revisión',
            'observaciones_ceo' => $request->observacion, // si no tienes campo, quita esto
        ]);

        return redirect()->route('dashboard.pedidos.consultar')->with('success', 'Pedido enviado a revisión.');
    }

    /**
     * CEO: rechazar (solo si Preaprobado). Guarda observación.
     */
    public function ceoRechazar(Request $request, $codigo)
    {
        abort_unless($this->esCEO(), 403);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes rechazar pedidos preaprobados.');

        $request->validate([
            'observacion' => 'required|string|min:3',
        ]);

        $pedido->update([
            'estado' => 'Rechazado',
            'observaciones_ceo' => $request->observacion, // si no tienes campo, quita esto
        ]);

        return redirect()->route('dashboard.pedidos.consultar')->with('success', 'Pedido rechazado.');
    }

    
}
