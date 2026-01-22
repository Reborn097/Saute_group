@extends('layouts.dashboard')

@section('titulo', 'Administrar proveedor')

@section('contenido')
<div class="contenedor">
    <div class="acciones-superior">
       

        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>
        <a href="{{ route('dashboard.proveedores.crear') }}" class="btn-agregar">Agregar proveedor</a>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Nombre del contacto</th>
                <th>Teléfono del contacto</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>RFC</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proveedores as $proveedor)
                <tr>
                    <td>{{ $proveedor->nombre }}</td>
                    <td>{{ $proveedor->nombre_contacto ?? '—' }}</td>
                    <td>{{ $proveedor->telefono_contacto ?? '—' }}</td>
                    <td>{{ $proveedor->telefono ?? '—' }}</td>
                    <td>
                        {{ $proveedor->calle ? $proveedor->calle . ', ' : '' }}
                        {{ $proveedor->colonia ? $proveedor->colonia . ', ' : '' }}
                        {{ $proveedor->codigo_postal ? 'CP ' . $proveedor->codigo_postal : '' }}
                    </td>
                    <td>{{ $proveedor->rfc ?? '—' }}</td>
                    <td class="text-center acciones">
    <a href="{{ route('dashboard.proveedores.cuenta', $proveedor->id) }}" class="btn-cuenta">Cuenta</a>
<a href="{{ route('dashboard.proveedores.editar', $proveedor->id) }}" class="btn-editar">Editar</a>

<form action="{{ route('dashboard.proveedores.eliminar', $proveedor->id) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn-eliminar">Eliminar</button>
</form>

</td>

                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No hay proveedores registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
        {{ $proveedores->onEachSide(1)->links('vendor.pagination.dashboard') }}
</div>

{{-- 🔹 Modal de error (solo aparece si existe un mensaje de error) --}}
@if (session('error'))
<div id="modalError" class="modal-overlay">
    <div class="modal-content">
        <h3>⚠️ No se puede eliminar</h3>
        <p>{{ session('error') }}</p>
        <button id="btnCerrarModal" class="btn-aceptar">Aceptar</button>
    </div>
</div>
@endif

<script>
    // Mostrar y cerrar modal de error
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalError');
        const btnCerrar = document.getElementById('btnCerrarModal');
        if (modal && btnCerrar) {
            modal.style.display = 'flex';
            btnCerrar.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
    });
</script>

<style>
.contenedor {
    background-color: #fceede;
    padding: 25px;
    border-radius: 12px;

    /* ✅ MÁS ANCHO */
    max-width: 1300px;      /* antes 1000px */
    width: 95%;             /* para que crezca en pantallas grandes */
    margin: auto;

    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 {
    color: #ffffff;
    font-weight: bold;
}
.acciones-superior {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.btn-agregar {
    background-color: #941c1c;
    color: #fff;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}
.btn-agregar:hover {
    background-color: #b82929;
}
.tabla {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
}
.tabla th {
    background-color: #b22b27;
    color: white;
    padding: 10px;
    text-align: center;
}
.tabla td {
    padding: 14px 12px;
    vertical-align: middle;
}
.tabla tbody tr {
    border-bottom: 1px solid #ddd; /* ✅ UNA sola línea pareja */
}

.tabla tbody tr:last-child {
    border-bottom: none; /* opcional: quita la última */
}

.tabla tr:hover {
    background-color: #f8dcdc;
}
.text-center {
    text-align: center;
}
.btn-menu {
    background-color: #999;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 1em;
    font-family: 'Poppins';
    cursor: pointer;
    transition: background-color 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.btn-menu:hover {
    background-color: #8f1f1c;
}

/* 🔹 Contenedor de acciones */
.acciones {
    display: flex;
    justify-content: center;
    gap: 10px;
}

/* 🔹 Estilo base compartido para ambos botones */
.btn-editar,
.btn-eliminar {
    display: inline-block;
    min-width: 90px;
    text-align: center;
    padding: 6px 0;
    border-radius: 6px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    border: none;
    cursor: pointer;
    color: #fff !important;
    transition: background-color 0.2s ease;
    text-decoration: none;
}

/* 🔴 Editar */
.btn-editar {
    background-color: #b22b27;
}
.btn-editar:hover {
    background-color: #941c1c;
}

/* ⚪ Eliminar */
.btn-eliminar {
    background-color: #888;
}
.btn-eliminar:hover {
    background-color: #666;
}

.btn-cuenta{
    display: inline-block;
    min-width: 90px;
    text-align: center;
    padding: 6px 0;
    border-radius: 6px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    border: none;
    cursor: pointer;
    color: #fff !important;
    transition: background-color 0.2s ease;
    text-decoration: none;
    background-color: #0e2238;
}
.btn-cuenta:hover{ background-color:#13314f; }


/* 🔹 Modal de error */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background-color: #fff;
    padding: 25px 35px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    max-width: 400px;
    width: 90%;
    font-family: 'Poppins', sans-serif;
    animation: fadeIn 0.3s ease;
}

.modal-content h3 {
    color: #b22b27;
    margin-bottom: 10px;
}
.modal-content p {
    color: #333;
    font-size: 1em;
    margin-bottom: 20px;
}
.btn-aceptar {
    background-color: #b22b27;
    color: #fff;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s ease;
}
.btn-aceptar:hover {
    background-color: #941c1c;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
@endsection
