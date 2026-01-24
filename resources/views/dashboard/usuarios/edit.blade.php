@extends('layouts.dashboard')

@section('titulo', 'Editar Usuario')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button type="button" class="btn-regresar"
            onclick="window.location.href='{{ route('usuarios.index') }}'">
            Regresar
        </button>
    </div>

    <h2>Editar Usuario</h2>

    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" style="margin-top:15px;">
        @csrf
        @method('PUT')

        <div class="grid">

            {{-- NOMBRE --}}
            <div>
                <label>Nombre</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $usuario->name) }}"
                    required>
            </div>

            {{-- USERNAME --}}
            <div>
                <label>Username</label>
                <input
                    type="text"
                    name="username"
                    value="{{ old('username', $usuario->username) }}"
                    placeholder="Ej. juan.perez"
                    required>
                <small class="hint">
                    Sin espacios. Recomendado: letras, números, guiones o guion_bajo.
                </small>
            </div>

            {{-- EMAIL --}}
            <div>
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $usuario->email) }}"
                    required>
            </div>

            {{-- ROL --}}
            <div>
                <label>Rol</label>
                <select name="role" id="role" required>
                    @foreach([
                        'admin' => 'Admin',
                        'ceo' => 'CEO',
                        'encargado_cocina' => 'Encargado de cocina',
                        'encargado_cafeteria' => 'Encargado de cafetería',
                        'almacenista' => 'Almacenista',
                        'proveedor' => 'Proveedor',
                    ] as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('role', $usuario->role) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- PROVEEDOR (solo si role=proveedor) --}}
            <div id="bloque-proveedor" style="display:none;">
                <label>Proveedor</label>
                <select name="proveedor_id" id="proveedor_id">
                    <option value="">Selecciona un proveedor</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->id }}"
                            {{ old('proveedor_id', $usuario->proveedor_id) == $p->id ? 'selected' : '' }}>
                            {{ $p->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- UNIDAD --}}
            <div>
                <label>Unidad</label>
                <select name="unidad_operativa_id">
                    <option value="">Sin unidad</option>
                    @foreach($unidades as $u)
                        <option value="{{ $u->id }}"
                            {{ old('unidad_operativa_id', $usuario->unidad_operativa_id) == $u->id ? 'selected' : '' }}>
                            {{ $u->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        <div class="footer-acciones">
            <button type="submit" class="btn">
                Guardar cambios
            </button>

            <a href="{{ route('usuarios.index') }}" class="btn-cancelar">
                Cancelar
            </a>
        </div>

    </form>

</div>

<style>
/* ===== CONTENEDOR ===== */
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* ===== TOP ACTIONS ===== */
.acciones-superior{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    margin-bottom:10px;
}

h2{ margin:0 0 10px; }

/* ===== GRID ===== */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}
@media(max-width:720px){
    .grid{ grid-template-columns:1fr; }
}

/* ===== CONTROLES ===== */
label{ font-weight:700; display:block; margin-bottom:6px; }
input, select{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
}
.hint{
    display:block;
    margin-top:6px;
    color:#6b6b6b;
    font-size:13px;
}

/* ===== BOTONES ===== */
.btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
}
.btn:hover{ background:#941c1c; }

.btn-regresar,
.btn-cancelar{
    background:#777;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
}
.btn-regresar:hover,
.btn-cancelar:hover{ filter:brightness(.95); }

/* footer */
.footer-acciones{
    margin-top:18px;
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
}
</style>

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
