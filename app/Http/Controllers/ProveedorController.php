<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Proveedor;
use App\Models\ProveedorTarjeta;

class ProveedorController extends Controller
{
    // ✅ LISTA DE PROVEEDORES (route name: dashboard.proveedores)
    public function index()
    {
        $proveedores = Proveedor::orderBy('id', 'desc')->paginate(10);
        return view('dashboard.admin_proveedor', compact('proveedores'));
    }

    // ✅ FORM CREAR PROVEEDOR (usa tarjetas en sesión)
    public function crear()
    {
        $proveedorDraft = session('proveedor_draft', []);
        $tarjetasDraft  = session('proveedor_tarjetas_draft', []);

        return view('dashboard.agregar_proveedor', compact('proveedorDraft', 'tarjetasDraft'));
    }

    // ✅ GUARDAR PROVEEDOR (crea proveedor + guarda tarjetas draft en BD)
    public function guardar(Request $request)
    {
        $data = $request->validate([
            'nombre'            => 'required|string|max:255',
            'telefono'          => 'nullable|string|max:13',
            'nombre_contacto'   => 'nullable|string|max:255',
            'telefono_contacto' => 'nullable|string|max:13',
            'codigo_postal'     => 'required|string|max:5',
            'colonia'           => 'required|string|max:255',
            'calle'             => 'nullable|string|max:255',
            'num_direccion'     => 'nullable|string|max:5',
            'rfc'               => 'nullable|string|max:13',
        ]);

        session(['proveedor_draft' => $data]);
        $tarjetasDraft = session('proveedor_tarjetas_draft', []);

        DB::transaction(function () use ($data, $tarjetasDraft) {

            $proveedor = Proveedor::create($data);

            foreach ($tarjetasDraft as $t) {
                $tieneAlgo =
                    !empty($t['clabe']) || !empty($t['cuenta']) || !empty($t['tarjeta']) ||
                    !empty($t['banco']) || !empty($t['titular']) || !empty($t['alias']);

                if (!$tieneAlgo) continue;

                ProveedorTarjeta::create([
                    'proveedor_id' => $proveedor->id,
                    'tipo'         => $t['tipo'] ?? 'empresa',
                    'alias'        => $t['alias'] ?? null,
                    'banco'        => $t['banco'] ?? null,
                    'titular'      => $t['titular'] ?? null,
                    'clabe'        => $t['clabe'] ?? null,
                    'cuenta'       => $t['cuenta'] ?? null,
                    'tarjeta'      => $t['tarjeta'] ?? null,
                    'activa'       => 1,
                ]);
            }
        });

        session()->forget('proveedor_draft');
        session()->forget('proveedor_tarjetas_draft');

        return redirect()->route('dashboard.proveedores')
            ->with('success', 'Proveedor guardado correctamente.');
    }

    // =============================================
    // ✅ TARJETAS (FLUJO EN SESIÓN) - SOLO CREAR
    // =============================================
    public function tarjetaCrear(Request $request)
    {
        $prev = session('proveedor_draft', []);
        $incoming = $request->except(['_token']);

        if (!empty(array_filter($incoming, fn($v) => $v !== null && $v !== ''))) {
            session(['proveedor_draft' => array_merge($prev, $incoming)]);
        }

        return view('dashboard.tarjeta_crear');
    }

    public function tarjetaGuardar(Request $request)
    {
        $t = $request->validate([
            'tipo'    => 'required|in:empresa,contacto',
            'alias'   => 'nullable|string|max:80',
            'banco'   => 'nullable|string|max:80',
            'titular' => 'nullable|string|max:120',
            'clabe'   => 'nullable|string|max:18',
            'cuenta'  => 'nullable|string|max:20',
            'tarjeta' => 'nullable|string|max:20',
        ]);

        $tarjetas = session('proveedor_tarjetas_draft', []);
        $tarjetas[] = $t;
        session(['proveedor_tarjetas_draft' => $tarjetas]);

        return redirect()->route('dashboard.proveedores.crear')
            ->with('success', 'Datos de tarjeta agregados. Ahora guarda el proveedor para registrarlo.');
    }

