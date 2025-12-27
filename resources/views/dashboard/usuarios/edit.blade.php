@extends('layouts.dashboard')

@section('titulo', 'Editar Usuario')

@section('contenido')
<div class="contenedor-form">

    <button type="button" class="btn"
        onclick="window.location.href='{{ route('usuarios.index') }}'">
        Volver
    </button>

    <h2 style="margin-top:20px;">Editar Usuario</h2>

    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" style="margin-top:25px;">
        @csrf
        @method('PUT')

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

            {{-- NOMBRE --}}
            <div>
                <label>Nombre</label>
                <input
                    type="text"
                    name="name"
                    class="input-buscar"
                    value="{{ old('name', $usuario->name) }}"
                    required>
            </div>

            {{-- EMAIL --}}
            <div>
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    class="input-buscar"
                    value="{{ old('email', $usuario->email) }}"
                    required>
            </div>

            {{-- ROL --}}
            <div>
                <label>Rol</label>
                <select name="role" class="input-buscar" required>
                    @foreach([
                        'admin' => 'Admin',
                        'ceo' => 'CEO',
                        'encargado_cocina' => 'Encargado de cocina',
                        'encargado_cafeteria' => 'Encargado de cafetería',
                        'almacenista' => 'Almacenista',
                        'proveedor' => 'Proveedor',
                    ] as $key => $label)
                        <option value="{{ $key }}"
                            {{ $usuario->role === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- UNIDAD --}}
            <div>
                <label>Unidad</label>
                <select name="unidad_operativa_id" class="input-buscar">
                    <option value="">Sin unidad</option>
                    @foreach($unidades as $u)
                        <option value="{{ $u->id }}"
                            {{ $usuario->unidad_operativa_id == $u->id ? 'selected' : '' }}>
                            {{ $u->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        <div style="margin-top:30px; display:flex; gap:10px;">
            <button type="submit" class="btn-accion">
                Guardar cambios
            </button>

            <a href="{{ route('usuarios.index') }}" class="btn-cancelar">
                Cancelar
            </a>
        </div>

    </form>

</div>
@endsection
