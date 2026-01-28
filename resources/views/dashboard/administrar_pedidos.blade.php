@extends('layouts.dashboard')

@section('titulo', 'Administrar Pedidos')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';

    // Staff (administración)
    $esStaff = in_array($role, ['admin','encargado_pedidos','ceo']);

    // Operativos permitidos (NO admin)
    $esOperativoPedidos = in_array($role, ['encargado_cocina','encargado_cafeteria']);
@endphp

<div class="contenedor">

    {{-- Menú principal (si no eres staff, te mando al dashboard normal) --}}
    <button class="btn-menu"
        onclick="window.location.href='{{ $esStaff ? route('dashboard.admin') : route('dashboard') }}'">
        Menú principal
    </button>
     @if($esStaff)
        <a href="{{ route('dashboard.pedidos.admin.reporte_proveedor') }}" class="btn">
            Reporte por proveedor
        </a>
    @endif

    {{-- =========================
        FILTROS (GET) + AUTO-UPDATE
        - Fechas y código => recargan solitos (sin botón Buscar)
        - Tipo normal/especial => sigue siendo filtro front (JS)
    ========================= --}}
    <form method="GET" action="{{ url()->current() }}" class="filtros filtros-wrap" id="formFiltros">

        {{-- Filtro tipo (solo visual, JS) --}}
        <div class="filtro-grupo">
            <label for="filtroTipo"><strong>Mostrar:</strong></label>
            <select id="filtroTipo">
                <option value="todos">Todos</option>
                <option value="normal">Pedidos normales</option>
                <option value="especial">Pedidos especiales</option>
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

        {{-- Si tu controlador usa ?tipo=... (backend), lo conservamos --}}
        @if(request()->has('tipo'))
            <input type="hidden" name="tipo" value="{{ request('tipo') }}">
        @endif

        {{-- Si tu controlador usa ?estado=... (backend), lo conservamos --}}
        @if(request()->has('estado'))
            <input type="hidden" name="estado" value="{{ request('estado') }}">
        @endif

        <div class="filtro-grupo acciones">
            <button type="button"
                class="btn-limpiar"
                onclick="window.location.href='{{ url()->current() }}'">
                Limpiar
            </button>
        </div>

    </form>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha solicitud</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($pedidos as $p)
                    @php
                        // Tipo
                        $tipo = $p->tipo ?? (($p->es_especial ?? false) ? 'especial' : 'normal');

                        // Permiso de editar según rol:
                        // - Staff: puede editar si NO está Preaprobado/Aprobado
                        // - Operativo: puede editar SOLO si sigue Pendiente (antes de Visto)
                        $puedeEditar = false;

                        if($esStaff){
                            $puedeEditar = !in_array($p->estado, ['Preaprobado', 'Aprobado']);
                        }elseif($esOperativoPedidos){
                            $puedeEditar = ($p->estado === 'Pendiente');
                        }
                    @endphp

                    <tr class="fila-pedido pedido-{{ $tipo }}" data-tipo="{{ $tipo }}">
                        <td>{{ $p->codigo }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->fecha_solicitud)->format('d/m/Y') }}</td>
                        <td>${{ number_format($p->total, 2) }}</td>

                        <td>
                            <span class="badge estado-{{ strtolower(str_replace(' ', '-', $p->estado)) }}">
                                {{ $p->estado }}
                            </span>
                        </td>

                        <td>
                            {{-- Ver --}}
                            <button class="btn-ver"
                                onclick="window.location.href='{{ route('dashboard.pedidos.admin.detalle', $p->codigo) }}'">
                                Ver
                            </button>

                            {{-- Editar --}}
                            @if($puedeEditar)
                                <a href="{{ route('dashboard.pedidos.admin.editar', $p->codigo) }}"
                                   class="btn-editar">
                                    Editar
                                </a>
                            @else
                                @if($esStaff)
                                    <span class="badge badge-warning"
                                        title="Este pedido ya fue {{ strtolower($p->estado) }} y no puede editarse">
                                        🔒
                                    </span>
                                @elseif($esOperativoPedidos)
                                    <span class="badge badge-warning"
                                        title="Este pedido ya fue visto y ya no puedes editarlo">
                                        🔒
                                    </span>
                                @else
                                    <span class="badge badge-warning"
                                        title="No tienes permiso para editar pedidos">
                                        🔒
                                    </span>
                                @endif
                            @endif

                            {{-- PDF --}}
                            <button class="btn-pdf"
                                onclick="window.location.href='{{ route('dashboard.pedidos.admin.pdf', $p->codigo) }}'">
                                PDF
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:18px; text-align:center;">
                            No hay pedidos con esos filtros.
                        </td>
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
/* CONTENEDOR GENERAL */
.contenedor{
    background:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* FILTROS */
.filtros{
    margin-top:15px;
    margin-bottom:10px;
    display:flex;
    flex-wrap:wrap;
    align-items:flex-end;
    gap:12px;
}
.filtro-grupo{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width: 200px;
}
.filtro-grupo.grow{
    flex: 1 1 360px;
    min-width: 320px;
}
.input-fecha,
.input-texto,
.filtros select{
    width: 100%;
    box-sizing: border-box;
    padding:6px 10px;
    border-radius:8px;
    border:1px solid #ccc;
    background:white;
}
@media (max-width: 820px){
    .filtro-grupo{ min-width: 100%; }
    .filtro-grupo.grow{
        min-width: 100%;
        flex-basis: 100%;
    }
}
.filtro-grupo.acciones{
    min-width: 100%;
    align-items:flex-end;
}
.btn-limpiar{
    background:#777;
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:8px;
    cursor:pointer;
    white-space:nowrap;
}
.btn-limpiar:hover{ opacity:.85; }
@media (max-width: 820px){
    .filtro-grupo.acciones{
        min-width:100%;
        align-items:stretch;
    }
}

/* TABLA GENERAL */
.tabla-contenedor{ margin-top:10px; }
.tabla{
    width:100%;
    border-collapse:collapse;
    border-radius:12px;
    overflow:hidden;
    background:white;
    box-shadow:0 3px 5px rgba(0,0,0,0.1);
}
.tabla th{
    background:#b22b27;
    color:white;
    padding:12px;
    text-align:center;
    font-weight:700;
}
.tabla td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
    font-size:15px;
}