    public function tarjetaEliminar(Request $request)
    {
        $request->validate(['index' => 'required|integer|min:0']);

        $tarjetas = session('proveedor_tarjetas_draft', []);
        $i = (int) $request->index;

        if (isset($tarjetas[$i])) {
            unset($tarjetas[$i]);
            session(['proveedor_tarjetas_draft' => array_values($tarjetas)]);
        }

        return back()->with('success', 'Tarjeta eliminada.');
    }

    // =============================================
    // ✅ EDITAR PROVEEDOR + TARJETAS REALES (BD)
    // =============================================
    public function editar($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $tarjetas = ProveedorTarjeta::where('proveedor_id', $proveedor->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('dashboard.editar_proveedor', compact('proveedor', 'tarjetas'));
    }

    public function actualizar(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $data = $request->validate([
            'nombre'            => 'required|string|max:255',
            'telefono'          => 'nullable|string|max:13',
            'nombre_contacto'   => 'nullable|string|max:255',
            'telefono_contacto' => 'nullable|string|max:13',
            'codigo_postal'     => 'required|string|max:5',
            'colonia'           => 'required|string|max:255',
            'calle'             => 'nullable|string|max:255',
            'num_direccion'     => 'nullable|string|max:5',
            'rfc'               => 'nullable|string|max:13',
        ]);

        $proveedor->update($data);

        return redirect()->route('dashboard.proveedores')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    // =============================================
    // ✅ CRUD TARJETAS REALES (BD) - PARA EDITAR
    // RUTAS:
    // POST   /dashboard/proveedores/{id}/tarjetas
    // PUT    /dashboard/proveedores/tarjetas/{tarjetaId}
    // DELETE /dashboard/proveedores/tarjetas/{tarjetaId}
    // =============================================
    public function tarjetaStore(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $data = $request->validate([
            'tipo'    => 'required|in:empresa,contacto',
            'alias'   => 'nullable|string|max:80',
            'banco'   => 'nullable|string|max:80',
            'titular' => 'nullable|string|max:120',
            'clabe'   => 'nullable|string|max:18',
            'cuenta'  => 'nullable|string|max:20',
            'tarjeta' => 'nullable|string|max:20',
            'activa'  => 'nullable|boolean',
        ]);

        $data['activa'] = $request->has('activa') ? 1 : 0;
        $data['proveedor_id'] = $proveedor->id;

        ProveedorTarjeta::create($data);

        return back()->with('success', 'Tarjeta agregada correctamente.');
    }

    public function tarjetaUpdate(Request $request, $tarjetaId)
    {
        $tarjeta = ProveedorTarjeta::findOrFail($tarjetaId);

        $data = $request->validate([
            'tipo'    => 'required|in:empresa,contacto',
            'alias'   => 'nullable|string|max:80',
            'banco'   => 'nullable|string|max:80',
            'titular' => 'nullable|string|max:120',
            'clabe'   => 'nullable|string|max:18',
            'cuenta'  => 'nullable|string|max:20',
            'tarjeta' => 'nullable|string|max:20',
            'activa'  => 'nullable|boolean',
        ]);

        $data['activa'] = $request->has('activa') ? 1 : 0;

        $tarjeta->update($data);

        return back()->with('success', 'Tarjeta actualizada correctamente.');
    }

    public function tarjetaDestroy($tarjetaId)
    {
        $tarjeta = ProveedorTarjeta::findOrFail($tarjetaId);
        $tarjeta->delete();

        return back()->with('success', 'Tarjeta eliminada correctamente.');
    }

    // =============================================
    // ✅ ELIMINAR PROVEEDOR
    // =============================================
    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $tieneTarjetas = ProveedorTarjeta::where('proveedor_id', $proveedor->id)->exists();
        if ($tieneTarjetas) {
            return redirect()->route('dashboard.proveedores')
                ->with('error', 'No se puede eliminar: el proveedor tiene cuentas/tarjetas registradas.');
        }

        $proveedor->delete();

        return redirect()->route('dashboard.proveedores')
            ->with('success', 'Proveedor eliminado correctamente.');
    }

    // =============================================
    // ✅ VISTA CUENTA (SOLO VISUALIZAR)
    // =============================================
    public function cuenta($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $tarjetas = ProveedorTarjeta::where('proveedor_id', $proveedor->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('dashboard.proveedor_cuenta', compact('proveedor', 'tarjetas'));
    }
}
