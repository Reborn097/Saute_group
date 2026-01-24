<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Inventario;
use App\Models\InventarioCaducidad;
use App\Models\UnidadOperativa;
use App\Models\Kardex;
use App\Models\Producto;
use Carbon\Carbon;
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

        $uoId = (int) $user->unidad_operativa_id;

        return $q->where('unidad_id', $uoId);
    }

    private function assertAlmacenAllowed(int $almacenId): void
    {
        $ok = $this->allowedAlmacenesQuery()->where('id', $almacenId)->exists();

        if (!$ok) {
            abort(403, 'No tienes permiso para acceder a este almacén.');
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role ?? '';

        $almacenId = $request->get('almacen_id');
        $uoId = $request->get('unidad_operativa_id'); // ✅ nuevo (solo admin)

        // ✅ Unidades operativas solo para admin (para el dropdown)
        $unidadesOperativas = collect();
        if ($role === 'admin') {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            // para no-admin, fuerza su unidad (por seguridad)
            $uoId = (int) $user->unidad_operativa_id;
        }

        // ✅ Almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // ✅ Si es admin y seleccionó unidad, filtra almacenes por esa unidad
        if ($role === 'admin' && $uoId) {
            $almacenesQuery->where('unidad_id', (int)$uoId);
        }

        $almacenes = $almacenesQuery->get();
        $allowedIds = $almacenes->pluck('id');

        // ✅ Si piden un almacén específico, validar permiso (y que pertenezca al set actual)
        if ($almacenId) {
            $this->assertAlmacenAllowed((int)$almacenId);
        }

        $query = Inventario::with(['producto.categoria', 'almacen'])
            ->whereIn('almacen_id', $allowedIds);

        if ($almacenId) {
            $query->where('almacen_id', (int)$almacenId);
        }

        $inventarios = $query->orderBy('almacen_id')
            ->orderBy('producto_id')
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

        // ✅ unidad operativa seleccionada (solo admin)
        $uoId = $request->get('unidad_operativa_id');

        $unidadesOperativas = collect();
        if ($esAdmin) {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            // para no-admin, forzar su unidad operativa
            $uoId = (int) $user->unidad_operativa_id;
        }

        // ✅ almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // ✅ admin: si elige unidad, filtra almacenes a esa unidad
        if ($esAdmin && $uoId) {
            $almacenesQuery->where('unidad_id', (int)$uoId);
        }

        $almacenes = $almacenesQuery->get();

        $productos = Producto::orderBy('nombre')->get();

        return view('dashboard.inventarios.movimiento', compact(
            'almacenes',
            'productos',
            'unidadesOperativas',
            'uoId'
        ));
    }


    /**
     * ✅ Versión para guardar MULTI items (items[]), compatible con la vista de "lista".
     * Si sigues usando el formulario viejo (1 item), entonces tendrías que adaptar la vista o crear otra ruta.
     */
    public function movimientoStore(Request $request)
    {
        $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'items'      => 'required|array|min:1',

            'items.*.producto_id'     => 'required|exists:productos,id',
            'items.*.tipo_movimiento' => 'required|in:entrada,salida,ajuste',
            'items.*.cantidad'        => 'required|numeric|min:0.01',
            'items.*.motivo'          => 'nullable|string|max:255',
            'items.*.lote'            => 'nullable|string|max:120',
            'items.*.caducidad'       => 'nullable|date',
        ]);

        $almacenId = (int) $request->almacen_id;

        // 🔒 permiso real
        $this->assertAlmacenAllowed($almacenId);

        $items = $request->input('items', []);

        DB::transaction(function () use ($almacenId, $items) {

            foreach ($items as $idx => $item) {

                $productoId = (int) ($item['producto_id'] ?? 0);
                $tipo       = (string) ($item['tipo_movimiento'] ?? '');
                $cantidad   = (float) ($item['cantidad'] ?? 0);

                // Normalizar lote/caducidad
                $lote = isset($item['lote']) && trim((string)$item['lote']) !== '' ? trim((string)$item['lote']) : null;
                $cad  = isset($item['caducidad']) && (string)$item['caducidad'] !== '' ? (string)$item['caducidad'] : null;

                // Solo tocar inventario_caducidades si hay lote o caducidad y NO es ajuste
                $usarCaducidades = (($lote !== null) || ($cad !== null)) && $tipo !== 'ajuste';

                /**
                 * 1) INVENTARIO AGREGADO (producto + almacen)
                 */
                $inventario = Inventario::firstOrCreate(
                    ['almacen_id' => $almacenId, 'producto_id' => $productoId],
                    ['cantidad' => 0, 'area_almacen' => null]
                );

                $cantidadActual = (float) $inventario->cantidad;
                $nuevaCantidad  = $cantidadActual;

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
                    // Ajuste = setear a la cantidad final (no tocar lotes/caducidades)
                    $nuevaCantidad = $cantidad;
                }

                $inventario->cantidad = $nuevaCantidad;
                $inventario->save();

                /**
                 * 2) INVENTARIO POR LOTE/CADUCIDAD (inventario_caducidades)
                 */
                if ($usarCaducidades) {

                    $row = InventarioCaducidad::firstOrCreate(
                        [
                            'inventario_id' => $inventario->id,
                            'producto_id'   => $productoId,
                            'almacen_id'    => $almacenId,
                            'lote'          => $lote,
                            'caducidad'     => $cad,
                        ],
                        ['cantidad' => 0]
                    );

                    $rowCantidadActual = (float) $row->cantidad;

                    if ($tipo === 'entrada') {
                        $row->cantidad = $rowCantidadActual + $cantidad;
                    } else { // salida
                        $row->cantidad = $rowCantidadActual - $cantidad;

                        if ($row->cantidad < 0) {
                            throw ValidationException::withMessages([
                                "items.$idx.cantidad" => "Renglón #".($idx+1).": No hay suficiente cantidad en ese lote/caducidad.",
                            ]);
                        }
                    }

                    $row->save();
                }

                /**
                 * 3) KARDEX (SIEMPRE)
                 */
                Kardex::create([
                    'inventario_id'    => $inventario->id,
                    'producto_id'      => $productoId,
                    'user_id'          => Auth::id(),
                    'cantidad'         => $cantidad,
                    'tipo_movimiento'  => $tipo,
                    'motivo'           => $item['motivo'] ?? null,
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

        // ✅ unidad operativa (solo admin selecciona, no-admin se fuerza)
        $uoId = $request->get('unidad_operativa_id');

        $unidadesOperativas = collect();
        if ($esAdmin) {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            $uoId = (int) $user->unidad_operativa_id;
        }

        // ✅ almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // ✅ admin: si eligió unidad, reduce almacenes a esa unidad
        if ($esAdmin && $uoId) {
            $almacenesQuery->where('unidad_id', (int) $uoId);
        }

        $almacenes = $almacenesQuery->get();
        $allowedIds = $almacenes->pluck('id');

        // validar permiso si piden almacén
        if ($almacenId) {
            $this->assertAlmacenAllowed((int) $almacenId);
        }

        // ✅ rango de fechas (por defecto últimos 7 días)
        // Usamos "date" del request y convertimos a rango inclusivo (start/end of day)
        $desdeStr = $request->get('desde');
        $hastaStr = $request->get('hasta');

        $desde = $desdeStr
            ? Carbon::parse($desdeStr)->startOfDay()
            : now()->subDays(7)->startOfDay();

        $hasta = $hastaStr
            ? Carbon::parse($hastaStr)->endOfDay()
            : now()->endOfDay();

        $query = Kardex::with(['producto', 'usuario', 'inventario.almacen'])
            ->whereHas('inventario', fn ($qInv) => $qInv->whereIn('almacen_id', $allowedIds))
            ->whereBetween('fecha_movimiento', [$desde, $hasta])
            ->orderBy('fecha_movimiento', 'desc');

        if ($almacenId) {
            $query->whereHas('inventario', fn ($qInv) => $qInv->where('almacen_id', (int) $almacenId));
        }

        if ($q !== '') {
            $query->whereHas('producto', function ($qProd) use ($q) {
                $qProd->where('nombre', 'like', "%{$q}%");
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


    public function caducidades(Request $request)
    {
        $user = Auth::user();
        $role = $user->role ?? '';
        $esAdmin = $role === 'admin';

        $almacenId = $request->get('almacen_id');

        // ✅ nuevo (solo admin)
        $uoId = $request->get('unidad_operativa_id');

        // ✅ nuevo: filtro estado
        $estado = $request->get('estado'); // vigente | por_vencer | vencido | sin_fecha

        // ✅ unidades operativas solo admin (dropdown)
        $unidadesOperativas = collect();
        if ($esAdmin) {
            $unidadesOperativas = UnidadOperativa::orderBy('nombre')->get();
        } else {
            // no-admin: forzar su unidad
            $uoId = (int) $user->unidad_operativa_id;
        }

        // ✅ almacenes permitidos por rol
        $almacenesQuery = $this->allowedAlmacenesQuery();

        // ✅ admin: si eligió unidad, reduce almacenes a esa unidad
        if ($esAdmin && $uoId) {
            $almacenesQuery->where('unidad_id', (int)$uoId);
        }

        $almacenes = $almacenesQuery->get();
        $allowedIds = $almacenes->pluck('id');

        if ($almacenId) {
            $this->assertAlmacenAllowed((int)$almacenId);
        }

        $query = InventarioCaducidad::with(['producto', 'almacen', 'inventario'])
            ->whereIn('almacen_id', $allowedIds);

        if ($almacenId) {
            $query->where('almacen_id', (int)$almacenId);
        }

        // ✅ filtro por estado
        // Definición:
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
