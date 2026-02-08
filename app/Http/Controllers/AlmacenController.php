<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\UnidadOperativa;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    private function esAdmin(): bool
    {
        return (auth()->user()->role ?? '') === 'admin';
    }

    private function esCedisSolicitado(Request $request): bool
    {
        $tipo = mb_strtolower(trim((string) $request->input('tipo', '')));
        return $request->boolean('es_cedis') || $tipo === 'cedis';
    }

    // Mostrar almacenes por unidad operativa
    public function index($unidad_id)
    {
        $unidad = UnidadOperativa::findOrFail($unidad_id);
        $almacenesQuery = Almacen::where('unidad_id', $unidad_id);

        if (!$this->esAdmin()) {
            $almacenesQuery
                ->where(function ($q) {
                    $q->whereNull('es_cedis')
                        ->orWhere('es_cedis', false);
                })
                ->whereRaw("LOWER(COALESCE(tipo, '')) <> 'cedis'");
        }

        $almacenes = $almacenesQuery->get();

        return view('almacenes.index', compact('unidad', 'almacenes'));
    }

    // Formulario crear
    public function create($unidad_id)
    {
        $unidad = UnidadOperativa::findOrFail($unidad_id);
        return view('almacenes.create', compact('unidad'));
    }

    // Guardar nuevo almacen
    public function store(Request $request, $unidad_id)
    {
        $request->validate([
            'nombre' => 'required',
            'tipo' => 'required',
            'es_cedis' => 'nullable|boolean',
        ]);

        if (!$this->esAdmin() && $this->esCedisSolicitado($request)) {
            abort(403, 'Solo administradores pueden registrar almacenes CEDIS.');
        }

        $esCedis = $this->esAdmin() && $this->esCedisSolicitado($request);

        Almacen::create([
            'nombre' => $request->nombre,
            'tipo' => $esCedis ? 'cedis' : $request->tipo,
            'ubicacion' => $request->ubicacion,
            'unidad_id' => $unidad_id,
            'es_cedis' => $esCedis,
        ]);

        return redirect()->route('almacenes.index', $unidad_id)
            ->with('success', 'Almacen registrado correctamente.');
    }

    // Editar
    public function edit($unidad_id, $almacen_id)
    {
        $unidad = UnidadOperativa::findOrFail($unidad_id);
        $almacen = Almacen::findOrFail($almacen_id);

        if (!$this->esAdmin() && $almacen->isCedis()) {
            abort(403, 'No tienes permiso para ver este almacen.');
        }

        return view('almacenes.edit', compact('unidad', 'almacen'));
    }

    // Actualizar
    public function update(Request $request, $unidad_id, $almacen_id)
    {
        $almacen = Almacen::findOrFail($almacen_id);

        $request->validate([
            'nombre' => 'required',
            'tipo' => 'required',
            'es_cedis' => 'nullable|boolean',
        ]);

        if (!$this->esAdmin() && ($almacen->isCedis() || $this->esCedisSolicitado($request))) {
            abort(403, 'Solo administradores pueden actualizar almacenes CEDIS.');
        }

        $esCedis = $this->esAdmin() && $this->esCedisSolicitado($request);

        $almacen->update([
            'nombre' => $request->nombre,
            'tipo' => $esCedis ? 'cedis' : $request->tipo,
            'ubicacion' => $request->ubicacion,
            'es_cedis' => $esCedis,
        ]);

        return redirect()->route('almacenes.index', $unidad_id)
            ->with('success', 'Almacen actualizado.');
    }

    // Eliminar
    public function destroy($unidad_id, $almacen_id)
    {
        $almacen = Almacen::findOrFail($almacen_id);

        if (!$this->esAdmin() && $almacen->isCedis()) {
            abort(403, 'Solo administradores pueden eliminar almacenes CEDIS.');
        }

        Almacen::destroy($almacen_id);

        return redirect()->route('almacenes.index', $unidad_id)
            ->with('success', 'Almacen eliminado.');
    }
}
