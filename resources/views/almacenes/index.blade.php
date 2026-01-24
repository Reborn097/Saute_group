@extends('layouts.dashboard')

@section('titulo', 'Almacenes de ' . $unidad->nombre)

@section('contenido')

<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('unidades.index') }}'">
            Regresar
        </button>

        <button class="btn"
            onclick="window.location.href='{{ route('almacenes.create', $unidad->id) }}'">
            Nuevo almacén
        </button>
    </div>

    <h2>Almacenes de {{ $unidad->nombre }}</h2>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th style="width:160px;">Tipo</th>
                    <th>Ubicación</th>
                    <th style="width:260px;">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($almacenes as $alm)
                    <tr>
                        <td style="font-weight:800;">{{ $alm->nombre }}</td>
                        <td class="center">{{ ucfirst($alm->tipo) }}</td>
                        <td>{{ $alm->ubicacion ?? '—' }}</td>

                        <td>
                            <div class="acciones">
                                <button class="btn-mini btn-mini-editar" type="button"
                                    onclick="window.location.href='{{ route('almacenes.edit', [$unidad->id, $alm->id]) }}'">
                                    Editar
                                </button>

                                <form
                                    action="{{ route('almacenes.destroy', [$unidad->id, $alm->id]) }}"
                                    method="POST"
                                    class="form-eliminar"
                                    data-nombre="{{ $alm->nombre }}"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-mini btn-mini-eliminar">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="vacio">
                            No hay almacenes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- =========================
   MODAL ELIMINAR
========================= --}}
<div id="modalEliminar" class="modal" aria-hidden="true">
    <div class="modal-contenido" role="dialog" aria-modal="true">
        <div class="modal-header">
            <span>Confirmar eliminación</span>
            <button type="button" onclick="cerrarModalEliminar()">✕</button>
        </div>

        <div class="modal-body">
            <p style="margin:0;">
                ¿Seguro que deseas eliminar el almacén <b id="modalEliminarNombre">—</b>?
            </p>
            <p style="margin:10px 0 0; color:#666; font-size:13px;">
                Esta acción no se puede deshacer.
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-cancelar" onclick="cerrarModalEliminar()">Cancelar</button>
            <button type="button" class="btn" id="btnConfirmarEliminar">Sí, eliminar</button>
        </div>
    </div>
</div>

<style>
/* ===== CONTENEDOR (igual inventarios) ===== */
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
    justify-content:space-between;
    margin-bottom:12px;
}

/* ===== TITULO ===== */
h2{ margin:0 0 10px; }

/* ===== BOTONES (igual inventarios) ===== */
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

.btn-menu{
    background:#777;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu:hover{ filter:brightness(.95); }

.btn-cancelar{
    background:#777;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-cancelar:hover{ filter:brightness(.95); }

/* ===== TABLA (igual inventarios) ===== */
.tabla-wrap{ overflow:auto; border-radius:10px; }
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
}
.tabla thead th{
    background:#b22b27;
    color:#fff;
    text-align:left;
    padding:10px;
    font-weight:700;
}
.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    vertical-align:top;
}
.center{ text-align:center; }
.vacio{
    text-align:center;
    padding:18px;
    color:#666;
    background:#fff7f0;
}

/* ===== ACCIONES (sin “horrible wrap”) ===== */
.acciones{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}
.btn-mini{
    padding:7px 12px;
    border-radius:8px;
    font-weight:800;
    border:none;
    cursor:pointer;
    min-width:105px;
    text-align:center;
    line-height:1;
    white-space:nowrap;
}
.btn-mini-editar{ background:#2b2b2b; color:#fff; }
.btn-mini-editar:hover{ filter:brightness(.95); }

.btn-mini-eliminar{ background:#777; color:#fff; }
.btn-mini-eliminar:hover{ filter:brightness(.95); }

/* ===== MODAL (igual al que ya estás usando) ===== */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:14px;
}
.modal.show{ display:flex; }
.modal-contenido{
    width:min(460px, 100%);
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,.25);
}
.modal-header{
    background:#b22b27;
    color:#fff;
    padding:14px 16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-weight:900;
}
.modal-header button{
    background:transparent;
    border:none;
    color:#fff;
    font-size:18px;
    cursor:pointer;
    padding:4px 8px;
    border-radius:8px;
}
.modal-header button:hover{ background:rgba(255,255,255,.15); }
.modal-body{ padding:16px; }
.modal-footer{
    padding:14px 16px;
    border-top:1px solid #eee;
    background:#fafafa;
    display:flex;
    justify-content:flex-end;
    gap:10px;
}
</style>

<script>
let formEliminarPendiente = null;

function abrirModalEliminar(form, nombre){
    formEliminarPendiente = form;
    document.getElementById('modalEliminarNombre').textContent = nombre || '—';
    const modal = document.getElementById('modalEliminar');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => document.getElementById('btnConfirmarEliminar')?.focus(), 0);
}

function cerrarModalEliminar(){
    formEliminarPendiente = null;
    const modal = document.getElementById('modalEliminar');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.form-eliminar').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            abrirModalEliminar(form, form.dataset.nombre || '');
        });
    });

    document.getElementById('btnConfirmarEliminar')?.addEventListener('click', () => {
        if(formEliminarPendiente) formEliminarPendiente.submit();
    });

    document.getElementById('modalEliminar')?.addEventListener('click', (e) => {
        if(e.target.id === 'modalEliminar') cerrarModalEliminar();
    });

    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape'){
            const modal = document.getElementById('modalEliminar');
            if(modal?.classList.contains('show')) cerrarModalEliminar();
        }
    });
});
</script>

@endsection
