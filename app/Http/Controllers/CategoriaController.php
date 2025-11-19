<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;

class CategoriaController extends Controller
{
    // ===============================
    // MOSTRAR FORMULARIO DE CREAR
    // ===============================
    public function crear()
{
    return view('dashboard.agregar_categoria');
}


    // ===============================
    // GUARDAR CATEGORÍA
    // ===============================
    public function guardar(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|boolean'
        ]);

        Categoria::create([
            'nombre'       => $request->nombre,
            'descripcion'  => $request->descripcion,
            'estado'       => $request->estado
        ]);

        return redirect()
            ->route('dashboard.productos')
            ->with('success', 'Categoría creada correctamente.');
    }
}
