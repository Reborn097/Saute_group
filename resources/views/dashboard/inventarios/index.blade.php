@extends('layouts.dashboard')
@section('titulo','Inventarios')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        <button class="btn-regresar"
            onclick="window.location.href='{{ url()->previous() }}'">
            Regresar
        </button>
    </div>

    <h2>Inventarios</h2>

    <form method="GET" style="margin:15px 0;">
        <label>Almacén:</label>
        <select name="almacen_id" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach($almacenes as $a)
                <option value="{{ $a->id }}" {{ (string)$almacenId===(string)$a->id?'selected':'' }}>
                    {{ $a->nombre }}
                </option>
            @endforeach
        </select>

        <a class="btn" href="{{ route('inventarios.movimiento.form') }}" style="margin-left:10px;">Registrar movimiento</a>
        <a class="btn" href="{{ route('inventarios.kardex') }}" style="margin-left:10px;">Ver Kardex</a>
        <a class="btn" href="{{ route('inventarios.caducidades') }}" style="margin-left:10px;">Ver Caducidades</a>
    </form>

    <table class="tabla">
        <thead>
            <tr>
                <th>Almacén</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
        @forelse($inventarios as $inv)
            <tr>
                <td>{{ $inv->almacen->nombre ?? '' }}</td>
                <td>{{ $inv->producto->nombre ?? '' }}</td>
                <td>{{ $inv->producto->categoria->nombre ?? '' }}</td>
                <td>{{ number_format($inv->cantidad, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Sin registros.</td></tr>
        @endforelse
        </tbody>
    </table>

</div>
<style>
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
