@extends('layouts.dashboard')

@section('titulo', 'Consultar Pedidos')

@section('contenido')
<div class="contenedor">

    {{-- ============================
          BOTÓN MENÚ PRINCIPAL
    ============================= --}}
    <div class="acciones-superior">
        @php
            $role = auth()->user()->role ?? '';
            $esAdmin = in_array($role, ['admin','encargado_pedidos']);
            $rutaMenu = $esAdmin ? route('dashboard.admin') : route('dashboard');
        @endphp

        <button class="btn-menu" onclick="window.location.href='{{ $rutaMenu }}'">
            Menú principal
        </button>
    </div>

    {{-- ============================
          FILTROS (GET) + AUTO-UPDATE + LIMPIAR
    ============================= --}}
    <form method="GET" action="{{ url()->current() }}" class="filtros" id="formFiltros">

        {{-- Tipo (backend) --}}
        <div class="filtro-grupo">
            <label for="tipo"><strong>Mostrar:</strong></label>
            <select name="tipo" id="tipo">
                <option value="todos" {{ (request('tipo','todos') == 'todos') ? 'selected' : '' }}>Todos</option>
                <option value="normales" {{ (request('tipo') == 'normales') ? 'selected' : '' }}>Pedidos normales</option>
                <option value="especiales" {{ (request('tipo') == 'especiales') ? 'selected' : '' }}>Pedidos especiales</option>
            </select>
        </div>

        {{-- Rango de fechas --}}
        <div class="filtro-grupo">
            <label><strong>Desde:</strong></label>
            <input type="date" name="desde" value="{{ request('desde', $desde ?? '') }}" class="input-fecha">
        </div>

        <div class="filtro-grupo">
            <label><strong>Hasta:</strong></label>
            <input type="date" name="hasta" value="{{ request('hasta', $hasta ?? '') }}" class="input-fecha">
        </div>

        {{-- Buscar por código --}}
        <div class="filtro-grupo grow">
            <label><strong>Código:</strong></label>
            <input
                type="text"
                name="codigo"
                value="{{ request('codigo', $codigo ?? '') }}"
                class="input-texto"
                placeholder="Ej: PED-2026-000123"
                autocomplete="off"
            >
        </div>

        {{-- Botón Limpiar --}}
        <div class="filtro-grupo acciones">
            <button type="button"
                class="btn-limpiar"
                onclick="window.location.href='{{ url()->current() }}'">
                Limpiar
            </button>
        </div>

    </form>

    {{-- ============================
          TABLA DE PEDIDOS (RESPONSIVE)
    ============================= --}}
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha solicitud</th>
                    <th>Usuario</th>
                    <th>Total</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th style="width:160px;">Acción</th>
                </tr>
            </thead>

            <tbody>
                @forelse($pedidos as $pedido)
                    <tr class="{{ $pedido->es_especial ? 'row-especial' : '' }}">
                        <td class="td-nowrap">{{ $pedido->codigo }}</td>

                        <td class="td-nowrap">{{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</td>

                        <td>{{ $pedido->usuario->name ?? '—' }}</td>

                        <td class="td-nowrap">${{ number_format($pedido->total, 2) }}</td>

                        {{-- TIPO DEL PEDIDO --}}
                        <td class="td-nowrap">
                            @if($pedido->es_especial)
                                <span class="badge tipo-especial">Especial</span>
                            @else
                                <span class="badge tipo-normal">Normal</span>
                            @endif
                        </td>

                        {{-- ESTADOS --}}
                        <td class="td-nowrap">
                            @php
                                $estadoRaw = trim((string)($pedido->estado ?? ''));
                                $estado = mb_strtolower($estadoRaw);
                            @endphp

                            @switch($estado)
                                @case('pendiente')
                                    <span class="badge estado-pendiente">Pendiente</span>
                                    @break

                                @case('visto')
                                    <span class="badge estado-en-proceso">Visto</span>
                                    @break

                                @case('en revisión')
                                @case('en revision')
                                    <span class="badge estado-revision">En revisión</span>
                                    @break

                                @case('preaprobado')
                                    <span class="badge estado-pre-aprobado">Preaprobado</span>
                                    @break

                                @case('aprobado')
                                    <span class="badge estado-aprobado">Aprobado</span>
                                    @break

                                @case('rechazado')
                                    <span class="badge estado-finalizado">Rechazado</span>
                                    @break

                                @case('finalizado')
                                    <span class="badge estado-finalizado">Finalizado</span>
                                    @break

                                @default
                                    <span class="badge estado-revision">{{ $estadoRaw ?: '—' }}</span>
                            @endswitch
                        </td>

                        <td>
                            <div class="acciones-tabla">
                                <button class="btn-ver"
                                    onclick="window.location.href='{{ route('dashboard.pedidos.detalle', $pedido->codigo) }}'">
                                    Visualizar
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No hay pedidos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINACIÓN --}}
    @if(method_exists($pedidos, 'links'))
        {{ $pedidos->links('vendor.pagination.dashboard') }}
    @endif

