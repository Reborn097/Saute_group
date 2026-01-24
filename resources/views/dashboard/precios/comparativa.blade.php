@extends('layouts.dashboard')

@section('titulo', 'Comparativa de Precios')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button type="button" class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>
    </div>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('dashboard.precios.comparativa') }}" class="filtros">

        <div class="campo" style="flex:1; min-width:260px;">
            <label>Buscar producto</label>
            <input
                type="text"
                name="q"
                value="{{ $q }}"
                placeholder="Buscar producto..."
                class="input">
        </div>

        <div class="campo" style="min-width:260px;">
            <label>Categoría</label>
            <select name="categoria" class="input">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ (string)$categoriaId === (string)$cat->id ? 'selected' : '' }}>
                        {{ $cat->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo acciones-filtro">
            <label style="visibility:hidden;">Acción</label>
            <button type="submit" class="btn">Filtrar</button>

            @if($q || $categoriaId)
                <a href="{{ route('dashboard.precios.comparativa') }}" class="btn-cancelar">Limpiar</a>
            @endif
        </div>
    </form>

    {{-- TABLA --}}
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="width:190px;">Unidad</th>
                    <th style="width:140px;">Precio actual</th>
                    <th style="width:140px;">Precio anterior</th>
                    <th style="width:140px;">Diferencia</th>
                    <th style="width:140px;">Variación %</th>
                </tr>
            </thead>

            <tbody>
                @forelse($comparativa as $item)

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
                        <td style="font-weight:800;">{{ $item->producto->nombre }}</td>

                        <td>
                            {{ $item->producto->valor_medida }} {{ $item->producto->unidad_medida }}
                        </td>

                        <td>
                            {{ $actual !== null ? '$'.number_format($actual,2) : '—' }}
                        </td>

                        <td>
                            {{ $anterior !== null ? '$'.number_format($anterior,2) : '—' }}
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

                @empty
                    <tr>
                        <td colspan="6" class="vacio">
                            No hay resultados para mostrar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $productos->onEachSide(1)->links('vendor.pagination.dashboard') }}
    </div>

</div>

<style>
/* ===== CONTENEDOR TARJETA ===== */
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* ===== TOP ACTIONS ===== */
.acciones-superior{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:flex-start;
    margin-bottom:12px;
}

h2{ margin:0 0 10px; }

/* ===== BOTONES ===== */
.btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
}
.btn:hover{ background:#941c1c; }

.btn-menu{
    background:#777;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu:hover{ filter:brightness(.95); }

.btn-cancelar{
    background:#777;
    color:white;
    padding:9px 14px;
    border-radius:8px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
}
.btn-cancelar:hover{ filter:brightness(.95); }

/* ===== FILTROS ===== */
.filtros{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:end;
    margin:15px 0;
}
.campo{ min-width:240px; }
label{ font-weight:700; display:block; margin-bottom:6px; }

.input{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
}

.acciones-filtro{
    display:flex;
    gap:10px;
    min-width:auto;
    align-items:end;
}
.acciones-filtro .btn,
.acciones-filtro .btn-cancelar{
    height:40px;
}

@media(max-width:720px){
    .campo{ min-width:100%; }
    .acciones-filtro{ width:100%; justify-content:flex-start; }
}

/* ===== TABLA ESTÁNDAR ===== */
.tabla-wrap{ overflow:auto; border-radius:10px; }
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
}
.tabla thead th{
    background:#b22b27;
    color:#fff;
    text-align:left;
    padding:10px;
    font-weight:700;
}
.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    vertical-align:top;
}
.vacio{
    text-align:center;
    padding:18px;
    color:#666;
    background:#fff7f0;
}

/* ===== COLORES DE FILA (conservados) ===== */
.fila-verde{ background:#e9f6ec !important; }
.fila-rojo{ background:#ffe5e5 !important; }
.fila-blanco{ background:#fff !important; }

/* paginación */
.paginacion-wrap{ margin-top:14px; }
</style>
@endsection
