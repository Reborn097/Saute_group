<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\PedidoEspecial;
use App\Models\Categoria;
use App\Models\Proveedor;
use App\Models\ProductoPresentacion;

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

    public function reportePorProveedor(Request $request)
    {
        $role = Auth::user()->role ?? '';

        $hoy   = now()->toDateString();
        $desde = $request->get('desde', now()->subDays(7)->toDateString());
        $hasta = $request->get('hasta', $hoy);

        $q = DetallePedido::query()
            ->join('pedidos', 'detalle_pedidos.codigo', '=', 'pedidos.codigo')
            ->join('presentacion_proveedor', 'detalle_pedidos.producto_proveedor_id', '=', 'presentacion_proveedor.id')
            ->join('producto_presentaciones', 'presentacion_proveedor.presentacion_id', '=', 'producto_presentaciones.id')
            ->join('productos', 'producto_presentaciones.producto_id', '=', 'productos.id')
            ->join('proveedores', 'presentacion_proveedor.proveedor_id', '=', 'proveedores.id')
            ->join('unidades_operativas', 'pedidos.unidad_operativa_id', '=', 'unidades_operativas.id')
            ->whereDate('pedidos.fecha_solicitud', '>=', $desde)
            ->whereDate('pedidos.fecha_solicitud', '<=', $hasta);



        // ✅ Si NO es staff, solo sus pedidos
        if (!$this->esStaffPedidos($role)) {
            $q->where('pedidos.user_id', Auth::id());
        }

        // ✅ CEO: solo ciertos estados
        if ($role === 'ceo') {
            $q->whereIn('pedidos.estado', ['Preaprobado', 'Aprobado']);
        }

        // ✅ Consolidado por producto
        $rows = $q->select([
                'proveedores.id as proveedor_id',
                'proveedores.nombre as proveedor',
                'unidades_operativas.id as uo_id',
                'unidades_operativas.nombre as unidad_operativa',
                'productos.nombre as producto',
                'producto_presentaciones.unidad_contenido as unidad_medida',
                DB::raw('SUM(COALESCE(detalle_pedidos.cantidad_aprobada, detalle_pedidos.cantidad_solicitada)) as cantidad_total'),
            ])
            ->groupBy(
                'proveedores.id', 'proveedores.nombre',
                'unidades_operativas.id', 'unidades_operativas.nombre',
                'productos.nombre',
                'producto_presentaciones.unidad_contenido'
            )
            ->orderBy('proveedores.nombre')
            ->orderBy('unidades_operativas.nombre')
            ->orderBy('productos.nombre')
            ->get();

        // ✅ Estructura: proveedor -> unidad -> items
        $agrupado = [];
        foreach ($rows as $r) {
            $pid = $r->proveedor_id;
            $uid = $r->uo_id;

            $agrupado[$pid]['proveedor'] ??= $r->proveedor;
            $agrupado[$pid]['unidades'][$uid]['unidad_operativa'] ??= $r->unidad_operativa;

            $agrupado[$pid]['unidades'][$uid]['items'][] = [
                'cantidad' => rtrim(rtrim(number_format((float)$r->cantidad_total, 2, '.', ''), '0'), '.'),
                'unidad_medida' => $r->unidad_medida,
                'producto' => $r->producto,
            ];
        }

        return view('dashboard.reporte_pedidos_proveedor', compact('desde', 'hasta', 'agrupado'));
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
                'detalles.presentacion.producto.categoria',
                'detalles.presentacion.proveedores.proveedor'
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
            'presentacion.producto.categoria',
            'presentacion.proveedores.proveedor'
        ])->where('codigo', $codigo)->get();

        // ✅ Items del pedido para JS
        $itemsPedido = $detalles->map(function ($d) use ($esAdminPedidos) {
            $pres = $d->presentacion;
            $prod = $pres?->producto;
            $provRel = $pres?->proveedores ?? collect();
            $primProv = $provRel->first();
            $prov = $primProv?->proveedor;

            $descPresenta = $pres?->descripcion ?? '';
            $contenido = $pres?->contenido ?? null;
            $descContenido = $descPresenta;
            if ($contenido !== null && $contenido !== '') {
                $descContenido = trim($descPresenta) . ' - ' . $contenido;
            }

            return [
                'detalle_id'            => (int)$d->id,
                'presentacion_id'       => (int)($pres?->id ?? 0),
                'producto_proveedor_id' => (int)($d->producto_proveedor_id ?? ($primProv?->id ?? 0)),
                'producto_id'           => (int)($prod?->id ?? 0),

                // proveedor solo visible para staff
                'proveedor_id'          => $esAdminPedidos ? (int)($prov?->id ?? 0) : null,
                'proveedor'             => $esAdminPedidos ? ($prov->nombre ?? '') : 'Proveedor asignado',

                'producto'              => $prod->nombre ?? '',
                'marca'                 => $prod->marca ?? '',
                'categoria'             => $prod?->categoria?->nombre ?? '',
                'descripcion_contenido' => $descContenido,
                'unidad_contenido'      => $pres?->unidad_contenido ?? ($pres?->unidad_base ?? ''),
                'precio'                => (float) ($d->precio_unitario ?? ($primProv?->precio_vigente ?? 0)),

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
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();

        $presentaciones = null;

        if ($esAdminPedidos) {
            $presentacionesQuery = ProductoPresentacion::query()
                ->with([
                    'producto.categoria',
                    'proveedores' => function ($q) {
                        $q->with(['proveedor:id,nombre'])
                          ->select('id','presentacion_id','proveedor_id','precio_vigente','estado')
                          ->where('estado', 1)
                          ->orderByDesc('id');
                    }
                ])
                ->orderBy('producto_id')
                ->orderBy('descripcion');

            if ($q !== '') {
                $presentacionesQuery->where(function ($sub) use ($q) {
                    $sub->whereHas('producto', function ($qp) use ($q) {
                        $qp->where('nombre', 'like', "%{$q}%")
                           ->orWhere('marca', 'like', "%{$q}%");
                    })
                    ->orWhere('descripcion', 'like', "%{$q}%");
                });
            }

            if (!empty($categoriaId)) {
                $presentacionesQuery->whereHas('producto', function ($qp) use ($categoriaId) {
                    $qp->where('categoria_id', $categoriaId);
                });
            }

            if (!empty($proveedorId)) {
                $presentacionesQuery->whereHas('proveedores', function ($sub) use ($proveedorId) {
                    $sub->where('proveedor_id', $proveedorId);
                });
            }

            $presentaciones = $presentacionesQuery->paginate(10)->withQueryString();
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
            'presentaciones',
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
            $detallesActuales = DetallePedido::where('codigo', $codigo)->get()->keyBy('presentacion_id');
            $presIdsActuales = $detallesActuales->keys()->map(fn($x) => (int)$x)->all();

            // IDs que vienen del front
            $presIdsFront = collect($items)
                ->pluck('presentacion_id')
                ->filter()
                ->map(fn($x) => (int)$x)
                ->unique()
                ->values()
                ->all();

            // ✅ NO-admin: NO permitir ppIds nuevos
            if (!$esAdminPedidos) {
                $presIdsFront = array_values(array_intersect($presIdsFront, $presIdsActuales));
            }

            // Si viene vacío, no hagas nada destructivo
            if (empty($presIdsFront)) {
                return;
            }

            // Inactiva los que ya no vienen
            DetallePedido::where('codigo', $codigo)
                ->whereNotIn('presentacion_id', $presIdsFront)
                ->update(['activo' => 0]);

            $total = 0;

            foreach ($items as $it) {

                $presentacionId = isset($it['presentacion_id']) ? (int)$it['presentacion_id'] : 0;
                if (!$presentacionId) continue;

                // ✅ NO-admin: ignora cualquier ID que no exista en el pedido
                if (!$esAdminPedidos && !in_array($presentacionId, $presIdsActuales, true)) {
                    continue;
                }

                $detalle = DetallePedido::where('codigo', $codigo)
                    ->where('presentacion_id', $presentacionId)
                    ->first();

                if (!$detalle) {
                    // Staff sí puede crear; no-admin no
                    if (!$esAdminPedidos) continue;

                    $detalle = new DetallePedido();
                    $detalle->codigo = $codigo;
                }
                $detalle->presentacion_id = $presentacionId;
                $detalle->producto_proveedor_id = isset($it['producto_proveedor_id']) ? (int)$it['producto_proveedor_id'] : null;

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
                'detalles.presentacion.producto.categoria',
                'detalles.presentacion.proveedores.proveedor'
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
