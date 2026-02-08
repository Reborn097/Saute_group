@extends('layouts.dashboard')
@section('titulo', 'Imprimir Transferencias Por Folio')

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

    <h2>Imprimir transferencias por folio</h2>

    @if($errors->any())
        <div class="alerta-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" class="filtros">
        <div class="filtro-grupo">
            <label>Folio</label>
            <input type="text" name="folio" value="{{ $folio ?? '' }}" placeholder="TRF-...">
        </div>

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
            <a href="{{ route('inventarios.transferencias.folios') }}" class="btn-limpiar">Limpiar</a>
        </div>
    </form>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Movimientos</th>
                    <th>Cantidad total</th>
                    <th>Accion</th>
                </tr>
            </thead>
            <tbody>
            @forelse($folios as $f)
                <tr>
                    <td><strong>{{ $f->folio }}</strong></td>
                    <td>{{ $f->fecha }}</td>
                    <td>{{ number_format((int)$f->total_movimientos, 0) }}</td>
                    <td>{{ number_format((float)$f->total_cantidad, 2) }}</td>
                    <td>
                        <a class="btn-pdf"
                           href="{{ route('inventarios.transferencias.folios.pdf', ['folio' => $f->folio]) }}">
                            Imprimir PDF
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="vacio">Sin folios en el rango seleccionado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $folios->links('vendor.pagination.dashboard') }}
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

.acciones-superior{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:10px;
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

.filtros{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:flex-end;
    margin:12px 0 16px;
}

.filtro-grupo{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width:220px;
}

.filtro-grupo label{ font-weight:700; }

.filtro-grupo input{
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
    font-size:14px;
    line-height:42px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:none;
    cursor:pointer;
    text-decoration:none;
}

.btn{ background:#b22b27; color:#fff; }
.btn:hover{ background:#941c1c; }
.btn-limpiar{ background:#777; color:#fff; }
.btn-limpiar:hover{ background:#5f5f5f; }

.btn-pdf{
    height:34px;
    padding:0 14px;
    border-radius:8px;
    background:#2f2f2f;
    color:#fff;
    font-weight:700;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
}
.btn-pdf:hover{ filter:brightness(.95); }

.tabla-wrap{
    overflow-x:auto;
    border-radius:10px;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
}

.tabla{
    width:100%;
    border-collapse:collapse;
    min-width:760px;
}

.tabla th{
    background:#b22b27;
    color:#fff;
    padding:10px;
    text-align:left;
    white-space:nowrap;
}

.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    white-space:nowrap;
}

.vacio{
    text-align:center;
    color:#666;
    background:#fff7f0;
}

.alerta-error{
    margin:10px 0;
    padding:10px 12px;
    border-radius:10px;
    border:1px solid #d93025;
    background:#fff3f2;
    color:#7f1d1d;
    font-weight:700;
}

.paginacion-wrap{ margin-top:14px; }
</style>
@endsection
