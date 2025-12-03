<?php

namespace App\Http\Controllers;

use App\Models\UnidadOperativa;
use Illuminate\Http\Request;

class UnidadOperativaController extends Controller
{
    public function index()
    {
        $unidades = UnidadOperativa::with('responsable')->orderBy('nombre')->get();
        return view('unidades.index', compact('unidades'));
    }

    public function create()
    {
        return view('unidades.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'tipo' => 'nullable',
            'ubicacion' => 'nullable',
        ]);

        UnidadOperativa::create($request->all());

        return redirect()->route('unidades.index')
            ->with('success', 'Unidad operativa creada correctamente.');
    }

    public function edit($id)
    {
        $unidad = UnidadOperativa::findOrFail($id);
        return view('unidades.edit', compact('unidad'));
    }

    public function update(Request $request, $id)
    {
        $unidad = UnidadOperativa::findOrFail($id);

        $request->validate([
            'nombre' => 'required',
        ]);

        $unidad->update($request->all());

        return redirect()->route('unidades.index')
            ->with('success', 'Unidad actualizada.');
    }

    public function destroy($id)
    {
        UnidadOperativa::destroy($id);
        return redirect()->route('unidades.index')
            ->with('success', 'Unidad eliminada.');
    }
}
