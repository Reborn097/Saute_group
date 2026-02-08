<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\InventarioCaducidad;
use App\Models\UnidadOperativa;
use App\Models\Kardex;
use App\Models\MovimientoInventario;
use App\Models\ProductoPresentacion;
use App\Models\PresentacionProveedor;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    /**
     * Almacenes permitidos:
     * - admin: todos
     * - almacenista / encargado_cocina / encargado_cafeteria: solo los de su unidad operativa
     *
     * BD:
     * - users.unidad_operativa_id
     * - almacenes.unidad_id
     */
    private function allowedAlmacenesQuery()
    {
        $user = Auth::user();
        $role = $user->role ?? '';

        $q = Almacen::query()->orderBy('nombre');

        if ($role === 'admin') {
            return $q;
        }

        $q->where(function ($sub) {
            $sub->whereNull('es_cedis')
                ->orWhere('es_cedis', false);
        })->whereRaw("LOWER(COALESCE(tipo, '')) <> 'cedis'");

        $uoId = (int) $user->unidad_operativa_id;

        return $q->where('unidad_id', $uoId);
    }

    private function assertAlmacenAllowed(int $almacenId): void
    {
        $ok = $this->allowedAlmacenesQuery()->where('id', $almacenId)->exists();

        if (!$ok) {
            abort(403, 'No tienes permiso para acceder a este almacÃ©n.');
        }
    }

    private function isCedisAlmacen(?Almacen $almacen): bool
    {
        if (!$almacen) {
            return false;
        }

        return (bool) ($almacen->es_cedis ?? false)
            || mb_strtolower((string) ($almacen->tipo ?? '')) === 'cedis';
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role ?? '';

        $almacenId = $request->get('almacen_id');
        $uoId = $request->get('unidad_operativa_id'); // âœ… nuevo (solo admin)

        // âœ… Unidades operativas solo para admin (para el dropdown)
        $unidadesOperativas = collect();
        if ($role === 'admin') {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            // para no-admin, fuerza su unidad (por seguridad)
            $uoId = (int) $user->unidad_operativa_id;
        }

        // âœ… Almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // âœ… Si es admin y seleccionÃ³ unidad, filtra almacenes por esa unidad
        if ($role === 'admin' && $uoId) {
            $almacenesQuery->where('unidad_id', (int)$uoId);
        }

        $almacenes = $almacenesQuery->get();
        $allowedIds = $almacenes->pluck('id');

        // âœ… Si piden un almacÃ©n especÃ­fico, validar permiso (y que pertenezca al set actual)
        if ($almacenId) {
            $this->assertAlmacenAllowed((int)$almacenId);
        }

        $query = Inventario::with(['presentacion.producto.categoria', 'producto.categoria', 'almacen'])
            ->addSelect([
                'precio_ultimo' => PresentacionProveedor::select('precio_vigente')
                    ->whereColumn('presentacion_id', 'inventarios.presentacion_id')
                    ->where(function ($q) {
                        $q->where('estado', 1)
                            ->orWhere('estado', 'Activo')
                            ->orWhere('estado', 'ACTIVO');
                    })
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->whereIn('almacen_id', $allowedIds);

        if ($almacenId) {
            $query->where('almacen_id', (int)$almacenId);
        }

        $inventarios = $query->orderBy('almacen_id')
            ->orderBy('presentacion_id')
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.inventarios.index', compact(
            'inventarios',
            'almacenes',
            'almacenId',
            'unidadesOperativas',
            'uoId'
        ));
    }


    public function movimientoForm(Request $request)
    {
        $user = Auth::user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        // âœ… unidad operativa seleccionada (solo admin)
        $uoId = $request->get('unidad_operativa_id');

        $unidadesOperativas = collect();
        if ($esAdmin) {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            // para no-admin, forzar su unidad operativa
            $uoId = (int) $user->unidad_operativa_id;
        }

        // âœ… almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // âœ… admin: si elige unidad, filtra almacenes a esa unidad
        if ($esAdmin && $uoId) {
            $almacenesQuery->where('unidad_id', (int)$uoId);
        }

        $almacenes = $almacenesQuery->get();
        $almacenesDestino = Almacen::query()
            ->where(function ($q) {
                $q->whereNull('es_cedis')->orWhere('es_cedis', false);
            })
            ->whereRaw("LOWER(COALESCE(tipo, '')) <> 'cedis'")
            ->orderBy('nombre')
            ->get();

        $presentaciones = ProductoPresentacion::with('producto')
            ->orderBy('producto_id')
            ->orderBy('descripcion')
            ->get();

        return view('dashboard.inventarios.movimiento', compact(
            'almacenes',
            'almacenesDestino',
            'presentaciones',
            'unidadesOperativas',
            'uoId'
        ));
    }


    /**
     * âœ… VersiÃ³n para guardar MULTI items (items[]), compatible con la vista de "lista".
     * Si sigues usando el formulario viejo (1 item), entonces tendrÃ­as que adaptar la vista o crear otra ruta.
     */
    public function movimientoStore(Request $request)
    {
        $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'items'      => 'required|array|min:1',

            'items.*.presentacion_id' => 'nullable|exists:producto_presentaciones,id',
            'items.*.producto_id'     => 'nullable|exists:productos,id',
            'items.*.tipo_movimiento' => 'required|in:entrada,salida,ajuste,transferencia',
            'items.*.cantidad'        => 'required|numeric|min:0.01',
            'items.*.motivo'          => 'nullable|string|max:30',
            'items.*.lote'            => 'nullable|string|max:15',
            'items.*.caducidad'       => 'nullable|date',
            'items.*.destino_almacen_id' => 'nullable|exists:almacenes,id',
        ]);

        $user = Auth::user();
        $isAdmin = ($user->role ?? '') === 'admin';
        $almacenId = (int) $request->almacen_id;

        $almacenOrigen = Almacen::findOrFail($almacenId);

        $this->assertAlmacenAllowed($almacenId);

        if ($this->isCedisAlmacen($almacenOrigen) && !$isAdmin) {
            abort(403, 'Solo administradores pueden operar movimientos en CEDIS.');
        }

        $items = $request->input('items', []);
        $origenEsCedis = $this->isCedisAlmacen($almacenOrigen);
        $transferenciaRef = 'TRF-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        DB::transaction(function () use ($almacenId, $items, $transferenciaRef, $isAdmin, $origenEsCedis) {

            foreach ($items as $idx => $item) {

                $presentacionId = (int) ($item['presentacion_id'] ?? 0);
                if (!$presentacionId && !empty($item['producto_id'])) {
                    $presentacionId = (int) ProductoPresentacion::query()
                        ->where('producto_id', (int)$item['producto_id'])
                        ->orderByDesc(DB::raw("descripcion = 'Default'"))
                        ->orderBy('id')
                        ->value('id');
                }
                $tipo       = (string) ($item['tipo_movimiento'] ?? '');
                $cantidad   = (float) ($item['cantidad'] ?? 0);
                $destinoAlmacenId = isset($item['destino_almacen_id']) ? (int) $item['destino_almacen_id'] : null;
                $motivo = isset($item['motivo']) ? trim((string)$item['motivo']) : null;

                $lote = isset($item['lote']) && trim((string)$item['lote']) !== '' ? trim((string)$item['lote']) : null;
                $cad  = isset($item['caducidad']) && (string)$item['caducidad'] !== '' ? (string)$item['caducidad'] : null;

                $usarCaducidades = (($lote !== null) || ($cad !== null)) && $tipo !== 'ajuste';

                $presentacion = ProductoPresentacion::with('producto')->find($presentacionId);
                if (!$presentacion) {
                    throw ValidationException::withMessages([
                        "items.$idx.presentacion_id" => "Renglón #".($idx+1).": Presentación inválida.",
                    ]);
                }
                $productoId = (int) $presentacion->producto_id;

                $inventarioOrigen = Inventario::firstOrCreate(
                    ['almacen_id' => $almacenId, 'presentacion_id' => $presentacionId],
                    ['producto_id' => $productoId, 'cantidad' => 0]
                );

                $cantidadActual = (float) $inventarioOrigen->cantidad;
                $nuevaCantidad  = $cantidadActual;
                $referencia = null;

                if ($tipo === 'entrada') {
                    $nuevaCantidad = $cantidadActual + $cantidad;
                } elseif ($tipo === 'salida') {
                    $nuevaCantidad = $cantidadActual - $cantidad;

                    if ($nuevaCantidad < 0) {
                        throw ValidationException::withMessages([
                            "items.$idx.cantidad" => "Renglón #".($idx+1).": No hay suficiente inventario general para hacer la salida.",
                        ]);
                    }
                } elseif ($tipo === 'ajuste') {
                    $nuevaCantidad = $cantidad;
                } elseif ($tipo === 'transferencia') {
                    if (!$isAdmin) {
                        throw ValidationException::withMessages([
                            "items.$idx.tipo_movimiento" => "Renglón #".($idx+1).": Solo administradores pueden registrar transferencias.",
                        ]);
                    }

                    if (!$origenEsCedis) {
                        throw ValidationException::withMessages([
                            "items.$idx.tipo_movimiento" => "Renglón #".($idx+1).": Las transferencias solo pueden salir de un almacén CEDIS.",
                        ]);
                    }

                    if ($destinoAlmacenId === null || $destinoAlmacenId <= 0) {
                        throw ValidationException::withMessages([
                            "items.$idx.destino_almacen_id" => "Renglón #".($idx+1).": Selecciona almacén destino.",
                        ]);
                    }

                    if ($destinoAlmacenId === $almacenId) {
                        throw ValidationException::withMessages([
                            "items.$idx.destino_almacen_id" => "Renglón #".($idx+1).": El almacén destino debe ser distinto al origen.",
                        ]);
                    }

                    $almacenDestino = Almacen::find($destinoAlmacenId);
                    if (!$almacenDestino) {
                        throw ValidationException::withMessages([
                            "items.$idx.destino_almacen_id" => "Renglón #".($idx+1).": Almacén destino inválido.",
                        ]);
                    }

                    if ($this->isCedisAlmacen($almacenDestino)) {
                        throw ValidationException::withMessages([
                            "items.$idx.destino_almacen_id" => "Renglón #".($idx+1).": El destino no puede ser otro CEDIS.",
                        ]);
                    }

                    $nuevaCantidad = $cantidadActual - $cantidad;
                    if ($nuevaCantidad < 0) {
                        throw ValidationException::withMessages([
                            "items.$idx.cantidad" => "Renglón #".($idx+1).": No hay suficiente inventario en origen para transferir.",
                        ]);
                    }

                    $inventarioDestino = Inventario::firstOrCreate(
                        ['almacen_id' => $destinoAlmacenId, 'presentacion_id' => $presentacionId],
                        ['producto_id' => $productoId, 'cantidad' => 0]
                    );

                    $inventarioDestino->cantidad = (float) $inventarioDestino->cantidad + $cantidad;
                    $inventarioDestino->save();

                    if ($usarCaducidades) {
                        $rowOrigen = InventarioCaducidad::firstOrCreate(
                            [
                                'inventario_id' => $inventarioOrigen->id,
                                'producto_id'   => $productoId,
                                'presentacion_id' => $presentacionId,
                                'almacen_id'    => $almacenId,
                                'lote'          => $lote,
                                'caducidad'     => $cad,
                            ],
                            ['cantidad' => 0]
                        );

                        $rowOrigen->cantidad = (float) $rowOrigen->cantidad - $cantidad;
                        if ($rowOrigen->cantidad < 0) {
                            throw ValidationException::withMessages([
                                "items.$idx.cantidad" => "Renglón #".($idx+1).": No hay suficiente cantidad en ese lote/caducidad para transferir.",
                            ]);
                        }
                        $rowOrigen->save();

                        $rowDestino = InventarioCaducidad::firstOrCreate(
                            [
                                'inventario_id' => $inventarioDestino->id,
                                'producto_id'   => $productoId,
                                'presentacion_id' => $presentacionId,
                                'almacen_id'    => $destinoAlmacenId,
                                'lote'          => $lote,
                                'caducidad'     => $cad,
                            ],
                            ['cantidad' => 0]
                        );
                        $rowDestino->cantidad = (float) $rowDestino->cantidad + $cantidad;
                        $rowDestino->save();
                    }

                    $referencia = $transferenciaRef;

                    Kardex::create([
                        'inventario_id'    => $inventarioDestino->id,
                        'producto_id'      => $productoId,
                        'presentacion_id'  => $presentacionId,
                        'user_id'          => Auth::id(),
                        'cantidad'         => $cantidad,
                        'tipo_movimiento'  => 'transferencia',
                        'motivo'           => $motivo,
                        'referencia'       => $referencia,
                        'fecha_movimiento' => now(),
                    ]);

                    $costoUnitario = (float) (PresentacionProveedor::query()
                        ->where('presentacion_id', $presentacionId)
                        ->where(function ($q) {
                            $q->where('estado', 1)
                                ->orWhere('estado', 'Activo')
                                ->orWhere('estado', 'ACTIVO');
                        })
                        ->orderByDesc('id')
                        ->value('precio_vigente') ?? 0);

                    MovimientoInventario::create([
                        'producto_id' => $productoId,
                        'presentacion_id' => $presentacionId,
                        'almacen_id' => $almacenId,
                        'almacen_destino_id' => $destinoAlmacenId,
                        'tipo' => 'transferencia',
                        'cantidad' => $cantidad,
                        'motivo' => $motivo,
                        'referencia' => $referencia,
                        'fecha' => now()->toDateString(),
                        'usuario_id' => Auth::id(),
                        'caducidad' => $cad,
                        'costo_unitario' => $costoUnitario,
                        'costo_total' => round($costoUnitario * $cantidad, 2),
                    ]);
                }

                $inventarioOrigen->cantidad = $nuevaCantidad;
                $inventarioOrigen->save();

                if ($usarCaducidades && $tipo !== 'transferencia') {

                    $row = InventarioCaducidad::firstOrCreate(
                        [
                            'inventario_id' => $inventarioOrigen->id,
                            'producto_id'   => $productoId,
                            'presentacion_id' => $presentacionId,
                            'almacen_id'    => $almacenId,
                            'lote'          => $lote,
                            'caducidad'     => $cad,
                        ],
                        ['cantidad' => 0]
                    );

                    $rowCantidadActual = (float) $row->cantidad;

                    if ($tipo === 'entrada') {
                        $row->cantidad = $rowCantidadActual + $cantidad;
                    } else {
                        $row->cantidad = $rowCantidadActual - $cantidad;

                        if ($row->cantidad < 0) {
                            throw ValidationException::withMessages([
                                "items.$idx.cantidad" => "Renglón #".($idx+1).": No hay suficiente cantidad en ese lote/caducidad.",
                            ]);
                        }
                    }

                    $row->save();
                }

                Kardex::create([
                    'inventario_id'    => $inventarioOrigen->id,
                    'producto_id'      => $productoId,
                    'presentacion_id'  => $presentacionId,
                    'user_id'          => Auth::id(),
                    'cantidad'         => $cantidad,
                    'tipo_movimiento'  => $tipo,
                    'motivo'           => $motivo,
                    'referencia'       => $referencia,
                    'fecha_movimiento' => now(),
                ]);
            }
        });

        return redirect()
            ->route('inventarios.index')
            ->with('success', 'Movimientos registrados correctamente.');
    }
    public function kardex(Request $request)
    {
        $user = Auth::user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        $almacenId = $request->get('almacen_id');
        $q = trim((string) $request->get('q'));

        // âœ… unidad operativa (solo admin selecciona, no-admin se fuerza)
        $uoId = $request->get('unidad_operativa_id');

        $unidadesOperativas = collect();
        if ($esAdmin) {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            $uoId = (int) $user->unidad_operativa_id;
        }

        // âœ… almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // âœ… admin: si eligiÃ³ unidad, reduce almacenes a esa unidad
        if ($esAdmin && $uoId) {
            $almacenesQuery->where('unidad_id', (int) $uoId);
        }

        $almacenes = $almacenesQuery->get();
        $allowedIds = $almacenes->pluck('id');

        // validar permiso si piden almacÃ©n
        if ($almacenId) {
            $this->assertAlmacenAllowed((int) $almacenId);
        }

        // âœ… rango de fechas (por defecto Ãºltimos 7 dÃ­as)
        // Usamos "date" del request y convertimos a rango inclusivo (start/end of day)
        $desdeStr = $request->get('desde');
        $hastaStr = $request->get('hasta');

        $desde = $desdeStr
            ? Carbon::parse($desdeStr)->startOfDay()
            : now()->subDays(7)->startOfDay();

        $hasta = $hastaStr
            ? Carbon::parse($hastaStr)->endOfDay()
            : now()->endOfDay();

        $query = Kardex::with(['presentacion.producto', 'producto', 'usuario', 'inventario.almacen'])
            ->whereHas('inventario', fn ($qInv) => $qInv->whereIn('almacen_id', $allowedIds))
            ->whereBetween('fecha_movimiento', [$desde, $hasta])
            ->orderBy('fecha_movimiento', 'desc');

        if ($almacenId) {
            $query->whereHas('inventario', fn ($qInv) => $qInv->where('almacen_id', (int) $almacenId));
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('presentacion.producto', function ($qProd) use ($q) {
                    $qProd->where('nombre', 'like', "%{$q}%")
                          ->orWhere('marca', 'like', "%{$q}%");
                })->orWhereHas('producto', function ($qProd) use ($q) {
                    $qProd->where('nombre', 'like', "%{$q}%")
                          ->orWhere('marca', 'like', "%{$q}%");
                });
            });
        }

        $movimientos = $query->paginate(10)->withQueryString();

        // para llenar inputs en la vista (formato Y-m-d)
        $desdeInput = $desde->toDateString();
        $hastaInput = $hasta->toDateString();

        return view('dashboard.inventarios.kardex', compact(
            'movimientos',
            'almacenes',
            'almacenId',
            'unidadesOperativas',
            'uoId',
            'q',
            'desdeInput',
            'hastaInput'
        ));
    }

    public function reporteTransferencias(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? '') !== 'admin') {
            abort(403, 'Solo administradores pueden consultar este reporte.');
        }

        $uoId = $request->get('unidad_operativa_id');
        $almacenOrigenId = $request->get('almacen_origen_id');
        $almacenDestinoId = $request->get('almacen_destino_id');
        $folio = trim((string) $request->get('folio', ''));

        $desde = $request->filled('desde')
            ? Carbon::parse((string) $request->get('desde'))->startOfDay()
            : now()->startOfMonth();

        $hasta = $request->filled('hasta')
            ? Carbon::parse((string) $request->get('hasta'))->endOfDay()
            : now()->endOfDay();

        if ($desde->gt($hasta)) {
            throw ValidationException::withMessages([
                'desde' => 'La fecha "Desde" no puede ser mayor que "Hasta".',
            ]);
        }

        $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();

        $almacenesOrigen = Almacen::query()
            ->where(function ($q) {
                $q->where('es_cedis', true)
                    ->orWhereRaw("LOWER(COALESCE(tipo, '')) = 'cedis'");
            })
            ->orderBy('nombre')
            ->get();

        $almacenesDestinoQuery = Almacen::query()
            ->where(function ($q) {
                $q->whereNull('es_cedis')
                    ->orWhere('es_cedis', false);
            })
            ->whereRaw("LOWER(COALESCE(tipo, '')) <> 'cedis'");

        if ($uoId) {
            $almacenesDestinoQuery->where('unidad_id', (int) $uoId);
        }

        $almacenesDestino = $almacenesDestinoQuery
            ->orderBy('nombre')
            ->get();

        $baseQuery = DB::table('movimientos_inventario as mi')
            ->leftJoin('almacenes as ao', 'ao.id', '=', 'mi.almacen_id')
            ->leftJoin('almacenes as ad', 'ad.id', '=', 'mi.almacen_destino_id')
            ->leftJoin('unidades_operativas as uod', 'uod.id', '=', 'ad.unidad_id')
            ->leftJoin('producto_presentaciones as pp', 'pp.id', '=', 'mi.presentacion_id')
            ->leftJoin('productos as pr', 'pr.id', '=', 'mi.producto_id')
            ->leftJoin('users as u', 'u.id', '=', 'mi.usuario_id')
            ->where('mi.tipo', 'transferencia')
            ->whereBetween('mi.fecha', [$desde->toDateString(), $hasta->toDateString()]);

        if ($uoId) {
            $baseQuery->where('ad.unidad_id', (int) $uoId);
        }

        if ($almacenOrigenId) {
            $baseQuery->where('mi.almacen_id', (int) $almacenOrigenId);
        }

        if ($almacenDestinoId) {
            $baseQuery->where('mi.almacen_destino_id', (int) $almacenDestinoId);
        }

        if ($folio !== '') {
            $baseQuery->where('mi.referencia', 'like', "%{$folio}%");
        }

        $stats = (clone $baseQuery)
            ->selectRaw("
                COUNT(*) as total_movimientos,
                COUNT(DISTINCT COALESCE(mi.referencia, CONCAT('SINREF-', mi.id))) as total_transferencias,
                SUM(COALESCE(mi.cantidad, 0)) as total_cantidad,
                SUM(COALESCE(mi.costo_total, 0)) as total_costo
            ")
            ->first();

        $transferencias = (clone $baseQuery)
            ->selectRaw("
                mi.id,
                mi.fecha,
                mi.referencia,
                ao.nombre as almacen_origen,
                uod.nombre as unidad_destino,
                ad.nombre as almacen_destino,
                pr.nombre as producto,
                pp.descripcion as presentacion,
                pp.contenido as presentacion_contenido,
                pp.unidad_contenido,
                mi.cantidad,
                mi.costo_unitario,
                mi.costo_total,
                mi.motivo,
                COALESCE(u.name, u.username) as usuario
            ")
            ->orderBy('mi.fecha', 'desc')
            ->orderBy('mi.id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $desdeInput = $desde->toDateString();
        $hastaInput = $hasta->toDateString();

        return view('dashboard.inventarios.transferencias', compact(
            'transferencias',
            'unidadesOperativas',
            'almacenesOrigen',
            'almacenesDestino',
            'uoId',
            'almacenOrigenId',
            'almacenDestinoId',
            'folio',
            'desdeInput',
            'hastaInput',
            'stats'
        ));
    }

    public function imprimirTransferenciasPorFolio(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? '') !== 'admin') {
            abort(403, 'Solo administradores pueden consultar esta pantalla.');
        }

        $folio = trim((string) $request->get('folio', ''));

        $desde = $request->filled('desde')
            ? Carbon::parse((string) $request->get('desde'))->startOfDay()
            : now()->startOfMonth();

        $hasta = $request->filled('hasta')
            ? Carbon::parse((string) $request->get('hasta'))->endOfDay()
            : now()->endOfDay();

        if ($desde->gt($hasta)) {
            throw ValidationException::withMessages([
                'desde' => 'La fecha "Desde" no puede ser mayor que "Hasta".',
            ]);
        }

        $foliosQuery = DB::table('movimientos_inventario as mi')
            ->where('mi.tipo', 'transferencia')
            ->whereNotNull('mi.referencia')
            ->where('mi.referencia', '<>', '')
            ->whereBetween('mi.fecha', [$desde->toDateString(), $hasta->toDateString()]);

        if ($folio !== '') {
            $foliosQuery->where('mi.referencia', 'like', "%{$folio}%");
        }

        $folios = $foliosQuery
            ->selectRaw("
                mi.referencia as folio,
                MIN(mi.fecha) as fecha,
                COUNT(*) as total_movimientos,
                SUM(COALESCE(mi.cantidad, 0)) as total_cantidad
            ")
            ->groupBy('mi.referencia')
            ->orderByRaw('MIN(mi.fecha) DESC')
            ->paginate(15)
            ->withQueryString();

        $desdeInput = $desde->toDateString();
        $hastaInput = $hasta->toDateString();

        return view('dashboard.inventarios.transferencias_folios', compact(
            'folios',
            'folio',
            'desdeInput',
            'hastaInput'
        ));
    }

    public function imprimirTransferenciasPorFolioPdf(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? '') !== 'admin') {
            abort(403, 'Solo administradores pueden descargar este PDF.');
        }

        $folio = trim((string) $request->get('folio', ''));
        if ($folio === '') {
            throw ValidationException::withMessages([
                'folio' => 'Debes seleccionar un folio para imprimir.',
            ]);
        }

        $transferencias = DB::table('movimientos_inventario as mi')
            ->leftJoin('almacenes as ad', 'ad.id', '=', 'mi.almacen_destino_id')
            ->leftJoin('unidades_operativas as uod', 'uod.id', '=', 'ad.unidad_id')
            ->leftJoin('producto_presentaciones as pp', 'pp.id', '=', 'mi.presentacion_id')
            ->leftJoin('productos as pr', 'pr.id', '=', 'mi.producto_id')
            ->where('mi.tipo', 'transferencia')
            ->where('mi.referencia', $folio)
            ->selectRaw("
                mi.id,
                mi.fecha,
                mi.referencia,
                uod.nombre as unidad_destino,
                ad.nombre as almacen_destino,
                pr.nombre as producto,
                pp.descripcion as presentacion,
                pp.contenido as presentacion_contenido,
                pp.unidad_contenido,
                mi.cantidad,
                mi.motivo
            ")
            ->orderBy('mi.id', 'asc')
            ->get();

        if ($transferencias->isEmpty()) {
            throw ValidationException::withMessages([
                'folio' => 'No se encontraron transferencias para ese folio.',
            ]);
        }

        $fecha = (string) ($transferencias->first()->fecha ?? now()->toDateString());
        $totalCantidad = (float) $transferencias->sum('cantidad');
        $safeFolio = preg_replace('/[^A-Za-z0-9_-]+/', '_', $folio);
        $file = 'transferencias_' . $safeFolio . '_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('dashboard.inventarios.transferencias_folio_pdf', [
            'folio' => $folio,
            'fecha' => $fecha,
            'transferencias' => $transferencias,
            'totalCantidad' => $totalCantidad,
        ])->setPaper('letter', 'landscape');

        return $pdf->download($file);
    }


    public function caducidades(Request $request)
    {
        $user = Auth::user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        $almacenId = $request->get('almacen_id');

        // âœ… nuevo (solo admin)
        $uoId = $request->get('unidad_operativa_id');

        // âœ… nuevo: filtro estado
        $estado = $request->get('estado'); // vigente | por_vencer | vencido | sin_fecha

        // âœ… unidades operativas solo admin (dropdown)
        $unidadesOperativas = collect();
        if ($esAdmin) {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            // no-admin: forzar su unidad
            $uoId = (int) $user->unidad_operativa_id;
        }

        // âœ… almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // âœ… admin: si eligiÃ³ unidad, reduce almacenes a esa unidad
        if ($esAdmin && $uoId) {
            $almacenesQuery->where('unidad_id', (int)$uoId);
        }

        $almacenes = $almacenesQuery->get();
        $allowedIds = $almacenes->pluck('id');

        if ($almacenId) {
            $this->assertAlmacenAllowed((int)$almacenId);
        }

        $query = InventarioCaducidad::with(['presentacion.producto', 'producto', 'almacen', 'inventario'])
            ->whereIn('almacen_id', $allowedIds);

        if ($almacenId) {
            $query->where('almacen_id', (int)$almacenId);
        }

        // âœ… filtro por estado
        // DefiniciÃ³n:
        // - sin_fecha: caducidad IS NULL
        // - vencido: caducidad < hoy
        // - por_vencer: caducidad entre hoy y hoy+15
        // - vigente: caducidad > hoy+15
        $hoy = Carbon::today();
        $limite = $hoy->copy()->addDays(15);

        if ($estado === 'sin_fecha') {
            $query->whereNull('caducidad');
        } elseif ($estado === 'vencido') {
            $query->whereNotNull('caducidad')->whereDate('caducidad', '<', $hoy);
        } elseif ($estado === 'por_vencer') {
            $query->whereNotNull('caducidad')
                ->whereDate('caducidad', '>=', $hoy)
                ->whereDate('caducidad', '<=', $limite);
        } elseif ($estado === 'vigente') {
            $query->whereNotNull('caducidad')->whereDate('caducidad', '>', $limite);
        }

        $caducidades = $query
            ->orderByRaw("caducidad IS NULL") // nulls al final
            ->orderBy('caducidad', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.inventarios.caducidades', compact(
            'caducidades',
            'almacenes',
            'almacenId',
            'unidadesOperativas',
            'uoId',
            'estado'
        ));
    }

    
}


