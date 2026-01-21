<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductoProveedor;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Categoria;
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

    private function estadoInicial(): string
    {
        return 'Pendiente';
    }

    // =====================
    // FORM CREAR PEDIDO
    // =====================
    public function crear(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id'); // id
        $categoriaId = $request->get('categoria_id'); // id

        $productosQuery = Producto::query()
            ->with([
                'categoria',
                'proveedores' => function ($q) {
                    $q->select('proveedores.id', 'nombre')
                      ->withPivot('id', 'precio');
                }
            ]);

        // 🔎 Buscador por nombre (producto)
        if ($q !== '') {
            $productosQuery->where('nombre', 'like', "%{$q}%");
        }

        // 🧩 Filtro por categoría
        if (!empty($categoriaId)) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        // 🏷️ Filtro por proveedor (many-to-many)
        if (!empty($proveedorId)) {
            $productosQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                $sub->where('proveedores.id', $proveedorId);
            });
        }

        // ✅ Solo 10 + paginación conservando filtros
        $productos = $productosQuery
            ->orderBy('nombre')
            ->paginate(10)
            ->appends($request->query());

        $proveedores = Proveedor::orderBy('nombre')->get();
        $categorias  = Categoria::orderBy('nombre')->get();

        return view('dashboard.crear_pedido', compact('productos', 'proveedores', 'categorias'));
    }

    public function solicitar(Request $request)
    {
        return $this->crear($request);
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
                'user_id'         => Auth::id() ?? 1,
                'total'           => 0,
                'estado'          => $this->estadoInicial(),
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
        $tipo = $request->get('tipo', 'todos');

        $query = Pedido::with('usuario');

        if (!$this->esAdmin() && !$this->esCEO()) {
            $query->where('user_id', Auth::id());
        }

        if ($tipo === 'normales') {
            $query->where('es_especial', 0);
        } elseif ($tipo === 'especiales') {
            $query->where('es_especial', 1);
        }

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

        if (!$this->esAdmin() && !$this->esCEO()) {
            abort_if(!$this->esSolicitante($pedido), 403, 'No tienes permiso para ver este pedido.');
        }

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

        $vista = $this->esAdmin()
            ? 'dashboard.editar_admin_pedido'
            : 'dashboard.editar_pedido';

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

        $ruta = $this->esAdmin()
            ? route('dashboard.pedidos.admin')
            : route('dashboard.pedidos.consultar');

        return redirect($ruta)->with('success', 'Pedido actualizado correctamente');
    }

    // ==================================================
    // ========= ACCIONES DE FLUJO POR ESTADO ============
    // ==================================================
    public function marcarVisto($codigo)
    {
        abort_unless($this->esAdmin(), 403);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        if ($pedido->estado === 'Pendiente') {
            $pedido->update(['estado' => 'Visto']);
        }

        return back()->with('success', 'Pedido marcado como visto.');
    }

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

    public function ceoAprobar($codigo)
    {
        abort_unless($this->esCEO(), 403);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes aprobar pedidos preaprobados.');

        $pedido->update(['estado' => 'Aprobado']);

        return redirect()->route('dashboard.pedidos.consultar')->with('success', 'Pedido aprobado.');
    }

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
            'observaciones_ceo' => $request->observacion,
        ]);

        return redirect()->route('dashboard.pedidos.consultar')->with('success', 'Pedido enviado a revisión.');
    }

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
            'observaciones_ceo' => $request->observacion,
        ]);

        return redirect()->route('dashboard.pedidos.consultar')->with('success', 'Pedido rechazado.');
    }
}
