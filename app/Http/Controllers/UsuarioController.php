<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnidadOperativa;
use Illuminate\Http\Request;
use App\Models\Proveedor;


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
        $proveedores = Proveedor::orderBy('nombre')->get(); // ✅
        return view('dashboard.usuarios.create', compact('unidades', 'proveedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
            'unidad_id' => 'nullable|exists:unidades_operativas,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
        ]);

        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'unidad_operativa_id' => $request->unidad_id,
        ]);

        // ✅ Si es proveedor, ligar proveedor existente
        if ($request->role === 'proveedor') {
            if (!$request->proveedor_id) {
                return back()->withErrors(['proveedor_id' => 'Selecciona un proveedor.'])->withInput();
            }

            // evitar que ese proveedor ya tenga user
            Proveedor::where('id', $request->proveedor_id)->update([
                'user_id' => $usuario->id,
            ]);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
    }


   public function edit(User $usuario)
    {
        $unidades = UnidadOperativa::all();
        $proveedores = Proveedor::orderBy('nombre')->get();

        // proveedor ya ligado a este usuario (si existe)
        $proveedorLigado = Proveedor::where('user_id', $usuario->id)->first();

        return view('dashboard.usuarios.edit', compact('usuario', 'unidades', 'proveedores', 'proveedorLigado'));
    }


    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'role' => 'required',
            'unidad_operativa_id' => 'nullable|exists:unidades_operativas,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'unidad_operativa_id' => $request->unidad_operativa_id,
        ];

        // ✅ si es proveedor, guardar proveedor_id
        if ($request->role === 'proveedor') {
            $data['proveedor_id'] = $request->proveedor_id; // puede ser null si no seleccionó
        } else {
            // ✅ si no es proveedor, limpiar proveedor_id
            $data['proveedor_id'] = null;
        }

        // ✅ si NO es rol con unidad, limpiar unidad (opcional pero recomendado)
        if (!in_array($request->role, ['encargado_cocina', 'encargado_cafeteria', 'almacenista'])) {
            $data['unidad_operativa_id'] = null;
        }

        $usuario->update($data);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente');
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


