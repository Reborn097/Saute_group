<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;

class ProveedorController extends Controller
{
    /**
     * Mostrar lista de proveedores
     */
    public function index()
    {
        $proveedores = Proveedor::all();
        return view('dashboard.admin_proveedor', compact('proveedores'));
    }

    /**
     * Mostrar formulario para agregar proveedor
     */
    public function crearProveedor()
    {
        return view('dashboard.agregar_proveedor');
    }

    /**
     * Guardar nuevo proveedor
     */
    public function guardarProveedor(Request $request)
    {
        $request->validate([
            'nombre'             => 'required|string|max:150',
            'nombre_contacto'    => 'nullable|string|max:150',
            'telefono_contacto'  => 'nullable|string|max:20',
            'telefono'           => 'nullable|string|max:20',
            'calle'              => 'nullable|string|max:100',
            'colonia'            => 'nullable|string|max:100',
            'codigo_postal'      => 'nullable|string|max:10',
            'num_direccion'      => 'nullable|string|max:50',
            'rfc'                => 'nullable|string|max:20',
        ]);

        Proveedor::create($request->all());

        return redirect()->route('dashboard.proveedores')
                         ->with('success', 'Proveedor registrado correctamente.');
    }

    /**
     * Mostrar formulario para editar un proveedor
     */
    public function editarProveedor($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        return view('dashboard.editar_proveedor', compact('proveedor'));
    }

    /**
     * Actualizar datos del proveedor
     */
    public function actualizarProveedor(Request $request, $id)
    {
        $request->validate([
            'nombre'             => 'required|string|max:150',
            'nombre_contacto'    => 'nullable|string|max:150',
            'telefono_contacto'  => 'nullable|string|max:20',
            'telefono'           => 'nullable|string|max:20',
            'calle'              => 'nullable|string|max:100',
            'colonia'            => 'nullable|string|max:100',
            'codigo_postal'      => 'nullable|string|max:10',
            'num_direccion'      => 'nullable|string|max:50',
            'rfc'                => 'nullable|string|max:20',
        ]);

        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update($request->all());

        return redirect()->route('dashboard.proveedores')
                         ->with('success', 'Proveedor actualizado correctamente.');
    }

    /**
     * Eliminar un proveedor
     */
    public function destroy($id)
{
    $proveedor = Proveedor::findOrFail($id);

    // Verificar si el proveedor tiene productos asociados
    if ($proveedor->productos()->count() > 0) {
        return redirect()->route('dashboard.proveedores')
            ->with('error', 'No se puede eliminar el proveedor porque tiene productos asociados.');
    }

    // Si no tiene productos, permitir eliminar
    $proveedor->delete();

    return redirect()->route('dashboard.proveedores')
        ->with('success', 'Proveedor eliminado correctamente.');
}

}
