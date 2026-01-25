<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\PedidoEspecial;
use App\Models\Categoria;
use App\Models\Proveedor;

use Barryvdh\DomPDF\Facade\Pdf;

class AdminPedidoController extends Controller
{
    // ==========================
    // Constantes de estados (consistentes)
    // ==========================
    private const ESTADOS_FINALES = ['Preaprobado', 'Aprobado', 'Cancelado'];

    /**
     * Roles
     */
    private function esStaffPedidos(string $role): bool
    {
        // quienes administran/revisan pedidos
        return in_array($role, ['admin', 'encargado_pedidos', 'ceo'], true);
    }

    private function esOperativoPedidos(string $role): bool
    {
        // no-admin que sí pueden editar mientras NO esté "Visto"
        return in_array($role, ['encargado_cocina', 'encargado_cafeteria'], true);
    }

    /**
     * Permisos de edición de PDFs de pedido especial (por estado + rol)
     * Regla:
     * - encargado_cocina / encargado_cafeteria: solo Pendiente
     * - admin / encargado_pedidos: Pendiente o Visto
     * - otros: no
     */
    private function puedeEditarPDFsEspecial(string $estado, string $role): bool
    {
        if (in_array($estado, ['Preaprobado', 'Aprobado', 'Cancelado'], true)) {
            return false;
        }

        if ($estado === 'Pendiente') {
            return in_array($role, ['admin', 'encargado_pedidos', 'encargado_cocina', 'encargado_cafeteria'], true);
        }

        if ($estado === 'Visto') {
            return in_array($role, ['admin', 'encargado_pedidos'], true);
        }

        return false;
    }

    /**
     * Listado de pedidos
     * - Staff (admin/encargado_pedidos): todos
     * - CEO: solo Preaprobado / Aprobado
     * - Operativo (encargado_cocina / encargado_cafeteria): SOLO SUS PEDIDOS
     * - Otros roles: SOLO SUS PEDIDOS
     */
    public function index(Request $request)
    {
        $role = Auth::user()->role ?? '';

        $hoy   = now()->toDateString();
        $desde = $request->get('desde', now()->subDays(7)->toDateString());
        $hasta = $request->get('hasta', $hoy);

        $codigo = trim((string) $request->get('codigo', ''));

        $query = Pedido::with('usuario');

        // ✅ Si NO es staff, solo sus pedidos
        if (!$this->esStaffPedidos($role)) {
            $query->where('user_id', Auth::id());
        }

        // ✅ CEO: solo ciertos estados (consistente con tus estados actuales)
        if ($role === 'ceo') {
            $query->whereIn('estado', ['Preaprobado', 'Aprobado']);
        }

        // ✅ Filtro por estado (si viene)
        if ($request->filled('estado')) {
            $estado = $request->estado;

            // Si es CEO, no lo dejes filtrar a estados fuera de su alcance
            if ($role === 'ceo' && !in_array($estado, ['Preaprobado', 'Aprobado'], true)) {
                // ignora el filtro inválido para CEO (o podrías regresar error)
            } else {
                $query->where('estado', $estado);
            }
        }

        // ✅ Rango de fechas (incluyente)
        $query->whereDate('fecha_solicitud', '>=', $desde)
              ->whereDate('fecha_solicitud', '<=', $hasta);

        // ✅ Buscar por código (parcial)
        if ($codigo !== '') {
            $query->where('codigo', 'like', "%{$codigo}%");
        }

        $pedidos = $query
            ->orderBy('codigo', 'desc')
            ->paginate(10)
            ->appends($request->query());

        return view('dashboard.administrar_pedidos', compact('pedidos', 'desde', 'hasta', 'codigo'));
    }

    /**
     * Detalle (solo lectura)
     * - Staff: cualquiera
     * - Operativos/otros: SOLO si es suyo
     */
    public function detalle($codigo)
    {
        $role = Auth::user()->role ?? '';

        $pedido = Pedido::where('codigo', $codigo)
            ->with([
                'usuario',
                'detalles.productoProveedor.producto.categoria',
                'detalles.productoProveedor.proveedor'
            ])
            ->firstOrFail();

        // ✅ No staff: solo su pedido
        if (!$this->esStaffPedidos($role) && (int)$pedido->user_id !== (int)Auth::id()) {
            abort(403);
        }

        // ✅ Si es especial, carga su registro
        $pedidoEspecial = null;
        if ((int)$pedido->es_especial === 1) {
            $pedidoEspecial = PedidoEspecial::where('codigo', $codigo)->first();
        }

        return view('dashboard.detalle_pedido', compact('pedido', 'pedidoEspecial'));
    }

