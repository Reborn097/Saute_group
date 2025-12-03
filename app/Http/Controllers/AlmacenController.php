<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\UnidadOperativa;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    // Mostrar almacenes por unidad operativa
    public function index($unidad_id)
    {
        $unidad = UnidadOperativa::findOrFail($unidad_id);
        $almacenes = Almacen::where('unidad_id', $unidad_id)->get();

        return view('almacenes.index', compact('unidad', 'almacenes'));
    }

    // Formulario crear
    public function create($unidad_id)
    {
        $unidad = UnidadOperativa::findOrFail($unidad_id);
        return view('almacenes.create', compact('unidad'));
    }

    // Guardar nuevo almacén
    public function store(Request $request, $unidad_id)
    {
        $request->validate([
            'nombre' => 'required',
            'tipo' => 'required',
        ]);

        Almacen::create([
            'nombre' => $request->nombre,
            'tipo' => $request->tipo,
            'ubicacion' => $request->ubicacion,
            'unidad_id' => $unidad_id
        ]);

        return redirect()->route('almacenes.index', $unidad_id)
            ->with('success', 'Almacén registrado correctamente.');
    }

    // Editar
    public function edit($unidad_id, $almacen_id)
    {
        $unidad = UnidadOperativa::findOrFail($unidad_id);
        $almacen = Almacen::findOrFail($almacen_id);

        return view('almacenes.edit', compact('unidad', 'almacen'));
    }

    // Actualizar
    public function update(Request $request, $unidad_id, $almacen_id)
    {
        $almacen = Almacen::findOrFail($almacen_id);

        $request->validate([
            'nombre' => 'required',
            'tipo' => 'required',
        ]);

        $almacen->update($request->all());

        return redirect()->route('almacenes.index', $unidad_id)
            ->with('success', 'Almacén actualizado.');
    }

    // Eliminar
    public function destroy($unidad_id, $almacen_id)
    {
        Almacen::destroy($almacen_id);

        return redirect()->route('almacenes.index', $unidad_id)
            ->with('success', 'Almacén eliminado.');
    }
}
