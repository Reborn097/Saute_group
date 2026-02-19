<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

use App\Models\PedidoEspecial;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\ProductoPresentacion;
use App\Models\PresentacionProveedor;
use App\Models\Proveedor;
use App\Models\Categoria;
use App\Models\UnidadOperativa;

class PedidoEspecialController extends Controller
{
    // ==========================
    // Helpers
    // ==========================
    private function role(): string
    {
        return Auth::user()->role ?? '';
    }

    private function esAdminPedidos(): bool
    {
        return in_array($this->role(), ['admin', 'encargado_pedidos'], true);
    }

    private function esResponsableDeUnidades(): bool
    {
        return $this->role() === \App\Models\User::ROLE_RESPONSABLE_UNIDADES;
    }

    private function estadoInicial(): string
    {
        return 'Pendiente';
    }

    /**
     * ✅ VER PDFs:
     * - Todos menos proveedor (por ahora)
     */
    private function puedeVerPDFs(): bool
    {
        return $this->role() !== 'proveedor';
    }

    /**
     * ✅ EDITAR/REEMPLAZAR PDFs por estado + rol
     * Regla:
     * - encargado_cocina / encargado_cafeteria: solo Pendiente
     * - admin / encargado_pedidos: Pendiente o Visto
     * - otros: no
     *
     * Estados válidos: Pendiente, Visto, Preaprobado, Aprobado, Cancelado
     */
    private function puedeEditarPDFs(string $estado): bool
    {
        $role = $this->role();

        // Estados finales -> nadie
        if (in_array($estado, ['Preaprobado', 'Aprobado', 'Cancelado'], true)) {
            return false;
        }

        if ($estado === 'Pendiente') {
            return in_array($role, ['admin', 'encargado_pedidos', 'encargado_cocina', 'encargado_cafeteria', 'responsable_de_unidades'], true);
        }

        if ($estado === 'Visto') {
            return in_array($role, ['admin', 'encargado_pedidos'], true);
        }

        return false;
    }