    /**
     * Editar (pantalla tipo crear)
     *
     * ✅ Staff:
     * - Solo si NO está en estados finales (Preaprobado/Aprobado/Cancelado)
     *
     * ✅ Operativo (encargado_cocina/encargado_cafeteria):
     * - Solo si es suyo
     * - Solo si estado = Pendiente
     * - Reutiliza la misma vista, pero SIN poder cambiar proveedor/precio ni agregar productos nuevos
     */
    public function editar(Request $request, $codigo)
    {
        $role = Auth::user()->role ?? '';
        $esAdminPedidos = $this->esStaffPedidos($role);
        $esOperativo = $this->esOperativoPedidos($role);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        // ✅ Permisos base para NO-staff
        if (!$esAdminPedidos) {
            // solo su pedido
            if ((int)$pedido->user_id !== (int)Auth::id()) {
                abort(403);
            }

            // solo operativos pueden editar
            if (!$esOperativo) {
                abort(403);
            }

            // solo si no ha sido visto
            if ($pedido->estado !== 'Pendiente') {
                return redirect()
                    ->route('dashboard.pedidos.admin')
                    ->with('warning', 'Este pedido ya fue visto y ya no puede editarse.');
            }
        }

        // ✅ Bloqueo general (para todos) si ya está en estados finales
        if (in_array($pedido->estado, self::ESTADOS_FINALES, true)) {
            return redirect()
                ->route('dashboard.pedidos.admin')
                ->with('warning', 'Este pedido ya no puede editarse porque está Preaprobado, Aprobado o Cancelado.');
        }

        $detalles = DetallePedido::with([
            'productoProveedor.producto.categoria',
            'productoProveedor.proveedor'
        ])->where('codigo', $codigo)->get();

        // ✅ Items del pedido para JS
        $itemsPedido = $detalles->map(function ($d) use ($esAdminPedidos) {
            $pp   = $d->productoProveedor;
            $prod = $pp->producto;
            $prov = $pp->proveedor;

            return [
                'producto_proveedor_id' => (int)$pp->id,
                'producto_id'           => (int)$prod->id,

                // proveedor solo visible para staff
                'proveedor_id'          => $esAdminPedidos ? (int)$prov->id : null,
                'proveedor'             => $esAdminPedidos ? ($prov->nombre ?? '') : 'Proveedor asignado',

                'nombre'                => $prod->nombre ?? '',
                'marca'                 => $prod->marca ?? '',
                'categoria'             => $prod->categoria->nombre ?? '',
                'unidad'                => $prod->unidad_medida ?? '',
                'precio'                => (float) $d->precio_unitario,

                'cantidad_solicitada'   => (float) $d->cantidad_solicitada,
                'cantidad_aprobada'     => (float) ($d->cantidad_aprobada ?? $d->cantidad_solicitada),

                'activo'                => (int) ($d->activo ?? 1),
                'subtotal'              => (float) $d->subtotal,
            ];
        })->values();

        // ✅ Catálogo solo para staff (para operativos lo dejamos vacío)
        $q           = trim((string) $request->get('q', ''));
        $categoriaId = $request->get('categoria_id');
        $proveedorId = $request->get('proveedor_id');

        $categorias  = Categoria::orderBy('nombre')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();

        $productos = null;

        if ($esAdminPedidos) {
            $productosQuery = Producto::query()
                ->with([
                    'categoria',
                    'proveedores' => function ($q) {
                        $q->select('proveedores.id', 'nombre')
                          ->withPivot('id', 'precio');
                    }
                ])
                ->orderBy('nombre');

            if ($q !== '') {
                $productosQuery->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('marca', 'like', "%{$q}%");
                });
            }

            if (!empty($categoriaId)) {
                $productosQuery->where('categoria_id', $categoriaId);
            }

