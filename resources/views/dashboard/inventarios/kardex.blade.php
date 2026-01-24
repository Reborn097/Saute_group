@extends('layouts.dashboard')

@section('titulo', 'Kardex de Inventarios')

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
            onclick="window.location.href='{{ route('inventarios.index') }}'">
            Regresar
        </button>

    </div>

    <h2>Kardex de movimientos</h2>

    {{-- FILTROS --}}
    <form method="GET" class="filtros">

        {{-- ✅ SOLO ADMIN: unidad operativa --}}
        @if($esAdmin)
            <select name="unidad_operativa_id" onchange="this.form.submit()">
                <option value="">Todas las unidades</option>
                @foreach(($unidadesOperativas ?? []) as $uo)
                    <option value="{{ $uo->id }}" {{ (string)($uoId ?? '') === (string)$uo->id ? 'selected' : '' }}>
                        {{ $uo->nombre }}
                    </option>
                @endforeach
            </select>
        @endif

        <select name="almacen_id">
            <option value="">Todos los almacenes</option>
            @foreach($almacenes as $a)
                <option value="{{ $a->id }}" {{ (string)($almacenId ?? '') === (string)$a->id ? 'selected' : '' }}>
                    {{ $a->nombre }}
                </option>
            @endforeach
        </select>

        <select name="producto_id">
            <option value="">Todos los productos</option>
            @foreach($productos as $p)
                <option value="{{ $p->id }}" {{ (string)($productoId ?? '') === (string)$p->id ? 'selected' : '' }}>
                    {{ $p->nombre }}
                </option>
            @endforeach
        </select>

        <button class="btn">Filtrar</button>
    </form>

    {{-- TABLA --}}
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
                <td colspan="7">No hay movimientos</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{ $movimientos->links('vendor.pagination.dashboard') }}

</div>

<style>
.contenedor{background:#fceede;padding:25px;border-radius:12px}
.filtros{display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap}
.filtros select{padding:8px;border-radius:8px;border:1px solid #ccc}
.tabla{width:100%;border-collapse:collapse;background:#fff}
.tabla th{background:#b22b27;color:#fff;padding:10px}
.tabla td{padding:8px;text-align:center;border-bottom:1px solid #ddd}
.badge{padding:5px 10px;border-radius:12px;color:#fff;font-size:.8em}
.entrada{background:#4caf50}
.salida{background:#f44336}
.ajuste{background:#ff9800}
.btn{background:#b22b27;color:white;padding:8px 14px;border:none;border-radius:6px;cursor:pointer}
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
