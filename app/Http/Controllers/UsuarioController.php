<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnidadOperativa;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with('unidad')->get();
        return view('dashboard.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $unidades = UnidadOperativa::all();
        return view('dashboard.usuarios.create', compact('unidades'));
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6',
        'role' => 'required',
    ]);

    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'role' => $request->role,
        'unidad_operativa_id' => $request->unidad_id,
    ]);

    return redirect()->route('usuarios.index');
}

    public function edit(User $usuario)
    {
        $unidades = UnidadOperativa::all();
        return view('dashboard.usuarios.edit', compact('usuario', 'unidades'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'role' => 'required',
        ]);

        $usuario->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'unidad_operativa_id' => $request->unidad_operativa_id,
        ]);

        return redirect()->route('usuarios.index');
    }


    public function destroy(User $usuario)
    {
        // No permitir eliminarse a sí mismo
        if (auth()->id() === $usuario->id) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propio usuario');
        }

        // No permitir eliminar al admin principal (opcional)
        if ($usuario->role === 'admin') {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'No se puede eliminar un usuario administrador');
        }

        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente');
    }

}


