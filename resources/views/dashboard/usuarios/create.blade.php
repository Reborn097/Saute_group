@extends('layouts.dashboard')

@section('titulo', 'Crear Usuario')

@section('contenido')
<div class="contenedor-form">

    <button type="button" class="btn"
        onclick="window.location.href='{{ route('usuarios.index') }}'">
        Volver
    </button>

    <h2 style="margin-top:20px;">Crear Usuario</h2>

    <form method="POST" action="{{ route('usuarios.store') }}" style="margin-top:25px;">
        @csrf

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

            {{-- NOMBRE --}}
            <div>
                <label>Nombre</label>
                <input
                    type="text"
                    name="name"
                    class="input-buscar"
                    value="{{ old('name') }}"
                    required>
            </div>

            {{-- EMAIL --}}
            <div>
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    class="input-buscar"
                    value="{{ old('email') }}"
                    required>
            </div>

            {{-- CONTRASEÑA --}}
            <div>
                <label>Contraseña</label>
                <input
                    type="password"
                    name="password"
                    class="input-buscar"
                    required>
            </div>

            {{-- ROL --}}
            <div>
                <label>Rol</label>
                <select name="role" class="input-buscar" required>
                    <option value="">Seleccione un rol</option>
                    <option value="admin">Admin</option>
                    <option value="ceo">CEO</option>
                    <option value="encargado_cocina">Encargado de cocina</option>
                    <option value="encargado_cafeteria">Encargado de cafetería</option>
                    <option value="almacenista">Almacenista</option>
                    <option value="proveedor">Proveedor</option>
                </select>
            </div>

            {{-- UNIDAD --}}
            <div>
                <label>Unidad</label>
                <select name="unidad_operativa_id" class="input-buscar">
                    <option value="">Sin unidad</option>
                    @foreach($unidades as $u)
                        <option value="{{ $u->id }}"
                            {{ old('unidad_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        <div style="margin-top:30px; display:flex; gap:10px;">
            <button type="submit" class="btn-accion">
                Crear usuario
            </button>

            <a href="{{ route('usuarios.index') }}" class="btn-cancelar">
                Cancelar
            </a>
        </div>

    </form>

</div>
@endsection
