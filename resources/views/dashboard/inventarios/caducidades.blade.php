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

        {{-- ✅ nuevo: filtro estado --}}
        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="vigente" {{ ($estado ?? '') === 'vigente' ? 'selected' : '' }}>Vigente (+15 días)</option>
            <option value="por_vencer" {{ ($estado ?? '') === 'por_vencer' ? 'selected' : '' }}>Por vencer (0–15 días)</option>
            <option value="vencido" {{ ($estado ?? '') === 'vencido' ? 'selected' : '' }}>Vencido</option>
            <option value="sin_fecha" {{ ($estado ?? '') === 'sin_fecha' ? 'selected' : '' }}>Sin fecha</option>
        </select>

        <button class="btn">Filtrar</button>
    </form>

    {{-- TABLA --}}
    <table class="tabla">
        <thead>
            <tr>
                <th>Producto</th>
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
            @endphp
            <tr>
                <td>{{ $c->producto->nombre }}</td>
                <td>{{ $c->almacen->nombre }}</td>
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
                <td colspan="6">No hay registros</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{ $caducidades->links('vendor.pagination.dashboard') }}

</div>

<style>
.contenedor{background:#fceede;padding:25px;border-radius:12px}
.filtros{display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap}
.filtros select{padding:8px;border-radius:8px;border:1px solid #ccc}
.tabla{width:100%;border-collapse:collapse;background:#fff}
.tabla th{background:#b22b27;color:#fff;padding:10px}
.tabla td{padding:8px;text-align:center;border-bottom:1px solid #ddd}
.badge{padding:5px 10px;border-radius:12px;color:#fff;font-size:.8em}
.ok{background:#4caf50}
.alerta{background:#ff9800}
.vencido{background:#f44336}
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
