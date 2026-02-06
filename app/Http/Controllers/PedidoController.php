<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\UnidadOperativa;

// ✅ NUEVO (presentaciones)
use App\Models\ProductoPresentacion;
use App\Models\PresentacionProveedor;

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
        return in_array($this->role(), ['admin', 'encargado_pedidos'], true);
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

    private function estadosEditablesAdmin(): array
    {
        return ['Pendiente', 'Visto', 'En revision'];
    }

    private function resolverUnidadOperativaId(?int $unidadSeleccionada): ?int
    {
        $user = Auth::user();
        if (!$user) return null;

        if ($this->esAdmin()) {
            return $unidadSeleccionada ?: null;
        }

        return $user->unidad_operativa_id ?: null;
    }

    /**
     * ✅ PresentaciónProveedor "principal" para una presentación
     * Puedes cambiar el orderBy para preferidos/fechas/activos.
     */
    private function resolverPresentacionProveedorPrincipal(int $presentacionId): ?PresentacionProveedor
    {
        return PresentacionProveedor::query()
            ->where('presentacion_id', $presentacionId)
            ->where(function ($q) {
                $q->where('estado', 1)
                  ->orWhere('estado', 'Activo')
                  ->orWhere('estado', 'ACTIVO');
            })
            // si manejas vigencias, puedes usar:
            // ->orderByDesc('fecha_vigencia_inicio')
            ->orderByDesc('id')
            ->first();
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function sendTelegramPedidoNotification(Pedido $pedido, string $tipoLabel): void
    {
        $botToken = (string) config('services.telegram.bot_token', '');
        $chatId = (string) config('services.telegram.chat_id', '');

        if ($botToken === '' || $chatId === '') {
            Log::warning('Telegram notification skipped: missing bot token or chat id.');
            return;
        }

        $pedido->loadMissing(['detalles.presentacion.producto', 'detalles.presentacion.proveedores.proveedor']);

        $mensaje = "PEDIDO: {$pedido->codigo}\n";

        $porProveedor = $pedido->detalles->groupBy(function ($det) {
            $presentacion = $det->presentacion;
            $prov = $presentacion?->proveedores?->first()?->proveedor ?? null;
            return $prov?->nombre ?? 'Sin proveedor';
        });

        foreach ($porProveedor as $provNombre => $detalles) {
            $mensaje .= "\n{$provNombre}\n";

            $porPresentacion = $detalles->groupBy('presentacion_id');
            foreach ($porPresentacion as $grupo) {
                $detalle = $grupo->first();
                $presentacion = $detalle?->presentacion;
                $productoNombre = $presentacion?->producto?->nombre ?? 'Producto';
                $presentacionDesc = trim((string) ($presentacion?->descripcion ?? ''));
                $unidadContenido = trim((string) ($presentacion?->unidad_contenido ?? ''));
                $cantidad = (float) $grupo->sum('cantidad_solicitada');

                $partes = [];
                $partes[] = rtrim(rtrim(number_format($cantidad, 2), '0'), '.');
                if ($unidadContenido !== '') $partes[] = $unidadContenido;
                $partes[] = $productoNombre;
                if ($presentacionDesc !== '') $partes[] = $presentacionDesc;

                $mensaje .= '- ' . trim(implode(' ', $partes)) . "\n";
            }
        }

        try {
            $response = Http::timeout(6)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $mensaje,
                ]
            );

            if (!$response->ok()) {
                Log::warning('Telegram notification failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram notification error.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =====================
    // FORM CREAR PEDIDO
    // =====================
    public function crear(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id');
        $categoriaId = $request->get('categoria_id');

        // excluir categoría especial (si así lo manejas)
        $catEspecialId = Categoria::whereRaw('LOWER(nombre) LIKE ?', ['%especial%'])
            ->value('id');

        // ✅ Traemos PRESENTACIONES (no productos)
        $presQuery = ProductoPresentacion::query()
            ->where(function ($q) {
                $q->where('estado', 1)
                  ->orWhere('estado', 'Activo')
                  ->orWhere('estado', 'ACTIVO');
            })
            ->whereHas('producto', function ($qProd) use ($catEspecialId, $categoriaId, $q) {
                $qProd->where(function ($q2) {
                    $q2->where('estado', 1)
                        ->orWhere('estado', 'Activo')
                        ->orWhere('estado', 'ACTIVO');
                });

                if ($catEspecialId) {
                    $qProd->where('categoria_id', '!=', $catEspecialId);
                }

                if (!empty($categoriaId)) {
                    $qProd->where('categoria_id', $categoriaId);
                }

                if ($q !== '') {
                    $qProd->where('nombre', 'like', "%{$q}%");
                }
            })
            ->with([
                'producto.categoria',
                // ✅ precio por proveedor de presentación
                'proveedores' => function ($q) {
                    $q->with(['proveedor:id,nombre'])
                    ->select('id','presentacion_id','proveedor_id','precio_vigente','estado')
                    ->where('estado', 1)
                    ->orderByDesc('id');
                }

            ]);

        if (!empty($proveedorId)) {
            $presQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                $sub->where('proveedor_id', $proveedorId);
            });
        }

        $presentaciones = $presQuery
            ->orderBy('producto_id')
            ->orderBy('descripcion')
            ->paginate(10)
            ->appends($request->query());

        // ✅ default de proveedor/precio para el front
        $presentaciones->getCollection()->transform(function ($pres) {
            $primero = $pres->proveedores->first(); // pivot(id,precio)
            $pres->pp_default_id = $primero?->pivot?->id; // id de presentacion_proveedor
            $pres->pp_default_precio = (float)($primero?->pivot?->precio ?? 0);
            $pres->producto_nombre = $pres->producto->nombre ?? '';
            $pres->categoria_nombre = $pres->producto->categoria->nombre ?? '';
            return $pres;
        });

        $proveedores = Proveedor::orderBy('nombre')->get();
        $categorias = Categoria::query()
            ->where('estado', 'Activo')
            ->whereIn('id', function ($sub) use ($catEspecialId) {
                $sub->select('productos.categoria_id')
                    ->from('productos')
                    ->join('producto_presentaciones', 'producto_presentaciones.producto_id', '=', 'productos.id')
                    ->whereNotNull('productos.categoria_id')
                    ->where(function ($q) {
                        $q->where('productos.estado', 1)
                            ->orWhere('productos.estado', 'Activo')
                            ->orWhere('productos.estado', 'ACTIVO');
                    })
                    ->where(function ($q) {
                        $q->where('producto_presentaciones.estado', 1)
                            ->orWhere('producto_presentaciones.estado', 'Activo')
                            ->orWhere('producto_presentaciones.estado', 'ACTIVO');
                    })
                    ->when($catEspecialId, function ($q) use ($catEspecialId) {
                        $q->where('productos.categoria_id', '!=', $catEspecialId);
                    })
                    ->distinct();
            })
            ->orderBy('nombre')
            ->get();

        $unidadesOperativas = $this->esAdmin()
            ? UnidadOperativa::orderBy('nombre')->get()
            : collect();

        // ⚠️ Cambia tu vista para consumir $presentaciones en lugar de $productos
        return view('dashboard.crear_pedido', compact(
            'presentaciones',
            'proveedores',
            'categorias',
            'unidadesOperativas'
        ));
    }

    public function solicitar(Request $request)
    {
        return $this->crear($request);
    }

    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido');
    }

    // =====================
    // GUARDAR PEDIDO (AJAX)
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

            $unidadSeleccionada = isset($data['unidad_operativa_id']) ? (int)$data['unidad_operativa_id'] : null;
            $unidadOperativaId  = $this->resolverUnidadOperativaId($unidadSeleccionada);

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
                        if ($esDuplicado && $intentos < 5) continue;
                        throw $e;
                    }
                }

                $total = 0;

                foreach ($data['productos'] as $p) {

                    // ✅ ahora el frontend debe mandar presentacion_id
                    $presentacionId = isset($p['presentacion_id']) ? (int)$p['presentacion_id'] : null;
                    if (!$presentacionId) continue;

                    $presentacion = ProductoPresentacion::with('producto')->find($presentacionId);
                    if (!$presentacion) {
                        Log::warning("presentacion_id inválida", $p);
                        continue;
                    }

                    // ✅ resolver precio vigente por presentación
                    $pp = $this->resolverPresentacionProveedorPrincipal($presentacionId);
                    $precio = isset($p['precio']) ? (float)$p['precio'] : (float)($pp->precio ?? 0);

                    // 🔒 Si NO es admin, ignoramos cualquier intento de forzar precio
                    if (!$this->esAdmin()) {
                        $precio = (float)($pp->precio ?? 0);
                    }

                    $cantidad = isset($p['cantidad']) ? (float)$p['cantidad'] : 0;
                    if ($cantidad < 0) $cantidad = 0;
                    if ($precio < 0) $precio = 0;

                    if ($cantidad <= 0) continue;

                    $subtotal = $cantidad * $precio;
                    $total += $subtotal;

                    DetallePedido::create([
                        'codigo'          => $pedido->codigo,

                        // ✅ NUEVO: el detalle se amarra a presentación
                        'presentacion_id' => $presentacionId,

                        // legacy (si tu columna no permite null, me dices)
                        'producto_proveedor_id' => null,

                        'cantidad_solicitada' => $cantidad,
                        'cantidad_aprobada'   => $cantidad,
                        'precio_unitario'     => $precio,
                        'subtotal'            => $subtotal,
                        'activo'              => 1,
                    ]);
                }

                $pedido->update(['total' => $total]);
            });

            if ($pedido) {
                $this->sendTelegramPedidoNotification($pedido, 'NORMAL');
            }

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

        $hoy   = now()->toDateString();
        $desde = $request->get('desde', now()->subDays(7)->toDateString());
        $hasta = $request->get('hasta', $hoy);

        $codigo = trim((string) $request->get('codigo', ''));

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

        $query->whereDate('fecha_solicitud', '>=', $desde)
              ->whereDate('fecha_solicitud', '<=', $hasta);

        if ($codigo !== '') {
            $query->where('codigo', 'like', "%{$codigo}%");
        }

        $pedidos = $query
            ->orderBy('fecha_solicitud', 'desc')
            ->paginate(10)
            ->appends($request->query());

        return view('dashboard.consultar_pedidos', compact('pedidos', 'tipo', 'desde', 'hasta', 'codigo'));
    }

    // =====================
    // DETALLE DEL PEDIDO
    // =====================
    public function detalle($codigo)
    {
        $pedido = Pedido::with([
            'usuario',
            'unidadOperativa',
            // ✅ ahora: detalle -> presentación -> producto -> categoria
            'detalles.presentacion.producto.categoria',
            // si necesitas ver proveedores en la vista:
            'detalles.presentacion.proveedores',
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
    // DETALLE INFORMATIVO
    // =====================
    public function detalleInformativo($codigo)
    {
        $pedido = Pedido::with([
            'usuario',
            'unidadOperativa',
            'detalles.presentacion.producto.categoria',
            'detalles.presentacion.proveedores',
        ])->where('codigo', $codigo)->firstOrFail();

        if (!$this->esAdmin() && !$this->esCEO()) {
            abort_if(!$this->esSolicitante($pedido), 403, 'No tienes permiso para ver este pedido.');
        }

        if ($this->esCEO()) {
            abort_if($pedido->estado !== 'Preaprobado', 403, 'Solo puedes ver pedidos preaprobados.');
        }

        return view('dashboard.detalle_pedido_informativo', compact('pedido'));
    }

    // =====================
    // EDITAR PEDIDO
    // =====================
    public function editar($codigo)
    {
        $pedido = Pedido::with([
            'detalles.presentacion.producto.categoria',
            'detalles.presentacion.proveedores',
        ])->where('codigo', $codigo)->firstOrFail();

        if ($this->esCEO()) {
            abort(403, 'El CEO no edita pedidos.');
        }

        if (!$this->esAdmin()) {
            abort_if(!$this->esSolicitante($pedido), 403, 'No tienes permiso para editar este pedido.');
            abort_if($pedido->estado !== 'Pendiente', 403, 'Solo puedes editar pedidos pendientes.');
        } else {
            abort_if(!in_array($pedido->estado, $this->estadosEditablesAdmin(), true), 403, 'Este pedido ya no se puede editar en este estado.');
        }

        // ✅ catálogo: presentaciones + proveedores/precio
        $presentaciones = ProductoPresentacion::with([
            'producto.categoria',
            'proveedores' => function ($q) {
                $q->select('proveedores.id', 'nombre')
                  ->withPivot('id', 'precio_vigente', 'estado');
            }
        ])->get();

        $itemsPedido = [];
        foreach ($pedido->detalles as $d) {

            $pres = $d->presentacion;
            if (!$pres) continue;

            $prod = $pres->producto;

            $sol = (float) ($d->cantidad_solicitada ?? 0);
            $apr = ($d->cantidad_aprobada === null || $d->cantidad_aprobada === '')
                ? $sol
                : (float) $d->cantidad_aprobada;

            $activo = (int) ($d->activo ?? 1);
            $precio = (float) ($d->precio_unitario ?? 0);

            $itemsPedido[] = [
                'detalle_id'      => $d->id,
                'presentacion_id' => $pres->id,

                'producto_id'     => $prod?->id,
                'proveedor_id'    => null, // si luego quieres mostrar el proveedor “principal”, lo resolvemos

                'producto'        => $prod?->nombre ?? '',
                'presentacion'    => $pres->descripcion ?? '',
                'categoria'       => $prod?->categoria?->nombre ?? '',

                'unidad'          => $pres->unidad_base ?? ($prod?->unidad_medida ?? ''),
                'contenido'       => $pres->unidad_contenido ?? null,

                'cantidad_solicitada' => $sol,
                'cantidad_aprobada'   => $apr,
                'activo'              => $activo,

                'precio'          => $precio,
                'subtotal'        => $activo === 1 ? ($apr * $precio) : 0,
                'is_new'          => 0,
            ];
        }

        $vista = $this->esAdmin()
            ? 'dashboard.editar_admin_pedido'
            : 'dashboard.editar_pedido';

        return view($vista, [
            'pedido'         => $pedido,
            'presentaciones' => $presentaciones,
            'itemsPedido'    => $itemsPedido
        ]);
    }

    // =====================
    // ACTUALIZAR PEDIDO (SIN BORRAR)
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
            abort_if(!in_array($pedido->estado, $this->estadosEditablesAdmin(), true), 403, 'Este pedido ya no se puede editar en este estado.');
        }

        $items = $request->items_json ? json_decode($request->items_json, true) : [];
        if (!is_array($items) || count($items) === 0) {
            return back()->with('error', 'Debe agregar al menos un producto.');
        }

        DB::transaction(function () use ($pedido, $codigo, $items) {

            $detalleIdsPresentes = collect($items)
                ->pluck('detalle_id')
                ->filter()
                ->map(fn($v) => (int)$v)
                ->unique()
                ->values()
                ->all();

            if (!empty($detalleIdsPresentes)) {
                DetallePedido::where('codigo', $codigo)
                    ->whereNotIn('id', $detalleIdsPresentes)
                    ->update(['activo' => 0]);
            }

            $total = 0;

            foreach ($items as $it) {

                $presentacionId = isset($it['presentacion_id']) ? (int)$it['presentacion_id'] : null;
                if (!$presentacionId) continue;

                $detalleId = isset($it['detalle_id']) ? (int)$it['detalle_id'] : null;

                $activo = isset($it['activo']) ? (int)$it['activo'] : 1;

                $cantSol = array_key_exists('cantidad_solicitada', $it) ? (float)$it['cantidad_solicitada'] : null;
                $cantApr = array_key_exists('cantidad_aprobada', $it) ? (float)$it['cantidad_aprobada'] : null;

                if ($cantApr === null && isset($it['cantidad'])) {
                    $cantApr = (float)$it['cantidad'];
                }

                // ✅ precio: admin puede mandar el del front, no-admin se fuerza
                $pp = $this->resolverPresentacionProveedorPrincipal($presentacionId);
                $precio = isset($it['precio']) ? (float)$it['precio'] : (float)($pp->precio ?? 0);
                if (!$this->esAdmin()) {
                    $precio = (float)($pp->precio ?? 0);
                }

                // 1) actualizar existente
                if ($detalleId) {
                    $detalle = DetallePedido::where('codigo', $codigo)->where('id', $detalleId)->first();
                    if (!$detalle) {
                        $detalleId = null;
                    } else {

                        $detalle->presentacion_id = $presentacionId;
                        $detalle->producto_proveedor_id = null; // legacy ya no

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

                // 2) crear nuevo detalle
                $nuevo = new DetallePedido();
                $nuevo->codigo = $codigo;

                $nuevo->presentacion_id = $presentacionId;
                $nuevo->producto_proveedor_id = null;

                $nuevo->cantidad_solicitada = $cantSol ?? ($cantApr ?? 0);
                $nuevo->cantidad_aprobada   = $cantApr ?? $nuevo->cantidad_solicitada;

                $nuevo->precio_unitario = $precio;
                $nuevo->activo = $activo;

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

        abort_if(!in_array($pedido->estado, ['Visto', 'En revision'], true), 403,
            'Solo puedes preaprobar pedidos vistos o en revisión.'
        );

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

    private function presentacionesPorTipo(string $tipo)
    {
        $categoriaId = $this->tipoToCategoriaId($tipo);

        return ProductoPresentacion::query()
            ->where('estado', 1)
            ->whereHas('producto', function ($q) use ($categoriaId) {
                $q->where('categoria_id', $categoriaId)
                  ->where(function ($q2) {
                      $q2->where('estado', 1)
                         ->orWhere('estado', 'Activo')
                         ->orWhere('estado', 'ACTIVO');
                  });
            })
            ->with([
                'producto:id,nombre,categoria_id,unidad_medida',
                'proveedores' => function ($q) {
                    $q->where('estado', 1)
                      ->orderByDesc('id');
                },
                'proveedores.proveedor:id,nombre',
            ])
            ->orderBy('producto_id')
            ->orderBy('descripcion')
            ->get()
            ->map(function ($pres) {
                // quedarte con 1 proveedor (precio vigente)
                if ($pres->relationLoaded('proveedores')) {
                    $pres->setRelation(
                        'proveedores',
                        $pres->proveedores->take(1)
                    );
                }
                return $pres;
            });
    }
}
