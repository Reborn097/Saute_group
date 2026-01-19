@extends('layouts.dashboard')

@section('titulo', 'Pedidos Diarios')

@section('contenido')

<div class="contenedor">

    <div class="acciones-superior" style="gap:10px;">
        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        <button class="btn" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pan.create') }}'">
            Nuevo pedido PAN
        </button>

        <button class="btn" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.tortilla.create') }}'">
            Nuevo pedido TORTILLA
        </button>
    </div>

    <h3 class="titulo-seccion">Listado de pedidos diarios</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
            <tr>
                <th>Semana</th>
                <th>Tipo</th>
                <th>Unidad operativa</th>
                <th>Total $</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pedidos as $p)
                <tr>
                    <td>
                        {{ \Carbon\Carbon::parse($p->semana_inicio)->format('d/m/Y') }}
                        -
                        {{ \Carbon\Carbon::parse($p->semana_fin)->format('d/m/Y') }}
                    </td>
                    <td>
                        <strong>{{ $p->tipo }}</strong>
                    </td>
                    <td>
                        {{ $p->unidadOperativa->nombre ?? 'N/A' }}
                    </td>
                    <td>
                        ${{ number_format($p->total, 2) }}
                    </td>
                    <td>
                        <button class="btn"
                            onclick="window.location.href='{{ route('dashboard.pedidos_diarios.show', $p->id) }}'">
                            Detalles
                        </button>
                        <button class="btn"
                            onclick="window.location.href='{{ route('dashboard.pedidos_diarios.edit', $p->id) }}'">
                            Editar
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:16px;">
                        No hay pedidos diarios registrados.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1000px;
    margin:auto;
}

.acciones-superior{
    display:flex;
    margin-bottom:15px;
}

.titulo-seccion{
    font-size:20px;
    font-weight:700;
    margin-bottom:12px;
}

.tabla{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
}

.tabla th{
    background:#b22b27;
    color:white;
    padding:12px;
}

.tabla td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
}

.tabla tr:hover td{
    background:#f5d6d6;
}

.btn-menu{
    background:#999;
    color:white;
    border:none;
    padding:8px 13px;
    border-radius:8px;
    cursor:pointer;
}

.btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 13px;
    border-radius:8px;
    cursor:pointer;
}
</style>

@endsection
