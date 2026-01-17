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

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:0 auto;
    box-shadow:0 0 10px rgba(0,0,0,0.08);
    font-family:'Poppins', sans-serif;
}

.barra-superior{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.grupo-botones{ display:flex; gap:10px; flex-wrap:wrap; }

.btn-menu,
.btn-accion,
.btn-cancelar,
.btn-secundario{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
}
.btn-menu:hover,
.btn-accion:hover{ background:#941c1c; }

.btn-cancelar{ background:#777; }
.btn-cancelar:hover{ background:#5f5f5f; }

.btn-secundario{
    background:#fff;
    color:#b22b27;
    border:1px solid #b22b27;
}
.btn-secundario:hover{ background:#fff7f6; }

.filtros{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:flex-end;
    justify-content:space-between;
    margin:10px 0 18px;
}
.filtros .left{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:flex-end;
}
.filtro-grupo label{
    display:block;
    font-weight:700;
    margin-bottom:6px;
}
.input, .select{
    width:260px;
    max-width:100%;
    padding:9px 10px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
}
@media(max-width:700px){
    .input, .select{ width:100%; }
    .filtros{ justify-content:flex-start; }
}

.tabla-wrap{
    border-radius:10px;
    overflow:hidden;
    background:#fff;
}

.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    margin:0;
}

.tabla thead th{
    background:#b22b27;
    color:#fff;
    padding:12px;
    text-align:center;
    border:0 !important;
}

.thead-titulo{
    padding:14px 16px !important;
    text-align:left !important;
    font-weight:900;
    font-size:16px;
}

.thead-total{
    padding:14px 16px !important;
    text-align:right !important;
    font-weight:900;
    font-size:13px;
    opacity:.95;
}

.tabla td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
}

.tabla tr:hover{ background:#f5d6d6; }

.col-nombre{ text-align:left !important; font-weight:800; }

.acciones{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
}

/* mini buttons */
.btn-mini{
    padding:7px 12px;
    border-radius:8px;
    font-weight:800;
    border:none;
    cursor:pointer;
}
.btn-mini-editar{ background:#b22b27; color:#fff; }
.btn-mini-editar:hover{ background:#941c1c; }

.btn-mini-almacenes{
    background:#fff;
    color:#b22b27;
    border:1px solid #b22b27;
}
.btn-mini-almacenes:hover{ background:#fff7f6; }

.btn-mini-eliminar{ background:#777; color:#fff; }
.btn-mini-eliminar:hover{ background:#5f5f5f; }

/* ===== MODAL ELIMINAR ===== */
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

<div class="contenedor">

    <div class="barra-superior">
        <div class="grupo-botones">
            <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
                Menú principal
            </button>
        </div>

        <div class="grupo-botones">
            <button class="btn-secundario" onclick="window.location.href='{{ route('unidades.create') }}'">
                + Nueva unidad
            </button>
        </div>
    </div>

    {{-- ✅ Filtros funcionando --}}
    <form method="GET" action="{{ route('unidades.index') }}">
        <div class="filtros">
            <div class="left">
                <div class="filtro-grupo">
                    <label>Buscar</label>
                    <input class="input"
                           type="text"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="Buscar unidad...">
                </div>

                <div class="filtro-grupo">
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
            </div>

            <div class="grupo-botones">
                <button class="btn-accion" type="submit">Buscar</button>
                <button class="btn-secundario" type="button"
                        onclick="window.location.href='{{ route('unidades.index') }}'">
                    Limpiar
                </button>
            </div>
        </div>
    </form>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th class="thead-titulo" colspan="4">Unidades operativas</th>
                    <th class="thead-total" colspan="1">Total: {{ ($unidades ?? collect())->count() }}</th>
                </tr>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Unidad</th>
                    <th style="width:150px;">Tipo</th>
                    <th>Ubicación</th>
                    <th style="width:280px;">Acciones</th>
                </tr>
            </thead>

            <tbody>
                {{-- ✅ NO ordenar aquí: el orden lo hace el controlador --}}
                @forelse(($unidades ?? collect()) as $u)
                    <tr>
                        <td>{{ $u->id }}</td>
                        <td class="col-nombre">{{ $u->nombre }}</td>
                        <td>{{ ucfirst($u->tipo ?? '—') }}</td>
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
                        <td colspan="5" style="text-align:center; padding:16px; color:#555;">
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
            <button type="button" class="btn-secundario" onclick="cerrarModalEliminar()">Cancelar</button>
            <button type="button" class="btn-accion" id="btnConfirmarEliminar">Sí, eliminar</button>
        </div>
    </div>
</div>

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
