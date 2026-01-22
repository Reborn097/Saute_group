@extends('layouts.dashboard')

@section('titulo', 'Comparativa de Precios')

@section('contenido')
<div class="contenedor-form">

    <button type="button" class="btn" onclick="window.location.href='{{ route('dashboard.admin') }}'">
        Menú principal
    </button>

    <h2 style="margin-top:20px;">Comparativa de precios (actual vs anterior)</h2>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('dashboard.precios.comparativa') }}" class="buscador">
        
        <input 
            type="text"
            name="q"
            value="{{ $q }}"
            placeholder="Buscar producto..."
            class="input-buscar">

        <select name="categoria" class="input-buscar" style="max-width:250px;">
            <option value="">Todas las categorías</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" {{ $categoriaId == $cat->id ? 'selected' : '' }}>
                    {{ $cat->nombre }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn-accion">Filtrar</button>

        @if($q || $categoriaId)
            <a href="{{ route('dashboard.precios.comparativa') }}" class="btn-cancelar">Limpiar</a>
        @endif
    </form>


    {{-- TABLA --}}
    <table class="tabla">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Unidad</th>
                <th>Precio actual</th>
                <th>Precio anterior</th>
                <th>Diferencia</th>
                <th>Variación %</th>
            </tr>
        </thead>

        <tbody>
            @foreach($comparativa as $item)

                @php
                    $actual = $item->actual->precio ?? null;
                    $anterior = $item->anterior->precio ?? null;
                    $dif = ($actual !== null && $anterior !== null) ? $actual - $anterior : null;

                    if($dif === null){
                        $color = 'blanco';
                        $icono = '⬜';
                    } elseif($dif > 0){
                        $color = 'rojo';
                        $icono = '🔺';
                    } elseif($dif < 0){
                        $color = 'verde';
                        $icono = '🔻';
                    } else {
                        $color = 'blanco';
                        $icono = '⬜';
                    }
                @endphp

                <tr class="fila-{{ $color }}">
                    <td>{{ $item->producto->nombre }}</td>

                    <td>{{ $item->producto->valor_medida }} {{ $item->producto->unidad_medida }}</td>

                    <td>
                        {{ $actual ? '$'.number_format($actual,2) : '—' }}
                    </td>

                    <td>
                        {{ $anterior ? '$'.number_format($anterior,2) : '—' }}
                    </td>

                    <td>
                        {{ $icono }}
                        @if($dif !== null)
                            {{ $dif > 0 ? '+' : '' }}{{ number_format($dif, 2) }}
                        @else
                            —
                        @endif
                    </td>

                    <td>
                        @if($item->variacion !== null)
                            {{ number_format($item->variacion, 2) }}%
                        @else
                            —
                        @endif
                    </td>
                </tr>

            @endforeach
        </tbody>
    </table>
    <div class="paginacion-wrap">
        {{ $productos->onEachSide(1)->links('vendor.pagination.dashboard') }}
    </div>


</div>

<style>
/* TABLA */
.tabla {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
}

.tabla th, .tabla td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.tabla th {
    background-color: #b22b27;
    color: white;
}

/* COLORES */
.fila-verde {
    background-color: #d4edda !important; 
}
.fila-rojo {
    background-color: #f8d7da !important;
}
.fila-blanco {
    background-color: white !important;
}

/* BUSCADOR */
.buscador {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    margin-bottom: 20px;
    align-items: center;
}

.input-buscar {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.btn-cancelar {
    background-color: #777;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
}

.btn-cancelar:hover {
    background-color: #555;
}

/* BOTONES */
.btn {
    background-color: #555;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}
.btn:hover {
    background-color: #222;
}
/* Estilo uniforme de botones */
.btn-accion {
    background-color: #b22b27;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn-accion:hover {
    background-color: #8f1f1c;
    transform: translateY(-2px);
}

/* Botón limpiar (gris suave) */
.btn-cancelar {
    background-color: #777;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s ease;
}

.btn-cancelar:hover {
    background-color: #555;
}
</style>
@endsection
