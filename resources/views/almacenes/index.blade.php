@extends('layouts.dashboard')

@section('titulo', 'Almacenes de ' . $unidad->nombre)

@section('contenido')

<style>
/* CONTENEDOR */
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:900px;
    margin:0 auto;
    box-shadow:0 0 10px rgba(0,0,0,0.08);
}

/* TITULO */
.titulo{
    text-align:center;
    margin:0 0 18px;
    font-size:22px;
    font-weight:800;
}

/* ACCIONES SUPERIORES */
.acciones-top{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-bottom:15px;
    flex-wrap:wrap;
}

/* BOTONES */
.btn{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:9px 14px;
    border-radius:10px;
    cursor:pointer;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    gap:8px;
}
.btn:hover{ background:#941c1c; }

.btn-outline{
    background:#fff;
    color:#b22b27;
    border:1px solid #b22b27;
    padding:9px 14px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:8px;
}
.btn-outline:hover{ background:#fff7f6; }

.btn-danger{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:7px 12px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:8px;
}
.btn-danger:hover{ background:#941c1c; }

.btn-gray{
    background:#777;
    color:#fff;
    border:none;
    padding:7px 12px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:8px;
}
.btn-gray:hover{ background:#5f5f5f; }

.ico{ font-size:14px; }

/* TABLA */
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
}

.tabla thead th{
    background:#b22b27;
    color:#fff;
    padding:12px;
    text-align:center;
}

.tabla tbody td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
}

.tabla tbody tr:hover{
    background:#f5d6d6;
}

.td-acciones{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
}

/* MODAL ELIMINAR */
.modal-eliminar{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    z-index:9999;
    align-items:center;
    justify-content:center;
    padding:16px;
}
.modal-eliminar.show{ display:flex; }

.modal-eliminar-card{
    width:min(420px, 100%);
    background:#fff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,.22);
    border:1px solid rgba(0,0,0,0.06);
}

.modal-eliminar-header{
    background:#b22b27;
    color:#fff;
    padding:14px 16px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    font-weight:900;
}
.modal-eliminar-title{ font-size:16px; }
.modal-eliminar-x{
    background:transparent;
    border:none;
    color:#fff;
    font-size:16px;
    cursor:pointer;
    padding:4px 8px;
    border-radius:8px;
}
.modal-eliminar-x:hover{ background:rgba(255,255,255,.15); }

.modal-eliminar-body{ padding:16px; }
.modal-eliminar-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:14px 16px 16px;
    background:#fafafa;
    border-top:1px solid #eee;
}
</style>

<div class="contenedor">

    <h2 class="titulo">Almacenes de {{ $unidad->nombre }}</h2>

    <div class="acciones-top">
        <button class="btn"
            onclick="window.location.href='{{ route('almacenes.create', $unidad->id) }}'">
            <span class="ico"></span> Nuevo almacén
        </button>

        <button class="btn-outline" onclick="window.location.href='{{ route('unidades.index') }}'">
            <span class="ico"></span> Volver
        </button>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Ubicación</th>
                <th style="width:240px;">Acciones</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($almacenes as $alm)
                <tr>
                    <td>{{ $alm->nombre }}</td>
                    <td>{{ ucfirst($alm->tipo) }}</td>
                    <td>{{ $alm->ubicacion ?? '—' }}</td>

                    <td>
                        <div class="td-acciones">
                            <button class="btn-gray"
                                onclick="window.location.href='{{ route('almacenes.edit', [$unidad->id, $alm->id]) }}'">
                                <span class="ico"></span> Editar
                            </button>

                            <form
                                action="{{ route('almacenes.destroy', [$unidad->id, $alm->id]) }}"
                                method="POST"
                                class="form-eliminar"
                                data-nombre="{{ $alm->nombre }}"
                                style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">
                                    <span class="ico"></span> Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#555; padding:18px;">
                        No hay almacenes registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- =========================
   MODAL ELIMINAR
========================= --}}
<div id="modalEliminar" class="modal-eliminar" aria-hidden="true">
    <div class="modal-eliminar-card" role="dialog" aria-modal="true">
        <div class="modal-eliminar-header">
            <div class="modal-eliminar-title">Confirmar eliminación</div>
            <button type="button" class="modal-eliminar-x" onclick="cerrarModalEliminar()">✕</button>
        </div>

        <div class="modal-eliminar-body">
            <p style="margin:0;">
                ¿Seguro que deseas eliminar el almacén
                <b id="modalEliminarNombre">—</b>?
            </p>
            <p style="margin:10px 0 0; color:#666; font-size:13px;">
                Esta acción no se puede deshacer.
            </p>
        </div>

        <div class="modal-eliminar-actions">
            <button type="button" class="btn-outline" onclick="cerrarModalEliminar()">Cancelar</button>
            <button type="button" class="btn-danger" id="btnConfirmarEliminar">
                Sí, eliminar
            </button>
        </div>
    </div>
</div>

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

    // interceptar eliminar
    document.querySelectorAll('.form-eliminar').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const nombre = form.dataset.nombre || '';
            abrirModalEliminar(form, nombre);
        });
    });

    // confirmar
    document.getElementById('btnConfirmarEliminar')?.addEventListener('click', () => {
        if(formEliminarPendiente) formEliminarPendiente.submit();
    });

    // click fuera
    document.getElementById('modalEliminar')?.addEventListener('click', (e) => {
        if(e.target.id === 'modalEliminar') cerrarModalEliminar();
    });

    // ESC
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape'){
            const modal = document.getElementById('modalEliminar');
            if(modal?.classList.contains('show')) cerrarModalEliminar();
        }
    });
});
</script>

@endsection
