<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

use App\Models\ProductoProveedor;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\UnidadOperativa;

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

    /**
     * Estados válidos para edición (admin).
     * OJO: tu sistema ya usa "En revision" (sin acento).
     */
    private function estadosEditablesAdmin(): array
    {
        return ['Pendiente', 'Visto', 'En revision'];
    }

    /**
     * Obtiene la unidad operativa para crear pedido:
     * - Admin/encargado_pedidos: la elige (viene del request JSON)
     * - Usuarios normales: viene del user->unidad_operativa_id
     */
    private function resolverUnidadOperativaId(?int $unidadSeleccionada): ?int
    {
        $user = Auth::user();
        if (!$user) return null;

        if ($this->esAdmin()) {
            return $unidadSeleccionada ?: null;
        }

        return $user->unidad_operativa_id ?: null;
    }

    // =====================
    // FORM CREAR PEDIDO
    // =====================
    public function crear(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id');
        $categoriaId = $request->get('categoria_id');

        $productosQuery = Producto::query()
            ->with([
                'categoria',
                'proveedores' => function ($q) {
                    $q->select('proveedores.id', 'nombre')
                      ->withPivot('id', 'precio');
                }
            ]);

        if ($q !== '') {
            $productosQuery->where('nombre', 'like', "%{$q}%");
        }

        if (!empty($categoriaId)) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        if (!empty($proveedorId)) {
            $productosQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                $sub->where('proveedores.id', $proveedorId);
            });
        }

        $productos = $productosQuery
            ->orderBy('nombre')
            ->paginate(10)
            ->appends($request->query());

        $proveedores = Proveedor::orderBy('nombre')->get();
        $categorias  = Categoria::orderBy('nombre')->get();

        // ✅ Para selector del admin
        $unidadesOperativas = $this->esAdmin()
            ? UnidadOperativa::orderBy('nombre')->get()
            : collect();

        return view('dashboard.crear_pedido', compact(
            'productos',
            'proveedores',
            'categorias',
            'unidadesOperativas'
        ));
    }

    public function solicitar(Request $request)
    {
        return $this->crear($request);
    }

    // =====================
    // PREVISUALIZACIÓN
    // =====================
    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido');
    }

    // =====================
    // GUARDAR PEDIDO (AJAX)
    // - Guarda unidad_operativa_id en pedidos
    // - Admin debe mandarla
    // - No-admin la toma del user
    // - Transacción + retry por colisión de código
    // =====================
    public function guardar(Request $request)
    {
        try {
            $data = $request->json()->all();
            Log::info("Datos recibidos desde el frontend:", $data);

            if (!$data || empty($data['productos']) || !is_array($data['productos'])) {
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

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado.'
                ], 401);
            }

            // ✅ unidad: admin selecciona, otros heredan
            $unidadSeleccionada = isset($data['unidad_operativa_id']) ? (int)$data['unidad_operativa_id'] : null;
            $unidadOperativaId = $this->resolverUnidadOperativaId($unidadSeleccionada);

            if (!$unidadOperativaId) {
                return response()->json([
                    'success' => false,
                    'message' => $this->esAdmin()
                        ? 'Debes seleccionar una unidad operativa para crear el pedido.'
                        : 'Tu usuario no tiene unidad operativa asignada. No se puede crear el pedido.'
                ], 422);
            }

            $pedido = null;

            DB::transaction(function () use (&$pedido, $data, $user, $unidadOperativaId) {

                // ✅ retry por colisión de código
                $intentos = 0;
                while (true) {
                    $intentos++;

                    try {
                        $pedido = Pedido::create([
                            'codigo'              => Pedido::generarCodigo(),
                            'fecha_solicitud'     => $data['fecha_solicitud'],
                            'fecha_entrega'       => $data['fecha_entrega'],
                            'estado'              => $this->estadoInicial(),
                            'user_id'             => $user->id,
                            'unidad_operativa_id' => $unidadOperativaId,
                            'total'               => 0,
                            'es_especial'         => 0,
                        ]);
                        break;
                    } catch (QueryException $e) {
                        $sqlState    = $e->errorInfo[0] ?? null;
                        $driverCode  = $e->errorInfo[1] ?? null;
                        $esDuplicado = ($sqlState === '23000' && (int)$driverCode === 1062);

                        if ($esDuplicado && $intentos < 5) {
                            continue;
                        }
                        throw $e;
                    }
                }

                $total = 0;

                foreach ($data['productos'] as $p) {

                    $ppId = $p['producto_proveedor_id'] ?? null;
                    if (!$ppId) continue;

                    $pp = ProductoProveedor::with(['producto', 'proveedor'])->find($ppId);
                    if (!$pp) {
                        Log::warning("ID inválido de producto_proveedor", $p);
                        continue;
                    }

                    $cantidad = isset($p['cantidad']) ? (float)$p['cantidad'] : 0;
                    $precio   = isset($p['precio']) ? (float)$p['precio'] : (float)($pp->precio ?? 0);

                    if ($cantidad < 0) $cantidad = 0;
                    if ($precio < 0) $precio = 0;

                    $subtotal = $cantidad * $precio;
                    $total += $subtotal;

                    DetallePedido::create([
                        'codigo'                => $pedido->codigo,
                        'producto_proveedor_id' => $pp->id,
                        'cantidad_solicitada'   => $cantidad,
                        'cantidad_aprobada'     => $cantidad,
                        'precio_unitario'       => $precio,
                        'subtotal'              => $subtotal,
                        'activo'                => 1,
                    ]);
                }

                $pedido->update(['total' => $total]);
            });

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
    // CONSULTAR PEDIDOS
    // =====================
    public function consultar(Request $request)
    {
        $tipo = $request->get('tipo', 'todos');

        $query = Pedido::with(['usuario', 'unidadOperativa']);

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
            'unidadOperativa',
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
    // - Enviamos detalle_id para poder actualizar sin borrar
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
            abort_if(!in_array($pedido->estado, $this->estadosEditablesAdmin()), 403, 'Este pedido ya no se puede editar en este estado.');
        }

        $productos = Producto::with('categoria', 'proveedores')->get();

        $itemsPedido = [];
        foreach ($pedido->detalles as $d) {
            $pp = $d->productoProveedor;
            if (!$pp) continue;

            $prod = $pp->producto;
            $prov = $pp->proveedor;

            $sol = (float) ($d->cantidad_solicitada ?? 0);
            $apr = ($d->cantidad_aprobada === null || $d->cantidad_aprobada === '')
                ? $sol
                : (float) $d->cantidad_aprobada;

            $activo = (int) ($d->activo ?? 1);
            $precio = (float) ($d->precio_unitario ?? 0);

            $itemsPedido[] = [
                // ✅ CLAVE para update sin borrar
                'detalle_id'           => $d->id,

                'producto_proveedor_id' => $pp->id,
                'producto_id'           => $prod?->id,
                'proveedor_id'          => $prov?->id,

                'proveedor'             => $prov?->nombre ?? '',
                'nombre'                => $prod?->nombre ?? '',
                'marca'                 => $prod?->marca ?? '',
                'categoria'             => $prod?->categoria?->nombre ?? '',
                'unidad'                => $prod?->unidad_medida ?? '',

                'cantidad_solicitada'   => $sol,
                'cantidad_aprobada'     => $apr,
                'activo'                => $activo,

                'precio'                => $precio,
                'subtotal'              => $activo === 1 ? ($apr * $precio) : 0,

                'is_new'                => 0,
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
    // ACTUALIZAR PEDIDO (SIN BORRAR)
    // - Usa detalle_id para actualizar existentes
    // - Crea nuevos si no hay detalle_id
    // - Los que NO vienen => activo=0
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
            abort_if(!in_array($pedido->estado, $this->estadosEditablesAdmin()), 403, 'Este pedido ya no se puede editar en este estado.');
        }

        $items = $request->items_json ? json_decode($request->items_json, true) : [];
        if (!is_array($items) || count($items) === 0) {
            return back()->with('error', 'Debe agregar al menos un producto.');
        }

        DB::transaction(function () use ($pedido, $codigo, $items) {

            // detalle_ids que vienen del front (solo existentes)
            $detalleIdsPresentes = collect($items)
                ->pluck('detalle_id')
                ->filter()
                ->map(fn($v) => (int)$v)
                ->unique()
                ->values()
                ->all();

            // Inactivar los existentes que ya no vienen
            if (!empty($detalleIdsPresentes)) {
                DetallePedido::where('codigo', $codigo)
                    ->whereNotIn('id', $detalleIdsPresentes)
                    ->update(['activo' => 0]);
            } else {
                // si no viene ninguno, NO apagues todo (porque quizá todo es "nuevo" en front)
                // en ese caso, no hacemos inactivación masiva aquí.
            }

            $total = 0;

            foreach ($items as $it) {

                $ppId = $it['producto_proveedor_id'] ?? null;
                if (!$ppId) continue;

                $detalleId = isset($it['detalle_id']) ? (int)$it['detalle_id'] : null;

                $activo = isset($it['activo'])
                    ? (int)$it['activo']
                    : 1;

                $cantSol = array_key_exists('cantidad_solicitada', $it) ? (float)$it['cantidad_solicitada'] : null;
                $cantApr = array_key_exists('cantidad_aprobada', $it) ? (float)$it['cantidad_aprobada'] : null;

                // compat si el front manda "cantidad"
                if ($cantApr === null && isset($it['cantidad'])) {
                    $cantApr = (float)$it['cantidad'];
                }

                $precio = isset($it['precio']) ? (float)$it['precio'] : 0;

                // 1) si existe detalle_id => actualiza ese registro
                if ($detalleId) {
                    $detalle = DetallePedido::where('codigo', $codigo)->where('id', $detalleId)->first();
                    if (!$detalle) {
                        // si por alguna razón no existe, cae a creación
                        $detalleId = null;
                    } else {
                        // si cambiaron proveedor/producto-proveedor
                        $detalle->producto_proveedor_id = $ppId;

                        if ($cantSol !== null) $detalle->cantidad_solicitada = $cantSol;

                        $detalle->precio_unitario   = $precio;
                        $detalle->cantidad_aprobada = $cantApr ?? ($detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada);
                        $detalle->activo            = $activo;

                        $detalle->subtotal = ($detalle->activo == 1)
                            ? ((float)$detalle->cantidad_aprobada * (float)$detalle->precio_unitario)
                            : 0;

                        $detalle->save();

                        if ($detalle->activo == 1) {
                            $total += (float)$detalle->subtotal;
                        }

                        continue;
                    }
                }

                // 2) si no hay detalle_id => crea nuevo detalle (sin borrar)
                $nuevo = new DetallePedido();
                $nuevo->codigo = $codigo;
                $nuevo->producto_proveedor_id = $ppId;
                $nuevo->cantidad_solicitada = $cantSol ?? ($cantApr ?? 0);
                $nuevo->cantidad_aprobada   = $cantApr ?? $nuevo->cantidad_solicitada;
                $nuevo->precio_unitario     = $precio;
                $nuevo->activo              = $activo;

                $nuevo->subtotal = ($nuevo->activo == 1)
                    ? ((float)$nuevo->cantidad_aprobada * (float)$nuevo->precio_unitario)
                    : 0;

                $nuevo->save();

                if ($nuevo->activo == 1) {
                    $total += (float)$nuevo->subtotal;
                }
            }

            $pedido->total = $total;
            $pedido->save();
        });

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

        abort_if(!in_array($pedido->estado, ['Visto', 'En revision']), 403, 'Solo puedes preaprobar pedidos vistos o en revisión.');

        $pedido->update([
            'estado'          => 'Preaprobado',
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
            'estado'            => 'En revision',
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
            'estado'            => 'Rechazado',
            'observaciones_ceo' => $request->observacion,
        ]);

        return redirect()->route('dashboard.pedidos.consultar')->with('success', 'Pedido rechazado.');
    }
}
