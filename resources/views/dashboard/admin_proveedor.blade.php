@extends('layouts.dashboard')

@section('titulo', 'Administrar proveedor')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        <a href="{{ route('dashboard.proveedores.crear') }}" class="btn-agregar">
            Agregar proveedor
        </a>
    </div>

    {{-- ✅ wrapper para que la tabla sea responsive sin salirse --}}
    <div class="tabla-wrap">
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

                        <td class="text-center">
                            <div class="acciones">
                                <a href="{{ route('dashboard.proveedores.cuenta', $proveedor->id) }}" class="btn-cuenta">
                                    Cuenta
                                </a>

                                <a href="{{ route('dashboard.proveedores.editar', $proveedor->id) }}" class="btn-editar">
                                    Editar
                                </a>

                                <form action="{{ route('dashboard.proveedores.eliminar', $proveedor->id) }}"
                                      method="POST"
                                      data-nombre="{{ $proveedor->nombre }}"
                                      onsubmit="return confirmarEliminarProveedor(this)"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-eliminar">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No hay proveedores registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $proveedores->onEachSide(1)->links('vendor.pagination.dashboard') }}
    </div>
</div>

{{-- ðŸ”¹ Modal de error (solo aparece si existe un mensaje de error) --}}
<div id="modalConfirmEliminar" class="modal-overlay">
    <div class="modal-content">
        <h3>¿Inactivar proveedor?</h3>
        <p id="modalConfirmText">¿Seguro que deseas inactivar este proveedor?</p>
        <div class="modal-actions">
            <button id="btnCancelarEliminar" type="button" class="btn-cancelar">Cancelar</button>
            <button id="btnConfirmEliminar" type="button" class="btn-aceptar">Sí, inactivar</button>
        </div>
    </div>
</div>

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

let formEliminarPendiente = null;

function confirmarEliminarProveedor(form) {
    const modal = document.getElementById('modalConfirmEliminar');
    const texto = document.getElementById('modalConfirmText');
    const nombre = form?.dataset?.nombre || 'este proveedor';

    formEliminarPendiente = form;
    if (texto) {
        texto.textContent = `¿Seguro que deseas inactivar al proveedor "${nombre}"? Se desactivarán sus relaciones y precios, pero no se eliminará de la base de datos.`;
    }
    if (modal) {
        modal.style.display = 'flex';
    }
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalConfirmEliminar');
    const btnCancelar = document.getElementById('btnCancelarEliminar');
    const btnConfirm = document.getElementById('btnConfirmEliminar');

    if (btnCancelar && modal) {
        btnCancelar.addEventListener('click', () => {
            modal.style.display = 'none';
            formEliminarPendiente = null;
        });
    }

    if (btnConfirm && modal) {
        btnConfirm.addEventListener('click', () => {
            modal.style.display = 'none';
            if (formEliminarPendiente) {
                formEliminarPendiente.submit();
            }
        });
    }
});
</script>

<style>
/* ✅ hereda Poppins del dashboard.css, pero lo aseguro aquí por si acaso */
.contenedor, .contenedor *{
    font-family:'Poppins', sans-serif;
}

/* CONTENEDOR (igual look de tu captura) */
.contenedor{
    background-color:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1300px;
    width:95%;
    margin:auto;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

/* ACCIONES SUPERIOR */
.acciones-superior{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:15px;
}

.btn-menu{
    background-color:#999;
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    font-size:1em;
    cursor:pointer;
    transition:background-color .2s ease;
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
    white-space:nowrap;
}
.btn-menu:hover{ background-color:#7f7f7f; }

.btn-agregar{
    background-color:#941c1c;
    color:#fff;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    white-space:nowrap;
}
.btn-agregar:hover{ background-color:#b82929; }

/* ✅ TABLA RESPONSIVA SIN SALIRSE */
.tabla-wrap{
    width:100%;
    overflow-x:auto;            /* ðŸ”¥ scroll horizontal solo si hace falta */
    border-radius:10px;         /* se ve como "tarjeta" dentro del contenedor */
    background:#fff;
    box-shadow:0 2px 6px rgba(0,0,0,0.06);
}

/* tabla */
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    min-width:1100px;           /* ðŸ”¥ fuerza scroll en pantallas chicas */
}

/* head */
.tabla th{
    background-color:#b22b27;
    color:#fff;
    padding:12px 10px;
    text-align:center;
    white-space:nowrap;
    font-weight:700;
}

/* body */
.tabla td{
    padding:14px 12px;
    vertical-align:middle;
    text-align:center;
    border-bottom:1px solid #ececec;
}

/* hover */
.tabla tbody tr:hover{ background-color:#f8dcdc; }

/* ACCIONES (botones en fila como tu captura) */
.acciones{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

/* botones */
.btn-editar,
.btn-eliminar,
.btn-cuenta{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:92px;
    height:36px;
    padding:0 14px;
    border-radius:8px;
    font-weight:700;
    border:none;
    cursor:pointer;
    color:#fff !important;
    text-decoration:none;
    transition:filter .15s ease, background-color .15s ease;
    white-space:nowrap;
}

.btn-editar{ background-color:#b22b27; }
.btn-editar:hover{ background-color:#941c1c; }

.btn-eliminar{ background-color:#888; }
.btn-eliminar:hover{ background-color:#666; }

.btn-cuenta{ background-color:#0e2238; }
.btn-cuenta:hover{ background-color:#13314f; }

.text-center{ text-align:center; }

.paginacion-wrap{ margin-top:15px; }

/* MODAL */
.modal-overlay{
    position:fixed;
    top:0; left:0;
    width:100%;
    height:100%;
    background-color:rgba(0,0,0,0.4);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:14px;
}

.modal-content{
    background-color:#fff;
    padding:25px 35px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 4px 15px rgba(0,0,0,0.3);
    max-width:420px;
    width:100%;
    animation:fadeIn .25s ease;
}

.modal-content h3{
    color:#b22b27;
    margin:0 0 10px;
}
.modal-content p{
    color:#333;
    font-size:1em;
    margin:0 0 20px;
}

.btn-aceptar{
    background-color:#b22b27;
    color:#fff;
    border:none;
    padding:10px 25px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
}
.btn-aceptar:hover{ background-color:#941c1c; }

.modal-actions{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
}

.btn-cancelar{
    background-color:#888;
    color:#fff;
    border:none;
    padding:10px 25px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
}
.btn-cancelar:hover{ background-color:#666; }

@keyframes fadeIn{
    from{ opacity:0; transform:scale(.96); }
    to{ opacity:1; transform:scale(1); }
}

/* ✅ RESPONSIVE: en móvil, botones superiores en columna, sin cambiar diseño desktop */
@media(max-width:720px){
    .contenedor{
        width:100%;
        padding:18px 14px;
    }

    .acciones-superior{
        flex-direction:column;
        align-items:stretch;
    }

    .btn-menu,
    .btn-agregar{
        width:100%;
    }

    .acciones{
        width:100%;
        flex-direction:column;   /* en móvil, botones uno abajo del otro */
        align-items:stretch;
    }

    .btn-editar,
    .btn-eliminar,
    .btn-cuenta{
        width:100%;
        min-width:0;
    }
}
</style>
@endsection







