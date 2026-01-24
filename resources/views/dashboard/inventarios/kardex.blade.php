@extends('layouts.dashboard') 

@section('titulo', 'Historial de Movimientos')

@section('contenido')
@php
    $role = auth()->user()->role ?? '';
    $esAdmin = $role === 'admin';
@endphp

<div class="contenedor">

    {{-- ================= ACCIONES SUPERIORES ================= --}}
    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        <button class="btn-regresar"
            onclick="window.location.href='{{ route('inventarios.index') }}'">
            Regresar
        </button>
    </div>


    {{-- ================= FILTROS ================= --}}
    <form method="GET" class="filtros">

        {{-- FILA 1 --}}
        <div class="fila-filtros">

            @if($esAdmin)
                <div class="filtro-grupo">
                    <label>Unidad operativa</label>
                    <select name="unidad_operativa_id" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach(($unidadesOperativas ?? []) as $uo)
                            <option value="{{ $uo->id }}"
                                {{ (string)request('unidad_operativa_id') === (string)$uo->id ? 'selected' : '' }}>
                                {{ $uo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="filtro-grupo">
                <label>Almacén</label>
                <select name="almacen_id">
                    <option value="">Todos</option>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}"
                            {{ (string)request('almacen_id') === (string)$a->id ? 'selected' : '' }}>
                            {{ $a->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filtro-grupo filtro-producto">
                <label>Producto (buscar)</label>
                <input
                    type="text"
                    name="q"
                    value="{{ $q ?? request('q') }}"
                    placeholder="Ej. leche, azúcar, tomate">
            </div>

        </div>

        {{-- FILA 2 --}}
        <div class="fila-filtros">

            <div class="filtro-grupo">
                <label>Desde</label>
                <input type="date" name="desde" value="{{ $desdeInput }}">
            </div>

            <div class="filtro-grupo">
                <label>Hasta</label>
                <input type="date" name="hasta" value="{{ $hastaInput }}">
            </div>

            <div class="acciones-filtro">
                <button type="submit" class="btn">Filtrar</button>
                <a href="{{ route('inventarios.kardex') }}" class="btn-limpiar">Limpiar</a>
            </div>

        </div>

    </form>



    {{-- ================= TABLA ================= --}}
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Almacén</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Usuario</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
            @forelse($movimientos as $m)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m->fecha_movimiento)->format('d/m/Y H:i') }}</td>
                    <td>{{ $m->producto->nombre ?? '—' }}</td>
                    <td>{{ $m->inventario->almacen->nombre ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $m->tipo_movimiento }}">
                            {{ strtoupper($m->tipo_movimiento) }}
                        </span>
                    </td>
                    <td>{{ $m->cantidad }}</td>
                    <td>{{ $m->usuario->name ?? 'Sistema' }}</td>
                    <td>{{ $m->motivo ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">No hay movimientos</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $movimientos->appends(request()->query())->links('vendor.pagination.dashboard') }}
    </div>

</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

h2{ margin:0 0 12px; }

/* ===== FILTROS ===== */
.filtros{
    display:flex;
    flex-direction:column;
    gap:14px;
    margin-bottom:20px;
}

.fila-filtros{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:flex-end;
}

.filtro-grupo{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width:220px;
}

.filtro-producto{
    flex:1; /* 🔥 el buscador crece y ordena visualmente */
}

.filtro-grupo label{
    font-weight:700;
}

/* inputs y selects IDENTICOS */
.filtros input,
.filtros select{
    height:42px;                 /* 🔥 clave */
    padding:0 12px;              /* 🔥 no vertical padding */
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-size:14px;
    line-height:42px;            /* 🔥 fuerza misma altura */
    box-sizing:border-box;
}

/* acciones */
.acciones-filtro{
    display:flex;
    gap:10px;
    align-items:flex-end;
}

/* BOTONES EXACTAMENTE IGUALES */
.btn,
.btn-limpiar{
    height:42px;                 /* 🔥 misma altura real */
    padding:0 18px;              /* 🔥 NO vertical padding */
    border-radius:8px;
    font-weight:700;
    font-size:14px;
    line-height:42px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    cursor:pointer;
    border:none;
    box-sizing:border-box;
}

/* colores */
.btn{
    background:#b22b27;
    color:#fff;
}
.btn:hover{ background:#941c1c; }

.btn-limpiar{
    background:#777;
    color:#fff;
}
.btn-limpiar:hover{ background:#5f5f5f; }



/* ===== TABLA (scroll solo aquí) ===== */
.tabla-wrap{
    overflow-x:auto;
    border-radius:10px;
}
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    min-width:980px; /* fuerza scroll si pantalla chica */
}
.tabla th{
    background:#b22b27;
    color:#fff;
    padding:10px;
    text-align:center;
    white-space:nowrap;
}
.tabla td{
    padding:8px 10px;
    text-align:center;
    border-bottom:1px solid #ddd;
    white-space:nowrap;
}

/* ===== BADGES ===== */
.badge{
    padding:5px 10px;
    border-radius:12px;
    color:#fff;
    font-size:.8em;
    display:inline-block;
}
.entrada{ background:#4caf50; }
.salida{ background:#f44336; }
.ajuste{ background:#ff9800; }

/* ===== BOTONES ===== */
.btn{
    background:#b22b27;
    color:white;
    padding:9px 14px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}
.btn:hover{ background:#941c1c; }

.btn-cancelar{
    background:#777;
    color:white;
    padding:9px 14px;
    border-radius:8px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}
.btn-cancelar:hover{ filter:brightness(.95); }

.btn-regresar{
    background:#777;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
}
</style>
@endsection
