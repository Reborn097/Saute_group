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

        <button class="btn-regresar"
            onclick="window.location.href='{{ url()->previous() }}'">
            Regresar
        </button>
    </div>

    <h2>Inventarios</h2>

    <form method="GET" style="margin:15px 0; display:flex; gap:10px; flex-wrap:wrap; align-items:end;">

        {{-- ✅ SOLO ADMIN: filtro unidad operativa --}}
        @if($esAdmin)
            <div>
                <label>Unidad operativa:</label>
                <select name="unidad_operativa_id" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach(($unidadesOperativas ?? []) as $uo)
                        <option value="{{ $uo->id }}" {{ (string)($uoId ?? '') === (string)$uo->id ? 'selected' : '' }}>
                            {{ $uo->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label>Almacén:</label>
            <select name="almacen_id" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach($almacenes as $a)
                    <option value="{{ $a->id }}" {{ (string)($almacenId ?? '') === (string)$a->id ? 'selected' : '' }}>
                        {{ $a->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn" href="{{ route('inventarios.movimiento.form') }}">Registrar movimiento</a>
            <a class="btn" href="{{ route('inventarios.kardex') }}">Historial</a>
            <a class="btn" href="{{ route('inventarios.caducidades') }}">Ver Caducidades</a>
        </div>
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

    {{-- ✅ paginación --}}
    <div style="margin-top:15px;">
        {{ $inventarios->links('vendor.pagination.dashboard') }}
    </div>

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

