<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminPedidoController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = Pedido::query()->orderBy('created_at', 'desc');

        // ✅ CEO solo ve preaprobados (o en revisión si quieres)
        if ($user->role === 'ceo') {
            $query->whereIn('estado', ['Preaprobado']);
        }

        // ✅ otros roles si entran aquí, pueden ver solo los suyos
        // (si quieres que no entren, mejor pon middleware)
        if (!in_array($user->role, ['admin','ceo'])) {
            $query->where('user_id', $user->id);
        }

        $pedidos = $query->get();
        return view('dashboard.administrar_pedidos', compact('pedidos'));
    }

    public function detalle($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->with([
                'usuario',
                'detalles.productoProveedor.producto.categoria',
                'detalles.productoProveedor.proveedor'
            ])
            ->firstOrFail();

        // pedido especial
        $pedidoEspecial = \App\Models\PedidoEspecial::where('codigo', $codigo)->first();

        // ✅ Permisos de lectura (casi todos)
        $user = auth()->user();

        $puedeVer = false;

        // Admin y CEO siempre
        if (in_array($user->role, ['admin','ceo'])) $puedeVer = true;

        // Solicitante siempre
        if ($pedido->user_id === $user->id) $puedeVer = true;

        // Proveedor/Almacenista solo si Aprobado (recomendado)
        if (in_array($user->role, ['proveedor','almacenista']) && $pedido->estado === 'Aprobado') {
            $puedeVer = true;
        }

        // Encargados pueden ver los suyos (ya cubierto)
        if (!$puedeVer) abort(403, 'No tienes permiso para ver este pedido.');

        return view('dashboard.detalle_pedido', compact('pedido', 'pedidoEspecial'));
    }

    public function editar($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        // ✅ SOLO editable si está Pendiente
        abort_if(
            in_array($pedido->estado, ['Preaprobado', 'Aprobado']),
            403,
            'Este pedido ya no puede ser editado.'
        );

        $detalles = DetallePedido::with([
            'productoProveedor.producto.categoria',
            'productoProveedor.proveedor'
        ])->where('codigo', $codigo)->get();

        $productos = Producto::with([
            'categoria',
            'proveedores' => function ($q) {
                $q->select('proveedores.id', 'nombre')->withPivot('id', 'precio');
            }
        ])->get();

        $itemsPedido = $detalles->map(function ($d) {
            $pp   = $d->productoProveedor;
            $prod = $pp->producto;
            $prov = $pp->proveedor;

            return [
                'producto_proveedor_id' => $pp->id,
                'producto_id'           => $prod->id,
                'proveedor_id'          => $prov->id,
                'nombre'                => $prod->nombre,
                'categoria'             => $prod->categoria->nombre ?? '',
                'unidad'                => $prod->unidad_medida ?? '',
                'proveedor'             => $prov->nombre,
                'precio'                => (float) $d->precio_unitario,
                'cantidad'              => (float) $d->cantidad_solicitada,
                'subtotal'              => (float) $d->subtotal,
            ];
        });

        return view('dashboard.editar_admin_pedido', compact('pedido', 'productos', 'itemsPedido'));
    }

    public function actualizar(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        // ✅ SOLO editable si Pendiente
        abort_if($pedido->estado !== 'Pendiente' | 'Visto' , 403, 'Solo se puede editar si el pedido está Pendiente.');

        $itemsJson = $request->input('items_json');
        if (!$itemsJson) return back()->with('error', 'No se recibieron productos.');

        $items = json_decode($itemsJson, true);
        if (!is_array($items) || empty($items)) return back()->with('error', 'Formato inválido.');

        DB::transaction(function () use ($items, $pedido, $codigo) {

            DetallePedido::where('codigo', $codigo)->delete();

            $total = 0;

            foreach ($items as $item) {
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precio   = (float) ($item['precio'] ?? 0);
                $ppId     = $item['producto_proveedor_id'] ?? null;

                if ($cantidad <= 0 || !$ppId) continue;

                $subtotal = $cantidad * $precio;

                DetallePedido::create([
                    'codigo'                => $codigo,
                    'producto_proveedor_id' => $ppId,
                    'precio_unitario'       => $precio,
                    'cantidad_solicitada'   => $cantidad,
                    'subtotal'              => $subtotal,
                ]);

                $total += $subtotal;
            }

            $pedido->update(['total' => $total]);
        });

        return redirect()->route('dashboard.pedidos.admin')->with('success', 'Pedido actualizado.');
    }

    /**
     * ✅ SOLO ADMIN: Pendiente->Visto / Visto->Preaprobado / En revision->Preaprobado
     */
    public function cambiarEstado(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        $request->validate([
            'estado' => 'required|string'
        ]);

        $nuevo = $request->estado;
        $actual = $pedido->estado ?? 'Pendiente';

        // Reglas admin
        $permitidos = [
            'Pendiente'   => ['Visto'],
            'Visto'       => ['Preaprobado'],
            'En revision' => ['Preaprobado'],
        ];

        /*abort_if(!isset($permitidos[$actual]) || !in_array($nuevo, $permitidos[$actual]), 422,
            "Transición no válida: {$actual} → {$nuevo}"
        );*/

        // Si pasa a Preaprobado, registra quién
        $data = ['estado' => $nuevo];

        if ($nuevo === 'Preaprobado') {
            $data['preaprobado_por'] = auth()->id();
        }

        // Si lo mueve a Visto, no borres preaprobado_por (por si re-ingresa)
        $pedido->update($data);

        return back()->with('success', 'Estado actualizado.');
    }

    /**
     * ✅ SOLO CEO: Preaprobado -> (Aprobado | Rechazado | En revision)
     */
    public function decisionCEO(Request $request, $codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

        $request->validate([
            'decision' => 'required|in:Aprobado,Rechazado,En revision',
            'observaciones' => 'nullable|string|max:2000',
        ]);

        $actual = $pedido->estado ?? 'Pendiente';

        abort_if($actual !== 'Preaprobado', 422, 'El CEO solo puede decidir cuando está Preaprobado.');

        $decision = $request->decision;

        $data = [
            'estado' => $decision,
            'observaciones' => $request->observaciones,
        ];

        if ($decision === 'Aprobado') {
            $data['aprobado_por'] = auth()->id();
        }

        $pedido->update($data);

        return back()->with('success', 'Decisión registrada.');
    }

    public function generarPDF($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $detalles = DetallePedido::where('codigo', $codigo)->get();

        $pdf = Pdf::loadView('dashboard.pedido_pdf', compact('pedido', 'detalles'));
        return $pdf->stream("Pedido_{$pedido->codigo}.pdf");
    }

    public function indexCeo()
    {
        $pedidos = Pedido::whereIn('estado', ['Preaprobado']) // ajusta estados si quieres
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.administrar_pedidos', compact('pedidos'));
    }

    public function detalleCeo($codigo)
    {
        // Reusa tu mismo detalle
        return $this->detalle($codigo);
    }

    public function cambiarEstadoCeo(Request $request, $codigo)
{
    $pedido = Pedido::where('codigo', $codigo)->firstOrFail();

    // OJO: aquí el name del botón debe ser "decision"
    $decision = trim((string) $request->input('decision'));
    $obs = trim((string) $request->input('observaciones'));

    switch ($decision) {
        case 'Aprobado':
            $pedido->estado = 'Aprobado';
            $pedido->observaciones = null;
            break;

        case 'Rechazado':
            $pedido->estado = 'Rechazado';
            $pedido->observaciones = $obs ?: 'Rechazado por CEO';
            break;

        case 'En revision':
            $pedido->estado = 'En revision';
            $pedido->observaciones = $obs ?: 'En revisión por CEO';
            break;

        case 'Desaprobado':
            // si ya te funciona este, déjalo igual
            $pedido->estado = 'Preaprobado'; // o el estado al que regresas
            $pedido->observaciones = $obs ?: null;
            break;

        default:
            return back()->with('error', "Decisión inválida: {$decision}");
    }

    $pedido->save();

    return back()->with('success', 'Decisión aplicada correctamente.');
}


}