</div>

<style>
.contenedor{
    background-color:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:0 auto;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
    font-family:'Poppins', sans-serif;
}

/* TOP */
.acciones-superior{
    display:flex;
    justify-content:flex-start;
    margin-bottom:20px;
}

/* BOTÓN MENU */
.btn-menu{
    background-color:#b22b27;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    font-weight:800;
    cursor:pointer;
    font-family:'Poppins', sans-serif;
    white-space:nowrap;
}
.btn-menu:hover{ background-color:#941c1c; }

/* FILTROS */
.filtros{
    margin-bottom:20px;
    display:flex;
    flex-wrap:wrap;
    align-items:flex-end;
    gap:12px;
}
.filtro-grupo{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width:200px;
}
.filtro-grupo.grow{
    flex:1 1 360px;
    min-width:320px;
}

.input-fecha,
.input-texto,
.filtros select{
    width:100%;
    box-sizing:border-box;
    padding:8px 10px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-family:'Poppins', sans-serif;
}
.filtro-grupo.acciones{
    min-width:auto;
    display:flex;
    justify-content:flex-end;
}
.btn-limpiar{
    background:#777;
    color:white;
    border:none;
    padding:10px 14px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
    font-family:'Poppins', sans-serif;
    white-space:nowrap;
}
.btn-limpiar:hover{ opacity:.85; }

@media (max-width:820px){
    .filtro-grupo{ min-width:100%; }
    .filtro-grupo.grow{ min-width:100%; flex-basis:100%; }
    .filtro-grupo.acciones{ width:100%; }
    .btn-limpiar{ width:100%; }
}

/* ✅ TABLA RESPONSIVE */
.tabla-wrap{
    overflow-x:auto;
    border-radius:10px;
}
.tabla{
    width:100%;
    min-width:900px; /* ✅ fuerza scroll en móvil */
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    border-collapse:collapse;
}
.tabla th{
    background-color:#b22b27;
    color:#fff;
    padding:12px;
    text-align:center;
    font-weight:800;
    white-space:nowrap;
}
.tabla td{
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:center;
    vertical-align:middle;
}

/* evitar que códigos/fechas revienten */
.td-nowrap{ white-space:nowrap; }

/* fila especial */
.row-especial{ background-color:#fff3cd !important; }

/* badges */
.badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:6px 14px;
    border-radius:999px;
    font-weight:800;
    font-family:'Poppins', sans-serif;
    white-space:nowrap;
}
/* tipo */
.tipo-normal{ background:#6c8793; color:#fff; }
.tipo-especial{ background:#ff9800; color:#fff; }
/* estados */
.estado-pendiente{ background:#ffe08a; color:#5c3d00; }
.estado-en-proceso{ background:#66b3ff; color:#fff; }
.estado-pre-aprobado{ background:#a3d977; color:#244a00; }
.estado-aprobado{ background:#4caf50; color:#fff; }
.estado-finalizado{ background:#9e66ff; color:#fff; }
.estado-revision{ background:#ffcc66; color:#5c3d00; }

/* acción */
.acciones-tabla{
    display:flex;
    justify-content:center;
    align-items:center;
}
.btn-ver{
    background-color:#b22b27;
    color:#fff;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
    border:none;
    font-weight:800;
    font-family:'Poppins', sans-serif;
    white-space:nowrap;
}
.btn-ver:hover{ background-color:#941c1c; }

.text-center{
    text-align:center;
    padding:15px;
    font-style:italic;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form   = document.getElementById('formFiltros');
    const tipo   = document.getElementById('tipo');
    const fechas = form.querySelectorAll('input[type="date"]');
    const codigo = form.querySelector('input[name="codigo"]');

    let timeout = null;

    if (tipo) tipo.addEventListener('change', () => form.submit());

    fechas.forEach(input => {
        input.addEventListener('change', () => form.submit());
    });

    if (codigo) {
        codigo.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => form.submit(), 500);
        });
    }
});
</script>

@endsection
