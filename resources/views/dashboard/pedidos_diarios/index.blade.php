@extends('layouts.dashboard')

@section('titulo', 'Pedidos Diarios')

@section('contenido')

<div class="contenedor">

    {{-- =========================
        ACCIONES SUPERIORES
    ========================= --}}
    <div class="acciones-superior">
        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        <div class="acciones-crear">
            @if($puedeCrear)
                <button class="btn" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pan.create') }}'">
                    Nuevo pedido PAN
                </button>

                <button class="btn" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.tortilla.create') }}'">
                    Nuevo pedido TORTILLA
                </button>
            @endif
        </div>
    </div>

    <h3 class="titulo">Listado de pedidos diarios</h3>

    {{-- =========================
        FILTROS
    ========================= --}}
    <form method="GET" class="filtros-grid">

        <div class="campo">
            <label>Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}">
        </div>

        <div class="campo">
            <label>Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}">
        </div>

        <div class="campo campo-codigo">
            <label>Código</label>
            <input type="text"
                   name="codigo"
                   value="{{ request('codigo') }}"
                   placeholder="Ej: DENE26001">
        </div>

    </form>

    {{-- BOTÓN LIMPIAR (SEPARADO A PROPÓSITO) --}}
    <div class="acciones-limpiar">
        <a href="{{ route('dashboard.pedidos_diarios.index') }}" class="btn-limpiar">
            Limpiar filtros
        </a>
    </div>

    {{-- =========================
        TABLA
    ========================= --}}
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Semana</th>
                    <th>Tipo</th>
                    <th>Unidad operativa</th>
                    <th>Total $</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
            @forelse($pedidos as $p)
                <tr class="{{ $p->estado === 'Rechazado' ? 'fila-rechazada' : '' }}">

                    <td><strong>{{ $p->codigo }}</strong></td>

                    <td>
                        {{ \Carbon\Carbon::parse($p->semana_inicio)->format('d/m/Y') }}
                        –
                        {{ \Carbon\Carbon::parse($p->semana_fin)->format('d/m/Y') }}
                    </td>

                    <td><strong>{{ $p->tipo }}</strong></td>

                    <td>{{ $p->unidadOperativa->nombre ?? '—' }}</td>

                    <td>${{ number_format($p->total, 2) }}</td>

                    <td>
                        <span class="badge estado-{{ strtolower($p->estado) }}">
                            {{ $p->estado }}
                        </span>
                    </td>

                    <td class="acciones-tabla">
                        <a class="btn btn-detalles"
                           href="{{ route('dashboard.pedidos_diarios.show', $p->id) }}">
                            Detalles
                        </a>

                        @if($p->puedeEditar)
                            <a class="btn btn-editar"
                               href="{{ route('dashboard.pedidos_diarios.edit', $p->id) }}">
                                Editar
                            </a>
                        @else
                            <span class="btn btn-editar disabled">🔒</span>
                        @endif

                        <a class="btn btn-pdf"
                           href="{{ route('dashboard.pedidos_diarios.pdf', $p->id) }}">
                            PDF
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="vacio">No hay pedidos diarios.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- =========================
    ESTILOS
========================= --}}
<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:14px;
    max-width:1200px;
    margin:auto;
}

/* Acciones */
.acciones-superior{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

.acciones-crear{
    display:flex;
    gap:10px;
}

.titulo{
    font-size:20px;
    font-weight:700;
    margin-bottom:15px;
}

/* Filtros */
.filtros-grid{
    display:grid;
    grid-template-columns: 180px 180px 1fr;
    gap:15px;
}

.campo label{
    display:block;
    font-weight:600;
    margin-bottom:4px;
}

.campo input{
    width:100%;
    padding:7px;
    border-radius:6px;
    border:1px solid #ccc;
}

/* Limpiar */
.acciones-limpiar{
    margin-top:10px;
    margin-bottom:18px;
}

.btn-limpiar{
    background:#777;
    color:white;
    padding:7px 14px;
    border-radius:8px;
    text-decoration:none;
}

/* Tabla */
.tabla-contenedor{
    margin-top:10px;
}

.tabla{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
}

.tabla th{
    background:#b22b27;
    color:white;
    padding:12px;
    text-align:center;
}

.tabla td{
    padding:11px;
    text-align:center;
    border-bottom:1px solid #eee;
}

.tabla tr:hover{
    background:#f5d6d6;
}

.fila-rechazada{
    background:#f9dddd;
}

/* Badges */
.badge{
    padding:5px 12px;
    border-radius:14px;
    font-weight:600;
    font-size:13px;
}

.estado-pendiente{ background:#ffd966; }
.estado-visto{ background:#6fb7ff; color:white; }
.estado-preaprobado{ background:#a3d977; }
.estado-aprobado{ background:#4caf50; color:white; }
.estado-rechazado{ background:#d9534f; color:white; }

/* Botones */
.btn{
    border:none;
    padding:6px 12px;
    border-radius:8px;
    cursor:pointer;
    color:white;
    text-decoration:none;
}

.btn-menu{ background:#999; }
.btn-crear{ background:#b22b27; }
.btn-detalles{ background:#b22b27; }
.btn-editar{ background:#f0ad4e; }
.btn-pdf{ background:#555; }

.btn.disabled{
    background:#ccc;
    cursor:not-allowed;
}

.acciones-tabla{
    display:flex;
    gap:6px;
    justify-content:center;
}

.vacio{
    text-align:center;
    padding:18px;
    font-style:italic;
}
</style>

@endsection
