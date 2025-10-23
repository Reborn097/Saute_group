<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductoProveedor;
use App\Models\HistorialPrecio;

class PrecioController extends Controller
{
    /**
     * Mostrar lista de precios.
     * Admin ve todo; proveedor solo ve su catálogo.
     */
    public function index()
    {
        $query = ProductoProveedor::with('producto.categoria', 'proveedor');

        if (Auth::user()->rol === 'proveedor') {
            $query->where('proveedor_id', Auth::user()->proveedor_id);
        }

        // Búsqueda por nombre de producto
        if (request()->filled('q')) {
            $q = request('q');
            $query->whereHas('producto', function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%$q%");
            });
        }

        $relaciones = $query->orderBy('id', 'desc')->paginate(10);

        return view('dashboard.precios.index', compact('relaciones'));
    }

    /**
     * Formulario para editar un precio.
     */
    public function editar($id)
    {
        $relacion = ProductoProveedor::with('producto', 'proveedor')->findOrFail($id);

        // 🔒 Proveedor solo puede editar los suyos
        if (Auth::user()->rol === 'proveedor' && Auth::user()->proveedor_id != $relacion->proveedor_id) {
            abort(403, 'No tienes permiso para modificar este producto.');
        }

        return view('dashboard.precios.editar', compact('relacion'));
    }

    /**
     * Actualizar precio y registrar historial.
     */
    public function actualizar(Request $request, $id)
    {
        $relacion = ProductoProveedor::findOrFail($id);

        //  Verificación de permisos
        if (Auth::user()->rol === 'proveedor' && Auth::user()->proveedor_id != $relacion->proveedor_id) {
            abort(403, 'No tienes permiso para modificar este producto.');
        }

        //  Validación de campos
        $request->validate([
            'precio' => 'required|numeric|min:0',
            'fecha_vigencia_inicio' => 'required|date',
            'fecha_vigencia_final' => 'nullable|date|after_or_equal:fecha_vigencia_inicio',
        ]);

        //  Registrar historial antes de actualizar
        HistorialPrecio::create([
            'producto_proveedor_id' => $relacion->id,
            'precio' => $relacion->precio,
            'fecha_vigencia_inicio' => $relacion->fecha_vigencia_inicio,
            'fecha_vigencia_final' => $relacion->fecha_vigencia_final ?? now(),
        ]);

        //  Actualizar nuevo precio
        $relacion->update([
            'precio' => $request->precio,
            'fecha_vigencia_inicio' => $request->fecha_vigencia_inicio,
            'fecha_vigencia_final' => $request->fecha_vigencia_final,
        ]);

        return redirect()->route('dashboard.precios')->with('success', 'Precio actualizado correctamente.');
    }
}
