@extends('layouts.dashboard')

@section('titulo', 'Unidades operativas')

@section('contenido')

@php
    // fallback por si no mandas $tipos desde el controlador
    $tiposLocal = collect($tipos ?? [])
        ->filter()
        ->values();

    if ($tiposLocal->isEmpty()) {
        $tiposLocal = collect($unidades ?? collect())
            ->pluck('tipo')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        <button class="btn-secundario"
            onclick="window.location.href='{{ route('unidades.create') }}'">
            + Nueva unidad
        </button>
    </div>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('unidades.index') }}" class="filtros">

        <div class="campo">
            <label>Buscar</label>
            <input class="input"
                   type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Buscar unidad...">
        </div>

        <div class="campo">
            <label>Tipo</label>
            <select class="select" name="tipo">
                <option value="">Todos</option>
                @foreach($tiposLocal as $t)
                    <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>
                        {{ ucfirst($t) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo acciones-filtro">
            <label style="visibility:hidden;">Acción</label>
            <button class="btn" type="submit">Buscar</button>
            <button class="btn-cancelar" type="button"
                    onclick="window.location.href='{{ route('unidades.index') }}'">
                Limpiar
            </button>
        </div>
    </form>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Unidad</th>
                    <th style="width:150px;">Tipo</th>
                    <th>Ubicación</th>
                    <th style="width:280px;">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse(($unidades ?? collect()) as $u)
                    <tr>
                        <td class="center">{{ $u->id }}</td>
                        <td class="col-nombre">{{ $u->nombre }}</td>
                        <td class="center">{{ ucfirst($u->tipo ?? '—') }}</td>
                        <td>{{ $u->ubicacion ?? '—' }}</td>
                        <td>
                            <div class="acciones">
                                <button class="btn-mini btn-mini-editar" type="button"
                                    onclick="window.location.href='{{ route('unidades.edit', $u->id) }}'">
                                    Editar
                                </button>

                                <button class="btn-mini btn-mini-almacenes" type="button"
                                    onclick="window.location.href='{{ route('almacenes.index', $u->id) }}'">
                                    Almacenes
                                </button>

                                <form action="{{ route('unidades.destroy', $u->id) }}"
                                      method="POST"
                                      class="form-eliminar"
                                      data-nombre="{{ $u->nombre }}"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-mini btn-mini-eliminar" type="submit">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="vacio">
                            No hay unidades registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- MODAL ELIMINAR --}}
<div id="modalEliminar" class="modal" aria-hidden="true">
    <div class="modal-contenido" role="dialog" aria-modal="true">
        <div class="modal-header">
            <span>Confirmar eliminación</span>
            <button type="button" onclick="cerrarModalEliminar()">✕</button>
        </div>

        <div class="modal-body">
            <p style="margin:0;">
                ¿Seguro que deseas eliminar la unidad <b id="nombreUnidadModal">—</b>?
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

/* ===== TITULOS ===== */
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
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu:hover{ background:#941c1c; }

.btn-cancelar{
    background:#777;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
}
.btn-cancelar:hover{ filter:brightness(.95); }

/* secundario (para + Nueva unidad) */
.btn-secundario{
    background:#2b2b2b;
    color:#fff;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-secundario:hover{ filter:brightness(.95); }

/* ===== FILTROS ===== */
.filtros{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:end;
    margin:15px 0;
}
.campo{ min-width:240px; }
label{ font-weight:700; display:block; margin-bottom:6px; }

.input, .select{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
}
.acciones-filtro{
    display:flex;
    gap:10px;
    min-width:auto;
    align-items:end;
}
.acciones-filtro button{ height:40px; }
@media(max-width:720px){
    .campo{ min-width:100%; }
    .acciones-filtro{ width:100%; justify-content:flex-start; }
}

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
    text-align:center;
    padding:10px;
    font-weight:700;
}
.thead-titulo{
    font-weight:900;
    font-size:16px;
}
.thead-total{
    text-align:right;
    font-weight:900;
    font-size:13px;
    opacity:.95;
}
.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    vertical-align:top;
}
.center{ text-align:center; }
.col-nombre{ font-weight:800; }
.vacio{
    text-align:center;
    padding:18px;
    color:#666;
    background:#fff7f0;
}

/* ===== ACCIONES MINI ===== */
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

  /* 🔥 clave para que no se vean raros */
  min-width:110px;       /* mismo ancho visual */
  text-align:center;
  line-height:1;
  white-space:nowrap;    /* no se parte el texto */
}

.btn-mini-editar{ background:#b22b27; color:#fff; }
.btn-mini-editar:hover{ background:#941c1c; }

.btn-mini-almacenes{
    background:#2b2b2b;
    color:#fff;
}
.btn-mini-almacenes:hover{ filter:brightness(.95); }

.btn-mini-eliminar{
    background:#777;
    color:#fff;
}
.btn-mini-eliminar:hover{ filter:brightness(.95); }

/* ===== MODAL (alineado a inventarios) ===== */
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
    document.getElementById('nombreUnidadModal').textContent = nombre || '—';

    const modal = document.getElementById('modalEliminar');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden','false');

    setTimeout(() => document.getElementById('btnConfirmarEliminar')?.focus(), 0);
}

function cerrarModalEliminar(){
    formEliminarPendiente = null;
    const modal = document.getElementById('modalEliminar');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden','true');
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
        const modal = document.getElementById('modalEliminar');
        if(e.key === 'Escape' && modal?.classList.contains('show')) cerrarModalEliminar();
    });
});
</script>

@endsection