    /**
     * ✅ Proveedor principal para un producto (para NO-admin)
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
            ->whereHas('proveedor', fn($q) => $q->where('estado', 1))
            ->orderByDesc('id')
            ->first();
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function sendTelegramPedidoNotification(Pedido $pedido): void
    {
        $botToken = (string) config('services.telegram.bot_token', '');
        $chatId = (string) config('services.telegram.chat_id', '');

        if ($botToken === '' || $chatId === '') {
            Log::warning('Telegram notification skipped: missing bot token or chat id.');
            return;
        }

        $pedido->loadMissing(['unidadOperativa', 'usuario', 'detalles.presentacion.producto']);

        $unidad = $pedido->unidadOperativa->nombre ?? 'N/D';
        $usuario = $pedido->usuario->name ?? 'N/D';
        $total = (float) ($pedido->total ?? 0);

        $fechaSolicitud = $pedido->fecha_solicitud instanceof \Carbon\Carbon
            ? $pedido->fecha_solicitud->toDateString()
            : (string) $pedido->fecha_solicitud;
        $fechaEntrega = $pedido->fecha_entrega instanceof \Carbon\Carbon
            ? $pedido->fecha_entrega->toDateString()
            : (string) $pedido->fecha_entrega;

        $lineas = [];
        $porPresentacion = $pedido->detalles->groupBy('presentacion_id');
        foreach ($porPresentacion as $detalles) {
            $detalle = $detalles->first();
            $presentacion = $detalle?->presentacion;
            $productoNombre = $presentacion?->producto?->nombre ?? 'Producto';
            $presentacionDesc = $presentacion?->descripcion ?? '';
            $cantidad = $detalles->sum('cantidad_solicitada');
            $subtotal = $detalles->sum('subtotal');

            $nombre = trim($productoNombre . ' ' . $presentacionDesc);
            $lineas[] = "- {$nombre}: {$cantidad} (Total {$this->formatMoney((float) $subtotal)})";
        }

        $mensaje = "Nuevo pedido ESPECIAL\n";
        $mensaje .= "Codigo: {$pedido->codigo}\n";
        $mensaje .= "Unidad: {$unidad}\n";
        $mensaje .= "Solicitud: {$fechaSolicitud}\n";
        $mensaje .= "Entrega: {$fechaEntrega}\n";
        $mensaje .= "Usuario: {$usuario}\n";
        $mensaje .= "Total: {$this->formatMoney($total)}\n";
        if (!empty($lineas)) {
            $mensaje .= "Detalle:\n" . implode("\n", $lineas);
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

    // ==========================
    // FORM CREAR PEDIDO ESPECIAL
    // ==========================
    public function crear(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id');
        $categoriaId = $request->get('categoria_id');

        $presQuery = ProductoPresentacion::query()
            ->where(function ($q) {
                $q->where('estado', 1)
                  ->orWhere('estado', 'Activo')
                  ->orWhere('estado', 'ACTIVO');
            })
            ->whereHas('producto', function ($qProd) use ($categoriaId, $q) {
                $qProd->where(function ($q2) {
                    $q2->where('estado', 1)
                        ->orWhere('estado', 'Activo')
                        ->orWhere('estado', 'ACTIVO');
                });

                if (!empty($categoriaId)) {
                    $qProd->where('categoria_id', $categoriaId);
                }

                if ($q !== '') {
                    $qProd->where('nombre', 'like', "%{$q}%");
                }
            })
            ->with([
                'producto.categoria',
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

        $presentaciones->getCollection()->transform(function ($pres) {
            $primero = $pres->proveedores->first();
            $pres->pp_default_id = $primero?->id;
            $pres->pp_default_precio = (float)($primero?->precio_vigente ?? 0);
            $pres->producto_nombre = $pres->producto->nombre ?? '';
            $pres->categoria_nombre = $pres->producto->categoria->nombre ?? '';
            return $pres;
        });

        $proveedores = Proveedor::activos()->orderBy('nombre')->get();
        $categorias = Categoria::query()
            ->where('estado', 'Activo')
            ->whereIn('id', function ($sub) {
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
                    ->distinct();
            })
            ->orderBy('nombre')
            ->get();

        if ($this->esAdminPedidos()) {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } elseif ($this->esResponsableDeUnidades()) {
            $unidadesOperativas = Auth::user()->unidadesAsignadas()->orderBy('nombre')->get();
        } else {
            $unidadesOperativas = collect();
        }

        return view('dashboard.crear_pedido_especial', compact(
            'presentaciones',
            'proveedores',
            'categorias',
            'unidadesOperativas'
        ));
    }

    public function previsualizar()
    {
        return view('dashboard.previsualizar_pedido_especial');
    }

    // ==========================
    // GUARDAR (BASE64) - como lo tenías
    // ==========================
    public function guardar(Request $request)
    {
        try {
            $request->validate([
                'fecha_solicitud'     => 'required|date',
                'fecha_entrega'       => 'required|date|after_or_equal:fecha_solicitud',
                'productos'           => 'required',
                'unidad_operativa_id' => 'nullable|integer',

                // PDFs pueden venir como archivo o base64, validamos “soft”
                'pdf_solicitud'    => 'required',
                'pdf_cotizacion'   => 'required',
                'pdf_autorizacion' => 'required',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'No autenticado.'], 401);
            }

            // ==========================
            // Unidad operativa según rol
            // ==========================
            $unidadOperativaId = null;

            if ($this->esAdminPedidos() || $this->esResponsableDeUnidades()) {
                $unidadOperativaId = $request->get('unidad_operativa_id') ?: null;
                if (!$unidadOperativaId) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Selecciona una unidad operativa para el pedido especial.'
                    ], 422);
                }
                if ($this->esResponsableDeUnidades() && !$user->puedeAccederUnidadOperativa((int)$unidadOperativaId)) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'La unidad seleccionada no estÃ¡ asignada a tu usuario.'
                    ], 422);
                }
            } else {
                $unidadOperativaId = $user->unidad_operativa_id ?? null;
                if (!$unidadOperativaId) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Tu usuario no tiene unidad operativa asignada. No se puede crear el pedido especial.'
                    ], 422);
                }
            }

            // ==========================
            // Productos
            // ==========================
            $productos = $request->productos;
            if (is_string($productos)) $productos = json_decode($productos, true);

            if (!is_array($productos) || count($productos) === 0) {
                return response()->json(['success' => false, 'error' => 'No se recibieron productos válidos.'], 422);
            }

            // ==========================
            // PDFs: aceptar File o Base64
            // ==========================
            $rutaSolicitud    = $this->guardarPdfFlexible($request, 'pdf_solicitud', 'pdfs_especiales');
            $rutaCotizacion   = $this->guardarPdfFlexible($request, 'pdf_cotizacion', 'pdfs_especiales');
            $rutaAutorizacion = $this->guardarPdfFlexible($request, 'pdf_autorizacion', 'pdfs_especiales');

            if (!$rutaSolicitud || !$rutaCotizacion || !$rutaAutorizacion) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Uno o más PDFs son inválidos o no se pudieron guardar.'
                ], 422);
            }

            $pedido = null;

            DB::transaction(function () use (
                &$pedido, $request, $user, $unidadOperativaId, $productos,
                $rutaSolicitud, $rutaCotizacion, $rutaAutorizacion
            ) {
                $intentos = 0;
                while (true) {
                    $intentos++;
                    try {
                        $pedido = Pedido::create([
                            'codigo'              => Pedido::generarCodigo(),
                            'fecha_solicitud'     => $request->fecha_solicitud,
                            'fecha_entrega'       => $request->fecha_entrega,
                            'estado'              => $this->estadoInicial(),
                            'user_id'             => $user->id,
                            'unidad_operativa_id' => (int)$unidadOperativaId,
                            'total'               => 0,
                            'es_especial'         => 1,
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

                foreach ($productos as $p) {
                    $presentacionId = isset($p['presentacion_id']) ? (int)$p['presentacion_id'] : null;
                    if (!$presentacionId) continue;

                    $ppId = $p['producto_proveedor_id'] ?? null; // ahora es presentacion_proveedor_id
                    if (!$ppId && isset($p['proveedor_id'])) {
                        $pp = PresentacionProveedor::where('presentacion_id', $presentacionId)
                            ->where('proveedor_id', (int)$p['proveedor_id'])
                            ->first();
                        $ppId = $pp?->id;
                    }

                    $pp = $ppId ? PresentacionProveedor::with(['presentacion', 'proveedor'])->find($ppId) : null;

                    if (!$this->esAdminPedidos()) {
                        $ppPrincipal = $this->resolverPresentacionProveedorPrincipal($presentacionId);
                        if ($ppPrincipal) {
                            $ppId = $ppPrincipal->id;
                            $pp = PresentacionProveedor::with(['presentacion', 'proveedor'])->find($ppId);
                        }
                    }

                    if (!$ppId || !$pp) continue;

                    $cantidad = isset($p['cantidad']) ? (float)$p['cantidad'] : 0;
                    $precio   = isset($p['precio']) ? (float)$p['precio'] : (float)($pp->precio_vigente ?? 0);

                    if ($cantidad < 0) $cantidad = 0;
                    if ($precio   < 0) $precio   = 0;

                    $subtotal = $cantidad * $precio;
                    $total += $subtotal;

                    DetallePedido::create([
                        'codigo'                => $pedido->codigo,
                        'presentacion_id'       => $presentacionId,
                        'producto_proveedor_id' => $ppId,
                        'cantidad_solicitada'   => $cantidad,
                        'cantidad_aprobada'     => $cantidad,
                        'precio_unitario'       => $precio,
                        'subtotal'              => $subtotal,
                        'activo'                => 1,
                    ]);
                }

                $pedido->update(['total' => $total]);

                PedidoEspecial::create([
                    'codigo'       => $pedido->codigo,
                    'solicitud'    => $rutaSolicitud,
                    'cotizacion'   => $rutaCotizacion,
                    'autorizacion' => $rutaAutorizacion,
                ]);
            });

            if ($pedido) {
                $this->sendTelegramPedidoNotification($pedido);
            }

            return response()->json(['success' => true, 'codigo' => $pedido->codigo]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => collect($e->errors())->flatten()->first() ?? 'Validación',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function guardarPdfFlexible(Request $request, string $field, string $folder): ?string
    {
        // 1) Si viene como archivo (lo que manda tu vista corregida)
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            if (!$file || !$file->isValid()) return null;

            // Validación real de PDF
            $ext  = strtolower($file->getClientOriginalExtension() ?? '');
            $mime = strtolower($file->getMimeType() ?? '');

            if ($ext !== 'pdf' && $mime !== 'application/pdf') return null;

            return $this->guardarUploadedPdf($file, $folder);
        }

        // 2) Si viene como base64 (por compatibilidad)
        $value = $request->input($field);

        if (!is_string($value) || trim($value) === '') return null;

        return $this->guardarBase64Pdf($value, $folder);
    }


    // ==========================
    // ✅ VER PDF (STREAM) SIN 403
    // ==========================
    public function verPdf(string $codigo, string $tipo)
    {
        if (!$this->puedeVerPDFs()) {
            abort(403);
        }

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        if ((int)$pedido->es_especial !== 1) abort(404);

        $especial = PedidoEspecial::where('codigo', $codigo)->firstOrFail();

        $tipo = strtolower($tipo);
        if (!in_array($tipo, ['solicitud', 'cotizacion', 'autorizacion'], true)) {
            abort(404);
        }

        $rutaPublica = $especial->{$tipo} ?? null;
        if (!$rutaPublica) abort(404);

        $ruta = ltrim($rutaPublica, '/');
        if (str_starts_with($ruta, 'storage/')) {
            $ruta = substr($ruta, strlen('storage/')); // disk public
        }

        if (!Storage::disk('public')->exists($ruta)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($ruta);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // ==========================
    // ✅ REEMPLAZAR PDFs (FILE UPLOAD) + BORRAR ANTERIOR
    // ==========================
    public function actualizarPdfFiles(Request $request, string $codigo)
    {
        $user = Auth::user();
        if (!$user) abort(401);

        if (!$this->puedeVerPDFs()) {
            abort(403);
        }

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        if ((int)$pedido->es_especial !== 1) abort(404);

        // 🔒 Permisos por estado + rol
        if (!$this->puedeEditarPDFs($pedido->estado)) {
            return back()->with('warning', "No se pueden modificar PDFs en estado: {$pedido->estado}.");
        }

        $request->validate([
            'pdf_solicitud_file'    => 'nullable|file|mimes:pdf|max:10240',
            'pdf_cotizacion_file'   => 'nullable|file|mimes:pdf|max:10240',
            'pdf_autorizacion_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $especial = PedidoEspecial::where('codigo', $codigo)->firstOrFail();

        DB::transaction(function () use ($request, $especial) {

            if ($request->hasFile('pdf_solicitud_file')) {
                $this->borrarPdfAnteriorSiExiste($especial->solicitud);
                $especial->solicitud = $this->guardarUploadedPdf($request->file('pdf_solicitud_file'), 'pdfs_especiales');
            }

            if ($request->hasFile('pdf_cotizacion_file')) {
                $this->borrarPdfAnteriorSiExiste($especial->cotizacion);
                $especial->cotizacion = $this->guardarUploadedPdf($request->file('pdf_cotizacion_file'), 'pdfs_especiales');
            }

            if ($request->hasFile('pdf_autorizacion_file')) {
                $this->borrarPdfAnteriorSiExiste($especial->autorizacion);
                $especial->autorizacion = $this->guardarUploadedPdf($request->file('pdf_autorizacion_file'), 'pdfs_especiales');
            }

            $especial->save();
        });

        return back()->with('success', 'PDF(s) actualizado(s) correctamente.');
    }

    // ==========================
    // Guardar base64 como PDF (PUBLIC)
    // ==========================
    private function guardarBase64Pdf($base64, string $folder): string
    {
        if (!is_string($base64) || trim($base64) === '') {
            throw new \Exception('PDF inválido (vacío).');
        }

        if (str_contains($base64, ',')) {
            $parts = explode(',', 2);
            $base64 = $parts[1] ?? '';
        }

        $pdfData = base64_decode(trim($base64), true);
        if ($pdfData === false) {
            throw new \Exception('PDF inválido (base64 no se pudo decodificar).');
        }

        $fileName = $folder . "/" . uniqid('esp_') . ".pdf";
        Storage::disk('public')->put($fileName, $pdfData);

        return "storage/" . $fileName;
    }

    // ✅ Guardar archivo real (upload)
    private function guardarUploadedPdf($file, string $folder): string
    {
        $path = $file->store($folder, 'public'); // devuelve "pdfs_especiales/xxx.pdf"
        return "storage/" . $path;
    }

    /**
     * Borra un PDF anterior del disk public.
     */
    private function borrarPdfAnteriorSiExiste(?string $rutaPublica): void
    {
        if (!$rutaPublica) return;

        $ruta = ltrim($rutaPublica, '/');
        if (str_starts_with($ruta, 'storage/')) {
            $ruta = substr($ruta, strlen('storage/'));
        }

        if ($ruta !== '' && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }

    // ==========================
    // BUSCADOR AJAX
    // ==========================
    public function buscarProductos(Request $request)
    {
        $q           = trim((string) $request->get('q', ''));
        $proveedorId = $request->get('proveedor_id');
        $categoriaId = $request->get('categoria_id');

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1]]);
        }

        $presQuery = ProductoPresentacion::query()
            ->where(function ($q) {
                $q->where('estado', 1)
                  ->orWhere('estado', 'Activo')
                  ->orWhere('estado', 'ACTIVO');
            })
            ->whereHas('producto', function ($qProd) use ($q) {
                $qProd->where(function ($q2) {
                    $q2->where('estado', 1)
                        ->orWhere('estado', 'Activo')
                        ->orWhere('estado', 'ACTIVO');
                });
                $qProd->where('nombre', 'like', "%{$q}%");
            })
            ->with([
                'producto.categoria',
                'proveedores' => function ($q) {
                    $q->with(['proveedor:id,nombre'])
                      ->select('id','presentacion_id','proveedor_id','precio_vigente','estado')
                      ->where('estado', 1)
                      ->orderByDesc('id');
                }
            ]);

        if (!empty($categoriaId)) {
            $presQuery->whereHas('producto', function ($qp) use ($categoriaId) {
                $qp->where('categoria_id', $categoriaId);
            });
        }

        if (!empty($proveedorId)) {
            $presQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                $sub->where('proveedor_id', $proveedorId);
            });
        }

        $presentaciones = $presQuery->orderBy('producto_id')->orderBy('descripcion')->paginate(10);

        $items = collect($presentaciones->items())->map(function ($pres) {
            $primero = $pres->proveedores->first();
            $pres->pp_default_id = $primero?->id;
            $pres->pp_default_precio = (float)($primero?->precio_vigente ?? 0);
            $pres->producto_nombre = $pres->producto->nombre ?? '';
            $pres->categoria_nombre = $pres->producto->categoria->nombre ?? '';
            return $pres;
        })->values()->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $presentaciones->currentPage(),
                'last_page'    => $presentaciones->lastPage(),
                'total'        => $presentaciones->total(),
            ]
        ]);
    }
}

