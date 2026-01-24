@extends('layouts.dashboard')

@section('titulo', 'Administrar Pedidos')

@section('contenido')

<div class="contenedor">

    <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
        Menú principal
    </button>

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
                        // Detectar tipo de pedido:
                        // 1) si el controlador manda $p->tipo (normal/especial), úsalo
                        // 2) si sólo manda $p->es_especial (true/false), lo convertimos
                        $tipo = $p->tipo ?? (($p->es_especial ?? false) ? 'especial' : 'normal');
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
                            <button class="btn-ver"
                                onclick="window.location.href='{{ route('dashboard.pedidos.admin.detalle', $p->codigo) }}'">
                                Ver
                            </button>

                            @if(!in_array($p->estado, ['Preaprobado', 'Aprobado']))
                                <a href="{{ route('dashboard.pedidos.admin.editar', $p->codigo) }}"
                                   class="btn-editar">
                                    Editar
                                </a>
                            @else
                                <span class="badge badge-warning"
                                    title="Este pedido ya fue {{ strtolower($p->estado) }} y no puede editarse">
                                    🔒
                                </span>
                            @endif

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

    {{-- =====================
        PAGINACIÓN (10 por página desde controller)
        Mantiene filtros (appends en controller)
    ===================== --}}
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
    flex-wrap:wrap;           /* ✅ ya fuerza salto de línea */
    align-items:flex-end;     /* ✅ alinea inputs abajo */
    gap:12px;
}

/* Cada bloque de filtro */
.filtro-grupo{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width: 200px;         /* ✅ evita que se aplasten y se monten */
}

/* El buscador crece */
.filtro-grupo.grow{
    flex: 1 1 360px;          /* ✅ crece y también puede saltar */
    min-width: 320px;
}

/* Inputs */
.input-fecha,
.input-texto,
.filtros select{
    width: 100%;              /* ✅ que el input use todo el ancho del grupo */
    box-sizing: border-box;   /* ✅ evita desbordes raros */
    padding:6px 10px;
    border-radius:8px;
    border:1px solid #ccc;
    background:white;
}

/* Responsive: en pantallas chicas, todo a 1 columna */
@media (max-width: 820px){
    .filtro-grupo{
        min-width: 100%;
    }
    .filtro-grupo.grow{
        min-width: 100%;
        flex-basis: 100%;
    }
}
/* Grupo de acciones (botón limpiar) */
.filtro-grupo.acciones{
    min-width: 100%;
    align-items:flex-end;
}

/* Botón limpiar */
.btn-limpiar{
    background:#777;
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:8px;
    cursor:pointer;
    white-space:nowrap;
}

.btn-limpiar:hover{
    opacity:.85;
}

/* En móvil, que el botón ocupe todo el ancho */
@media (max-width: 820px){
    .filtro-grupo.acciones{
        min-width:100%;
        align-items:stretch;
    }
}



/* TABLA GENERAL */
.tabla-contenedor{
    margin-top:10px;
}
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

/* Hover general */
.tabla tbody tr:hover{
    background:#f0d8d5;
}

/* Estilo base para normales (blanco) */
.tabla tbody tr.pedido-normal{
    background:#ffffff;
}

/* Estilo para pedidos ESPECIALES */
.tabla tbody tr.pedido-especial{
    background:#fff7c2 !important;
}

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
}
.btn-pdf{
    background:#555;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    cursor:pointer;
}
.badge-warning{
    background:#ffe08a;
}
.btn-ver:hover,
.btn-editar:hover,
.btn-pdf:hover{
    opacity:0.8;
}

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

{{-- ===================== SCRIPTS ===================== --}}
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

    // Cambiar fecha => submit inmediato
    fechas.forEach(input => {
        input.addEventListener('change', () => form.submit());
    });

    // Escribir código => submit con debounce
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
