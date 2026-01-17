<?php

namespace App\Http\Controllers;

use App\Models\UnidadOperativa;
use Illuminate\Http\Request;

class UnidadOperativaController extends Controller
{
    public function index(Request $request)
    {
        $q    = trim((string) $request->get('q', ''));
        $tipo = trim((string) $request->get('tipo', ''));

        // Para llenar el select de tipos (valores únicos)
        $tipos = UnidadOperativa::query()
            ->select('tipo')
            ->whereNotNull('tipo')
            ->where('tipo', '<>', '')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo');

        $query = UnidadOperativa::with('responsable');

        // Buscar (q)
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('ubicacion', 'like', "%{$q}%")
                    ->orWhere('tipo', 'like', "%{$q}%");
            });
        }

        // Filtro por tipo (exact match)
        if ($tipo !== '') {
            $query->where('tipo', $tipo);
        }

        // Orden ASC (para que no salga descendente)
        $unidades = $query
            ->orderBy('id', 'asc')   // o ->orderBy('nombre','asc')
            ->get();

        // 👇 Ajusta el view según dónde está tu blade
        // Si tu blade está en resources/views/dashboard/unidades/index.blade.php:
        return view('unidades.index', compact('unidades', 'tipos'));

        // Si tu blade está en resources/views/unidades/index.blade.php, usa:
        // return view('unidades.index', compact('unidades', 'tipos'));
    }

    public function create()
    {
        return view('unidades.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:255',
            'tipo'      => 'nullable|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        UnidadOperativa::create($request->only(['nombre','tipo','ubicacion']));

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
            'nombre'    => 'required|string|max:255',
            'tipo'      => 'nullable|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
        ]);

        $unidad->update($request->only(['nombre','tipo','ubicacion']));

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