/* Alternar filas SOLO para pedidos normales */
.tabla tbody tr.pedido-normal:nth-child(even){
    background:#f7f1ef;
}
.tabla tbody tr:hover{ background:#f0d8d5; }
.tabla tbody tr.pedido-normal{ background:#ffffff; }
.tabla tbody tr.pedido-especial{ background:#fff7c2 !important; }

/* BOTONES */
.btn-menu{
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 15px;
    border-radius:8px;
    cursor:pointer;
}
.btn-ver{
    background:#b22b27;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    margin-right:5px;
    cursor:pointer;
}
.btn-editar{
    background:#d98f00;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    margin-right:5px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}
.btn-pdf{
    background:#555;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    cursor:pointer;
}
.badge-warning{ background:#ffe08a; }
.btn-ver:hover,
.btn-editar:hover,
.btn-pdf:hover{ opacity:0.8; }

/* ETIQUETAS DE ESTADO */
.badge{
    padding:5px 12px;
    border-radius:15px;
    font-size:13px;
    font-weight:600;
    color:#222;
}
.estado-en-revisión{ background:#ffcc66; }
.estado-pendiente{ background:#ffe08a; }
.estado-en-proceso{ background:#66b3ff; color:white; }
.estado-pre-aprobado{ background:#a3d977; }
.estado-aprobado{ background:#4caf50; color:white; }
.estado-finalizado{ background:#9e66ff; color:white; }

/* PAGINACIÓN */
.paginacion{
    margin-top:14px;
    display:flex;
    justify-content:center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ====== filtro tipo (front) ======
    const selectFiltro = document.getElementById('filtroTipo');
    const filas = document.querySelectorAll('.fila-pedido');

    selectFiltro.addEventListener('change', function () {
        const filtro = this.value;  // 'todos' | 'normal' | 'especial'

        filas.forEach(tr => {
            const tipo = tr.dataset.tipo || 'normal';
            tr.style.display = (filtro === 'todos' || filtro === tipo) ? '' : 'none';
        });
    });

    // ====== auto-update (GET) ======
    const form   = document.getElementById('formFiltros');
    const fechas = form.querySelectorAll('input[type="date"]');
    const codigo = form.querySelector('input[name="codigo"]');

    let timeout = null;

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

<script>
window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>

@endsection
