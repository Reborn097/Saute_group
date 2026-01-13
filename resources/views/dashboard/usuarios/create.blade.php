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

            {{-- USERNAME (NUEVO) --}}
            <div>
                <label>Username</label>
                <input
                    type="text"
                    name="username"
                    class="input-buscar"
                    value="{{ old('username') }}"
                    placeholder="Ej. juan.perez"
                    required>
                <small style="opacity:.7; display:block; margin-top:6px;">
                    Sin espacios. Recomendado: letras, números, guiones o guion_bajo.
                </small>
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
                <select name="role" id="role" class="input-buscar" required>
                    @foreach([
                        'admin' => 'Admin',
                        'ceo' => 'CEO',
                        'encargado_cocina' => 'Encargado de cocina',
                        'encargado_cafeteria' => 'Encargado de cafetería',
                        'almacenista' => 'Almacenista',
                        'proveedor' => 'Proveedor',
                    ] as $key => $label)

                        <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>

                    @endforeach
                </select>
            </div>

            {{-- PROVEEDOR (solo si el rol es proveedor) --}}
            <div class="form-grupo" id="bloque-proveedor" style="display:none;">
                <label for="proveedor_id">Proveedor (solo si el rol es proveedor)</label>
                <select name="proveedor_id" id="proveedor_id" class="input-buscar">
                    <option value="">Selecciona un proveedor</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->id }}" {{ old('proveedor_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nombre }}
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
                            {{ old('unidad_operativa_id') == $u->id ? 'selected' : '' }}>
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

<script>
function toggleProveedor() {
    const role = document.getElementById('role')?.value;
    const bloque = document.getElementById('bloque-proveedor');
    if (!bloque) return;

    bloque.style.display = (role === 'proveedor') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    toggleProveedor();
    document.getElementById('role')?.addEventListener('change', toggleProveedor);
});
</script>

@endsection