            if (!empty($proveedorId)) {
                $productosQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                    $sub->where('proveedores.id', $proveedorId);
                });
            }

            $productos = $productosQuery->paginate(10)->withQueryString();
        }

        // ✅ Integración de PedidoEspecial (para mostrar PDFs + permitir reemplazo condicional)
        $pedidoEspecial = null;
        $puedeEditarPDFs = false;

        if ((int)$pedido->es_especial === 1) {
            $pedidoEspecial = PedidoEspecial::where('codigo', $codigo)->first();
            $puedeEditarPDFs = $this->puedeEditarPDFsEspecial($pedido->estado, $role);
        }

        return view('dashboard.editar_admin_pedido', compact(
            'pedido',
            'productos',
            'itemsPedido',
            'categorias',
            'proveedores',
            'esAdminPedidos',
            'pedidoEspecial',
            'puedeEditarPDFs'
        ));
    }

    /**
     * Actualizar pedido
     *
     * ✅ Staff:
     * - tu lógica completa
     *
     * ✅ Operativo:
     * - solo su pedido
     * - solo si estado = Pendiente
     * - NO puede cambiar proveedor ni precio
     * - NO puede agregar productos nuevos
     */
    public function actualizar(Request $request, $codigo)
    {
        $role = Auth::user()->role ?? '';
        $esAdminPedidos = $this->esStaffPedidos($role);
        $esOperativo = $this->esOperativoPedidos($role);

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        // ✅ Permisos para NO-staff
        if (!$esAdminPedidos) {
            if ((int)$pedido->user_id !== (int)Auth::id()) {
                abort(403);
            }
            if (!$esOperativo) {
                abort(403);
            }
            if ($pedido->estado !== 'Pendiente') {
                return redirect()
                    ->route('dashboard.pedidos.admin')
                    ->with('warning', 'Este pedido ya fue visto y ya no puede editarse.');
            }
        }

        // ✅ Bloqueo general estados finales (incluye Cancelado)
        if (in_array($pedido->estado, self::ESTADOS_FINALES, true)) {
            return redirect()
                ->route('dashboard.pedidos.admin')
                ->with('warning', 'Este pedido ya no puede editarse porque está Preaprobado, Aprobado o Cancelado.');
        }

        $itemsJson = $request->input('items_json');
        $items = $itemsJson ? json_decode($itemsJson, true) : [];

        if (!is_array($items)) {
            return back()->with('error', 'El formato de los productos es inválido.');
        }

        DB::transaction(function () use ($pedido, $codigo, $items, $esAdminPedidos) {

            // Detalles actuales (para proteger NO-admin)
            $detallesActuales = DetallePedido::where('codigo', $codigo)->get()->keyBy('producto_proveedor_id');
            $ppIdsActuales = $detallesActuales->keys()->map(fn($x) => (int)$x)->all();

            // IDs que vienen del front
            $ppIdsFront = collect($items)
                ->pluck('producto_proveedor_id')
                ->filter()
                ->map(fn($x) => (int)$x)
                ->unique()
                ->values()
                ->all();

            // ✅ NO-admin: NO permitir ppIds nuevos
            if (!$esAdminPedidos) {
                $ppIdsFront = array_values(array_intersect($ppIdsFront, $ppIdsActuales));
            }

            // Si viene vacío, no hagas nada destructivo
            if (empty($ppIdsFront)) {
                return;
            }

            // Inactiva los que ya no vienen
            DetallePedido::where('codigo', $codigo)
                ->whereNotIn('producto_proveedor_id', $ppIdsFront)
                ->update(['activo' => 0]);

            $total = 0;

            foreach ($items as $it) {

                $ppId = isset($it['producto_proveedor_id']) ? (int)$it['producto_proveedor_id'] : 0;
                if (!$ppId) continue;

                // ✅ NO-admin: ignora cualquier ID que no exista en el pedido
                if (!$esAdminPedidos && !in_array($ppId, $ppIdsActuales, true)) {
                    continue;
                }

                $detalle = DetallePedido::where('codigo', $codigo)
                    ->where('producto_proveedor_id', $ppId)
                    ->first();

                if (!$detalle) {
                    // Staff sí puede crear; no-admin no
                    if (!$esAdminPedidos) continue;

                    $detalle = new DetallePedido();
                    $detalle->codigo = $codigo;
                    $detalle->producto_proveedor_id = $ppId;
                }

                // Activo
                $activo = isset($it['activo'])
                    ? (int)$it['activo']
                    : (isset($it['estado_detalle']) ? (int)$it['estado_detalle'] : 1);

                // cantidades
                $cantSol = array_key_exists('cantidad_solicitada', $it) ? (float)$it['cantidad_solicitada'] : null;
                $cantApr = array_key_exists('cantidad_aprobada', $it) ? (float)$it['cantidad_aprobada'] : null;

                // compat: "cantidad" (tómala como aprobada)
                if ($cantApr === null && array_key_exists('cantidad', $it)) {
                    $cantApr = (float)$it['cantidad'];
                }

                if (!$esAdminPedidos) {
                    // ✅ NO-admin:
                    // - cambia solicitada si viene
                    // - aprobada = solicitada (en Pendiente)
                    // - precio NO se toca
                    if ($cantSol !== null) {
                        $detalle->cantidad_solicitada = $cantSol;
                    }

                    $detalle->cantidad_aprobada = (float)($detalle->cantidad_solicitada ?? 0);
                    $detalle->activo = $activo;

                    $precioBD = (float)($detalle->precio_unitario ?? 0);
                    $detalle->subtotal = ($detalle->activo == 1)
                        ? ($detalle->cantidad_aprobada * $precioBD)
                        : 0;

                    $detalle->save();

                    if ($detalle->activo == 1) {
                        $total += $detalle->subtotal;
                    }

                    continue;
                }

                // ✅ STAFF:
                $precio = isset($it['precio']) ? (float)$it['precio'] : 0;

                // solicitada
                if ($cantSol !== null) {
                    $detalle->cantidad_solicitada = $cantSol;
                } elseif (!isset($detalle->cantidad_solicitada)) {
                    $detalle->cantidad_solicitada = 0;
                }

                $detalle->precio_unitario = $precio;
                $detalle->cantidad_aprobada = $cantApr ?? ($detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada);
                $detalle->activo = $activo;

                $detalle->subtotal = ($detalle->activo == 1)
                    ? ($detalle->cantidad_aprobada * $detalle->precio_unitario)
                    : 0;

                $detalle->save();

                if ($detalle->activo == 1) {
                    $total += $detalle->subtotal;
                }
            }

            $pedido->total = $total;
            $pedido->save();
        });

        return redirect()
            ->route('dashboard.pedidos.admin')
            ->with('success', 'Pedido actualizado correctamente.');
    }

    /**
     * Cambiar estado del pedido (Admin/CEO)
     * Estados válidos (según tu definición actual):
     * Pendiente, Visto, Preaprobado, Aprobado, Cancelado
     */
   
    public function cambiarEstado(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $role   = Auth::user()->role ?? '';

        if (!in_array($role, ['admin', 'ceo'], true)) {
            abort(403);
        }

        $estado = $request->input('estado');

        $permitidosAdmin = ['Pendiente','Visto','Preaprobado','Aprobado','Cancelado'];
        $permitidosCeo   = ['Aprobado','Visto','Cancelado']; // CEO NO descancela

        $permitidos = ($role === 'admin') ? $permitidosAdmin : $permitidosCeo;

        if (!in_array($estado, $permitidos, true)) {
            return back()->with('error', 'Estado no permitido para tu rol.');
        }

        // 1) Si ya está Cancelado:
        // - CEO: no puede hacer nada útil (solo “cancelar” de nuevo) => bloquea
        // - Admin: puede quitar cancelación SOLO regresando a Preaprobado
        if ($pedido->estado === 'Cancelado') {
            if ($role !== 'admin') {
                return back()->with('error', 'Este pedido está cancelado. Solo admin puede quitar cancelación.');
            }
            if (!in_array($estado, ['Preaprobado', 'Cancelado'], true)) {
                return back()->with('error', 'Para quitar cancelación, cambia a Preaprobado.');
            }
        }

        // 2) CEO: aprobar o mandar a revisión SOLO si está Preaprobado
        if ($role === 'ceo' && in_array($estado, ['Aprobado', 'Visto'], true) && $pedido->estado !== 'Preaprobado') {
            return back()->with('error', 'El CEO solo puede aprobar o mandar a revisión pedidos en Preaprobado.');
        }

        // 3) Admin: si quieres que admin también solo apruebe desde Preaprobado
        if ($role === 'admin' && $estado === 'Aprobado' && $pedido->estado !== 'Preaprobado') {
            return back()->with('error', 'Solo puedes aprobar pedidos en Preaprobado.');
        }

        // 4) Cancelar: permitir desde cualquier estado excepto ya Cancelado
        if ($estado === 'Cancelado' && $pedido->estado === 'Cancelado') {
            return back()->with('warning', 'El pedido ya está cancelado.');
        }

        $pedido->estado = $estado;
        $pedido->save();

        return back()->with('success', 'Estado actualizado.');
    }



    /**
     * PDF del pedido
     * - Staff: cualquiera
     * - No-staff: solo si es suyo
     */
    public function generarPDF($codigo)
    {
        $role = Auth::user()->role ?? '';

        $pedido = Pedido::where('codigo', $codigo)
            ->with([
                'usuario',
                'detalles.productoProveedor.producto.categoria',
                'detalles.productoProveedor.proveedor'
            ])
            ->firstOrFail();

        if (!$this->esStaffPedidos($role) && (int)$pedido->user_id !== (int)Auth::id()) {
            abort(403);
        }

        $pdf = Pdf::loadView('dashboard.pedido_pdf', [
            'pedido'   => $pedido,
            'detalles' => $pedido->detalles
        ]);

        return $pdf->stream("Pedido_{$pedido->codigo}.pdf");
    }

    public function indexCeo(Request $request)
{
    if ((auth()->user()->role ?? '') !== 'ceo') {
        abort(403);
    }

    $q = trim((string)$request->get('q', ''));

    $query = \App\Models\Pedido::query()
        ->with(['usuario'])
        ->where('estado', 'Preaprobado')
        ->orderByDesc('created_at');

    if ($q !== '') {
        $query->where(function($sub) use ($q){
            $sub->where('codigo', 'like', "%{$q}%")
                ->orWhereHas('usuario', function($u) use ($q){
                    $u->where('name', 'like', "%{$q}%");
                });
        });
    }

    $pedidos = $query->paginate(10)->appends($request->query());

    return view('dashboard.pedidos_ceo', compact('pedidos', 'q'));
}

}
