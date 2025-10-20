<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;

class ProveedorController extends Controller
{
    public function crearProveedor()
    {
        // Muestra la vista para agregar un proveedor
        return view('dashboard.agregar_proveedor');
    }

    public function guardarProveedor(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'calle' => 'nullable|string|max:100',
            'colonia' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'num_direccion' => 'nullable|string|max:50',
            'rfc' => 'nullable|string|max:20',
        ]);

        Proveedor::create($request->all());

        return redirect()->route('dashboard.admin')
                         ->with('success', 'Proveedor registrado correctamente.');
    }

    public function index()
    {
        $proveedores = Proveedor::all();
        return view('dashboard.admin_proveedor', compact('proveedores'));
    }


    public function destroy($id)
    {
        $proveedor = \App\Models\Proveedor::findOrFail($id);
        $proveedor->delete();

        return redirect()->route('dashboard.proveedores')
                     ->with('success', 'Proveedor eliminado correctamente.');
    }

}
