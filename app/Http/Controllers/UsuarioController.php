<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnidadOperativa;
use Illuminate\Http\Request;
use App\Models\Proveedor;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        // username ya vendrá con los usuarios (no ocupa with)
        $usuarios = User::with('unidad')->get();
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

            // ✅ NUEVO: username único
            'username' => 'required|string|max:60|alpha_dash|unique:users,username',

            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string',

            // en tu create mandas "unidad_id"
            'unidad_id' => 'nullable|exists:unidades_operativas,id',

            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('estado', 1),
            ],
        ]);

        // Si es rol con unidad, se guarda, si no, null
        $unidadOperativaId = $request->unidad_id;
        if (!in_array($request->role, ['encargado_cocina', 'encargado_cafeteria', 'almacenista'])) {
            $unidadOperativaId = null;
        }

        // ✅ Validación de proveedor si role=proveedor
        if ($request->role === 'proveedor' && !$request->proveedor_id) {
            return back()->withErrors(['proveedor_id' => 'Selecciona un proveedor.'])->withInput();
        }

        $usuario = User::create([
            'name' => $request->name,
            'username' => $request->username, // ✅ NUEVO
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,

            'unidad_operativa_id' => $unidadOperativaId,

            // ✅ IMPORTANTE: guardar proveedor_id también en users
            'proveedor_id' => ($request->role === 'proveedor') ? $request->proveedor_id : null,
        ]);

        // ✅ Si es proveedor: amarrar proveedor.user_id -> usuario.id
        if ($request->role === 'proveedor') {

            // Evitar que ese proveedor ya esté ligado a otro user (si sí, lo pisas o lo bloqueas)
            // Aquí lo piso de forma controlada:
            Proveedor::where('id', $request->proveedor_id)->update([
                'user_id' => $usuario->id,
            ]);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        $unidades = UnidadOperativa::all();
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();

        // proveedor ya ligado a este usuario (si existe)
        $proveedorLigado = Proveedor::where('user_id', $usuario->id)->first();

        return view('dashboard.usuarios.edit', compact('usuario', 'unidades', 'proveedores', 'proveedorLigado'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',

            // ✅ NUEVO: username único ignorando al usuario actual
            'username' => 'required|string|max:60|alpha_dash|unique:users,username,' . $usuario->id,

            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'role' => 'required|string',
            'unidad_operativa_id' => 'nullable|exists:unidades_operativas,id',
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('estado', 1),
            ],
        ]);

        // Si NO es rol con unidad, limpiar unidad
        $unidadOperativaId = $request->unidad_operativa_id;
        if (!in_array($request->role, ['encargado_cocina', 'encargado_cafeteria', 'almacenista'])) {
            $unidadOperativaId = null;
        }

        // proveedor que estaba ligado por proveedores.user_id
        $proveedorAntes = Proveedor::where('user_id', $usuario->id)->first();

        $data = [
            'name' => $request->name,
            'username' => $request->username, // ✅ NUEVO
            'email' => $request->email,
            'role' => $request->role,
            'unidad_operativa_id' => $unidadOperativaId,
        ];

        if ($request->role === 'proveedor') {

            if (!$request->proveedor_id) {
                return back()->withErrors(['proveedor_id' => 'Selecciona un proveedor.'])->withInput();
            }

            // ✅ users.proveedor_id
            $data['proveedor_id'] = $request->proveedor_id;

            // ✅ Si cambió el proveedor, libera el anterior
            if ($proveedorAntes && (int)$proveedorAntes->id !== (int)$request->proveedor_id) {
                $proveedorAntes->update(['user_id' => null]);
            }

            // ✅ Liga el nuevo proveedor a este user
            Proveedor::where('id', $request->proveedor_id)->update([
                'user_id' => $usuario->id,
            ]);

        } else {
            // ✅ Si ya no es proveedor: limpiar users.proveedor_id y soltar proveedor.user_id
            $data['proveedor_id'] = null;

            if ($proveedorAntes) {
                $proveedorAntes->update(['user_id' => null]);
            }
        }

        $usuario->update($data);

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

        // ✅ Si era proveedor, suelta el vínculo
        $proveedorAntes = Proveedor::where('user_id', $usuario->id)->first();
        if ($proveedorAntes) {
            $proveedorAntes->update(['user_id' => null]);
        }

        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente');
    }
}
