<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\UnidadOperativa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private const ROLES_CON_UNIDAD_UNICA = ['encargado_cocina', 'encargado_cafeteria', 'almacenista'];

    public function index()
    {
        $usuarios = User::with(['unidad', 'unidadesAsignadas'])->get();
        return view('dashboard.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $unidades = UnidadOperativa::all();
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();
        return view('dashboard.usuarios.create', compact('unidades', 'proveedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:60|alpha_dash|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string',
            'unidad_operativa_id' => 'nullable|exists:unidades_operativas,id',
            'unidad_operativa_ids' => 'nullable|array',
            'unidad_operativa_ids.*' => 'integer|exists:unidades_operativas,id',
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('estado', 1),
            ],
        ]);

        $role = (string)$request->role;

        $unidadOperativaId = $request->unidad_operativa_id;
        if (!in_array($role, self::ROLES_CON_UNIDAD_UNICA, true)) {
            $unidadOperativaId = null;
        }

        $unidadesMultiples = collect($request->input('unidad_operativa_ids', []))
            ->map(fn ($id) => (int)$id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($role === User::ROLE_RESPONSABLE_UNIDADES && $unidadesMultiples->isEmpty()) {
            return back()->withErrors([
                'unidad_operativa_ids' => 'Selecciona al menos una unidad para este rol.'
            ])->withInput();
        }

        if ($role === User::ROLE_RESPONSABLE_UNIDADES) {
            // Compatibilidad con código que todavía lee users.unidad_operativa_id
            $unidadOperativaId = (int)$unidadesMultiples->first();
        }

        if ($role === 'proveedor' && !$request->proveedor_id) {
            return back()->withErrors(['proveedor_id' => 'Selecciona un proveedor.'])->withInput();
        }

        $usuario = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $role,
            'unidad_operativa_id' => $unidadOperativaId,
            'proveedor_id' => ($role === 'proveedor') ? $request->proveedor_id : null,
        ]);

        if ($role === 'proveedor') {
            Proveedor::where('id', $request->proveedor_id)->update([
                'user_id' => $usuario->id,
            ]);
        }

        if ($role === User::ROLE_RESPONSABLE_UNIDADES) {
            $usuario->unidadesAsignadas()->sync($unidadesMultiples->all());
        } else {
            $usuario->unidadesAsignadas()->sync([]);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        $usuario->load('unidadesAsignadas');

        $unidades = UnidadOperativa::all();
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();

        $proveedorLigado = Proveedor::where('user_id', $usuario->id)->first();

        return view('dashboard.usuarios.edit', compact('usuario', 'unidades', 'proveedores', 'proveedorLigado'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:60|alpha_dash|unique:users,username,' . $usuario->id,
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'role' => 'required|string',
            'unidad_operativa_id' => 'nullable|exists:unidades_operativas,id',
            'unidad_operativa_ids' => 'nullable|array',
            'unidad_operativa_ids.*' => 'integer|exists:unidades_operativas,id',
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('estado', 1),
            ],
        ]);

        $role = (string)$request->role;

        $unidadOperativaId = $request->unidad_operativa_id;
        if (!in_array($role, self::ROLES_CON_UNIDAD_UNICA, true)) {
            $unidadOperativaId = null;
        }

        $unidadesMultiples = collect($request->input('unidad_operativa_ids', []))
            ->map(fn ($id) => (int)$id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($role === User::ROLE_RESPONSABLE_UNIDADES && $unidadesMultiples->isEmpty()) {
            return back()->withErrors([
                'unidad_operativa_ids' => 'Selecciona al menos una unidad para este rol.'
            ])->withInput();
        }

        if ($role === User::ROLE_RESPONSABLE_UNIDADES) {
            // Compatibilidad con código que todavía lee users.unidad_operativa_id
            $unidadOperativaId = (int)$unidadesMultiples->first();
        }

        $proveedorAntes = Proveedor::where('user_id', $usuario->id)->first();

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $role,
            'unidad_operativa_id' => $unidadOperativaId,
        ];

        if ($role === 'proveedor') {
            if (!$request->proveedor_id) {
                return back()->withErrors(['proveedor_id' => 'Selecciona un proveedor.'])->withInput();
            }

            $data['proveedor_id'] = $request->proveedor_id;

            if ($proveedorAntes && (int)$proveedorAntes->id !== (int)$request->proveedor_id) {
                $proveedorAntes->update(['user_id' => null]);
            }

            Proveedor::where('id', $request->proveedor_id)->update([
                'user_id' => $usuario->id,
            ]);
        } else {
            $data['proveedor_id'] = null;

            if ($proveedorAntes) {
                $proveedorAntes->update(['user_id' => null]);
            }
        }

        $usuario->update($data);

        if ($role === User::ROLE_RESPONSABLE_UNIDADES) {
            $usuario->unidadesAsignadas()->sync($unidadesMultiples->all());
        } else {
            $usuario->unidadesAsignadas()->sync([]);
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $usuario)
    {
        if (auth()->id() === $usuario->id) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propio usuario');
        }

        if ($usuario->role === 'admin') {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'No se puede eliminar un usuario administrador');
        }

        $proveedorAntes = Proveedor::where('user_id', $usuario->id)->first();
        if ($proveedorAntes) {
            $proveedorAntes->update(['user_id' => null]);
        }

        $usuario->unidadesAsignadas()->sync([]);
        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente');
    }
}
