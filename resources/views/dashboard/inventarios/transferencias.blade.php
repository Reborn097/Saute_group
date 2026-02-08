@extends('layouts.dashboard')
@section('titulo', 'Reporte de transferencias')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menu principal
        </button>

        <button class="btn-regresar"
            onclick="window.location.href='{{ route('inventarios.index') }}'">
            Regresar
        </button>
    </div>

    <form method="GET" class="filtros">
        <div class="fila-filtros">
            <div class="filtro-grupo">
                <label>Unidad destino</label>
                <select name="unidad_operativa_id" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach(($unidadesOperativas ?? []) as $uo)
                        <option value="{{ $uo->id }}" {{ (string)($uoId ?? '-') === (string)$uo->id ? 'selected' : '' }}>
                            {{ $uo->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filtro-grupo">
                <label>Almacen origen (CEDIS)</label>
                <select name="almacen_origen_id">
                    <option value="">Todos</option>
                    @foreach(($almacenesOrigen ?? []) as $a)
                        <option value="{{ $a->id }}" {{ (string)($almacenOrigenId ?? '') === (string)$a->id ? 'selected' : '' }}>
                            {{ $a->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filtro-grupo">
                <label>Almacen destino</label>
                <select name="almacen_destino_id">
                    <option value="">Todos</option>
                    @foreach(($almacenesDestino ?? []) as $a)
                        <option value="{{ $a->id }}" {{ (string)($almacenDestinoId ?? '') === (string)$a->id ? 'selected' : '' }}>
                            {{ $a->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filtro-grupo">
                <label>Folio</label>
                <input type="text" name="folio" value="{{ $folio ?? '' }}" placeholder="TRF-...">
            </div>
        </div>

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
                <a class="btn-limpiar" href="{{ route('inventarios.transferencias') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="resumen-grid">
        <div class="resumen-card">
            <span>Transferencias</span>
            <strong>{{ number_format((int)($stats->total_transferencias ?? 0), 0) }}</strong>
        </div>
        <div class="resumen-card">
            <span>Movimientos</span>
            <strong>{{ number_format((int)($stats->total_movimientos ?? 0), 0) }}</strong>
        </div>
        <div class="resumen-card">
            <span>Cantidad total</span>
            <strong>{{ number_format((float)($stats->total_cantidad ?? 0), 2) }}</strong>
        </div>
        <div class="resumen-card">
            <span>Costo total</span>
            <strong>$ {{ number_format((float)($stats->total_costo ?? 0), 2) }}</strong>
        </div>
    </div>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Folio</th>
                    <th>Unidad destino</th>
                    <th>Almacen destino</th>
                    <th>Producto</th>
                    <th>Presentacion</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Costo unit.</th>
                    <th class="num">Costo total</th>
                    <th>Usuario</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
            @forelse($transferencias as $t)
                @php
                    $presentacion = $t->presentacion ?? '-';
                    if (!empty($t->presentacion_contenido)) {
                        $presentacion = trim(($t->presentacion ?? '-') . ' - ' . $t->presentacion_contenido);
                    }
                @endphp
                <tr>
                    <td>{{ $t->fecha }}</td>
                    <td>{{ $t->referencia ?? '-' }}</td>
                    <td>{{ $t->unidad_destino ?? '-' }}</td>
                    <td>{{ $t->almacen_destino ?? '-' }}</td>
                    <td>{{ $t->producto ?? '-' }}</td>
                    <td>{{ $presentacion }}</td>
                    <td class="num">{{ number_format((float)$t->cantidad, 2) }}</td>
                    <td class="num">$ {{ number_format((float)($t->costo_unitario ?? 0), 2) }}</td>
                    <td class="num">$ {{ number_format((float)($t->costo_total ?? 0), 2) }}</td>
                    <td>{{ $t->usuario ?? '-' }}</td>
                    <td>{{ $t->motivo ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="vacio">Sin transferencias para los filtros seleccionados.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $transferencias->links('vendor.pagination.dashboard') }}
    </div>

</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1200px;
    margin:auto;
}

.acciones-superior{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:14px;
}

.btn-menu,
.btn-regresar{
    height:42px;
    padding:0 16px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    background:#777;
    color:#fff;
    font-weight:700;
    line-height:42px;
}
.btn-menu:hover,
.btn-regresar:hover{ filter:brightness(.95); }

.filtros{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin:0 0 14px;
}

.fila-filtros{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:flex-end;
}

.filtro-grupo{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width:220px;
    flex:1 1 220px;
}

.filtro-grupo label{ font-weight:700; }

.filtro-grupo input,
.filtro-grupo select{
    height:42px;
    padding:0 12px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-size:14px;
    box-sizing:border-box;
}

.acciones-filtro{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn,
.btn-limpiar{
    height:42px;
    padding:0 16px;
    border-radius:8px;
    font-weight:700;
    line-height:42px;
    text-decoration:none;
    border:none;
    cursor:pointer;
}
.btn{ background:#b22b27; color:#fff; }
.btn:hover{ background:#941c1c; }
.btn-limpiar{ background:#777; color:#fff; }
.btn-limpiar:hover{ background:#5f5f5f; }

.resumen-grid{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:10px;
    margin:10px 0 14px;
}

.resumen-card{
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
    border-radius:10px;
    padding:10px 12px;
    display:flex;
    flex-direction:column;
    gap:4px;
}

.resumen-card span{
    color:#666;
    font-size:12px;
    font-weight:700;
}

.resumen-card strong{
    font-size:18px;
    color:#b22b27;
    line-height:1.1;
}

.tabla-wrap{
    overflow-x:auto;
    border-radius:10px;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
}

.tabla{
    width:100%;
    min-width:1040px;
    border-collapse:collapse;
    background:#fff;
}

.tabla th{
    background:#b22b27;
    color:#fff;
    text-align:left;
    padding:10px;
    white-space:nowrap;
}

.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    white-space:nowrap;
}

.tabla .num{ text-align:right; }
.tabla td:nth-child(11){
    max-width:260px;
    overflow:hidden;
    text-overflow:ellipsis;
}

.vacio{
    text-align:center;
    color:#666;
    background:#fff7f0;
    padding:16px;
}

.paginacion-wrap{ margin-top:12px; }

@media (max-width:980px){
    .contenedor{ max-width:100%; padding:18px; }
    .resumen-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
}

@media (max-width:600px){
    .acciones-superior .btn-menu,
    .acciones-superior .btn-regresar{
        width:100%;
    }
    .acciones-filtro{
        width:100%;
    }
    .acciones-filtro .btn,
    .acciones-filtro .btn-limpiar{
        width:100%;
    }
    .resumen-grid{ grid-template-columns:1fr; }
}
</style>
@endsection
