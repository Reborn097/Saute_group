@extends('layouts.dashboard')

@section('titulo', 'Inventario por Caducidad')

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

        {{-- ✅ evita loop: regresar al index --}}
        <button class="btn-regresar"
            onclick="window.location.href='{{ route('inventarios.index') }}'">
            Regresar
        </button>
    </div>

    <h2>Inventario por caducidad / lote</h2>

    {{-- ================= FILTROS (2 filas) ================= --}}
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

            <div class="filtro-grupo">
                <label>Estado</label>
                <select name="estado">
                    <option value="">Todos</option>
                    <option value="vigente" {{ (string)request('estado') === 'vigente' ? 'selected' : '' }}>Vigente (+15 días)</option>
                    <option value="por_vencer" {{ (string)request('estado') === 'por_vencer' ? 'selected' : '' }}>Por vencer (0–15 días)</option>
                    <option value="vencido" {{ (string)request('estado') === 'vencido' ? 'selected' : '' }}>Vencido</option>
                    <option value="sin_fecha" {{ (string)request('estado') === 'sin_fecha' ? 'selected' : '' }}>Sin fecha</option>
                </select>
            </div>

        </div>

        {{-- FILA 2 --}}
        <div class="fila-filtros">
            <div class="acciones-filtro">
                <button class="btn" type="submit">Filtrar</button>
                <a class="btn-limpiar" href="{{ route('inventarios.caducidades') }}">Limpiar</a>
            </div>
        </div>

    </form>

    {{-- ================= TABLA (scroll solo aquí) ================= --}}
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Descripción - Contenido</th>
                    <th>Unidad contenido</th>
                    <th>Almacén</th>
                    <th>Lote</th>
                    <th>Caducidad</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            @forelse($caducidades as $c)
                @php
                    $dias = $c->caducidad
                        ? now()->diffInDays(\Carbon\Carbon::parse($c->caducidad), false)
                        : null;
                    $pres = $c->presentacion ?? null;
                    $prod = $pres?->producto ?? $c->producto ?? null;
                    $desc = $pres?->descripcion ?? '—';
                    if (!empty($pres?->contenido)) {
                        $desc = trim($pres?->descripcion ?? '') . ' - ' . $pres?->contenido;
                    }
                @endphp
                <tr>
                    <td>{{ $prod?->nombre ?? '—' }}</td>
                    <td>{{ $prod?->marca ?? '—' }}</td>
                    <td>{{ $desc }}</td>
                    <td>{{ $pres?->unidad_contenido ?? '—' }}</td>
                    <td>{{ $c->almacen->nombre ?? '—' }}</td>
                    <td>{{ $c->lote ?? '—' }}</td>
                    <td>{{ $c->caducidad ?? '—' }}</td>
                    <td>{{ $c->cantidad }}</td>
                    <td>
                        @if(is_null($dias))
                            <span class="badge ok">Sin fecha</span>
                        @elseif($dias < 0)
                            <span class="badge vencido">Vencido</span>
                        @elseif($dias <= 15)
                            <span class="badge alerta">Por vencer</span>
                        @else
                            <span class="badge ok">Vigente</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">No hay registros</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $caducidades->appends(request()->query())->links('vendor.pagination.dashboard') }}
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

/* ===== FILTROS EN 2 FILAS (sin scrollbar) ===== */
.filtros{
    display:flex;
    flex-direction:column;
    gap:14px;
    margin-bottom:20px;
}

.fila-filtros{
    display:flex;
    gap:12px;
    flex-wrap:wrap;              /* 🔥 baja en vertical */
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

/* selects iguales */
.filtros select{
    height:42px;
    padding:0 12px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-size:14px;
    line-height:42px;
    box-sizing:border-box;
}

/* acciones */
.acciones-filtro{
    display:flex;
    gap:10px;
    align-items:flex-end;
}

/* botones idénticos */
.btn,
.btn-limpiar{
    height:42px;
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
}

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

.btn-regresar{
    background:#777;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
}

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

.badge{
    padding:5px 10px;
    border-radius:12px;
    color:#fff;
    font-size:.8em;
    display:inline-block;
}
.ok{ background:#4caf50; }
.alerta{ background:#ff9800; }
.vencido{ background:#f44336; }
</style>
@endsection
