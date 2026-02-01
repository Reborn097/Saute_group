@extends('layouts.dashboard')
@section('titulo','Inventarios')

@section('contenido')
@php
    $role = auth()->user()->role ?? '';
    $esAdmin = $role === 'admin';
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>
    </div>

    {{-- ================= FILTROS (2 filas) ================= --}}
    <form method="GET" class="filtros">

        {{-- FILA 1: Selects --}}
        <div class="fila-filtros">

            @if($esAdmin)
                <div class="filtro-grupo">
                    <label>Unidad operativa</label>
                    <select name="unidad_operativa_id" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach(($unidadesOperativas ?? []) as $uo)
                            <option value="{{ $uo->id }}"
                                {{ (string)($uoId ?? '') === (string)$uo->id ? 'selected' : '' }}>
                                {{ $uo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="filtro-grupo">
                <label>Almacén</label>
                <select name="almacen_id" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}"
                            {{ (string)($almacenId ?? '') === (string)$a->id ? 'selected' : '' }}>
                            {{ $a->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- FILA 2: Acciones --}}
        <div class="fila-filtros">
            <div class="acciones-filtro">
                <a class="btn" href="{{ route('inventarios.movimiento.form') }}">Registrar movimiento</a>
                <a class="btn" href="{{ route('inventarios.kardex') }}">Historial</a>
                <a class="btn" href="{{ route('inventarios.caducidades') }}">Caducidades</a>
            </div>
        </div>

    </form>

    {{-- ================= TABLA (scroll solo aquí) ================= --}}
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Almacén</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Descripción - Contenido</th>
                    <th>Unidad contenido</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
            @forelse($inventarios as $inv)
                @php
                    $pres = $inv->presentacion ?? null;
                    $prod = $pres?->producto ?? $inv->producto ?? null;
                    $desc = $pres?->descripcion ?? '—';
                    if (!empty($pres?->contenido)) {
                        $desc = trim($pres?->descripcion ?? '') . ' - ' . $pres?->contenido;
                    }
                @endphp
                <tr>
                    <td>{{ $inv->almacen->nombre ?? '—' }}</td>
                    <td>{{ $prod?->nombre ?? '—' }}</td>
                    <td>{{ $prod?->marca ?? '—' }}</td>
                    <td>{{ $desc }}</td>
                    <td>{{ $pres?->unidad_contenido ?? '—' }}</td>
                    <td>{{ number_format((float)$inv->cantidad, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">Sin registros.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- ✅ paginación --}}
    <div class="paginacion-wrap">
        {{ $inventarios->links('vendor.pagination.dashboard') }}
    </div>

</div>

<style>
/* ===== CONTENEDOR ===== */
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

h2{ margin:0 0 12px; }

/* ===== FILTROS (2 filas, sin scrollbar) ===== */
.filtros{
    display:flex;
    flex-direction:column;
    gap:14px;
    margin:10px 0 18px;
}

.fila-filtros{
    display:flex;
    gap:12px;
    flex-wrap:wrap;       /* 🔥 baja en vertical */
    align-items:flex-end;
}

.filtro-grupo{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width:240px;
}

.filtro-grupo label{
    font-weight:700;
}

.filtros select{
    height:42px;          /* 🔥 altura fija */
    padding:0 12px;       /* 🔥 sin padding vertical */
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-size:14px;
    line-height:42px;
    box-sizing:border-box;
}

.acciones-filtro{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:flex-end;
}

/* ===== BOTONES (idénticos) ===== */
.btn{
    height:42px;          /* 🔥 misma altura que selects */
    padding:0 18px;
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

    background:#b22b27;
    color:#fff;
}
.btn:hover{ background:#941c1c; }

/* ===== TABLA (scroll solo aquí) ===== */
.tabla-wrap{
    overflow-x:auto;
    border-radius:10px;
}

.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    min-width:900px; /* fuerza scroll si pantalla chica */
}

.tabla thead th{
    background:#b22b27;
    color:#fff;
    padding:10px;
    text-align:center;
    white-space:nowrap;
}

.tabla td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #ddd;
    white-space:nowrap;
}

/* paginación */
.paginacion-wrap{ margin-top:15px; }
</style>
@endsection
