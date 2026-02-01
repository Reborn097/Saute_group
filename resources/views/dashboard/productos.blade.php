@extends('layouts.dashboard')

@section('titulo', 'Productos')

@section('contenido')
<div class="contenedor">

    {{-- ================= ACCIONES SUPERIORES ================= --}}
    <div class="acciones-superior">

        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        {{-- ===== FILTROS ===== --}}
        <form method="GET" action="{{ route('dashboard.productos') }}" class="filtros-linea">

            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                class="input-filtro"
                placeholder="Buscar producto..."
            >

            <select name="categoria_id" class="input-filtro">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="proveedor_id" class="input-filtro">
                <option value="">Todos los proveedores</option>
                @foreach($proveedores as $prov)
                    <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                        {{ $prov->nombre }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-filtrar">Filtrar</button>

            <a href="{{ route('dashboard.productos') }}" class="btn-limpiar">Limpiar</a>
        </form>

        <div class="botones-derecha">
            <button class="btn-agregar"
                onclick="window.location.href='{{ route('dashboard.categorias.crear') }}'">
                Agregar categoría
            </button>

            <button class="btn-agregar"
                onclick="window.location.href='{{ route('dashboard.productos.crear') }}'">
                Agregar producto
            </button>
        </div>
    </div>

    {{-- ================= TABLA ================= --}}
    <div class="tabla-wrapper">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Descripción - Contenido</th>
                    <th>Unidad contenido</th>
                    <th>Proveedor</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($presentaciones as $pres)
                    @php
                        $desc = $pres->descripcion ?? '—';
                        if (!empty($pres->contenido)) {
                            $desc = trim($pres->descripcion ?? '') . ' - ' . $pres->contenido;
                        }
                        $primero = $pres->proveedores->first();
                    @endphp
                    <tr>
                        <td>{{ $pres->producto->nombre ?? '—' }}</td>
                        <td>{{ $pres->producto->marca ?? '—' }}</td>
                        <td>{{ $desc }}</td>
                        <td>{{ $pres->unidad_contenido ?? '—' }}</td>

                        {{-- PROVEEDORES --}}
                        <td>
                            @if($pres->proveedores->isNotEmpty())
                                <select class="selector-proveedor" onchange="actualizarDatos(this)">
                                    @foreach($pres->proveedores as $prov)
                                        <option
                                            value="{{ $prov->precio_vigente }}"
                                            data-inicio="—"
                                            data-fin="—">
                                            {{ $prov->proveedor->nombre ?? '—' }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span>Sin proveedor</span>
                            @endif
                        </td>

                        {{-- PRECIO --}}
                        <td class="precio">
                            @if($primero)
                                ${{ number_format($primero->precio_vigente, 2) }}
                            @else
                                —
                            @endif
                        </td>

                        {{-- ESTADO --}}
                        <td>
                            <span class="badge {{ ($pres->producto->estado ?? 0) ? 'activo' : 'inactivo' }}">
                                {{ ($pres->producto->estado ?? 0) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        {{-- ACCIONES --}}
                        <td>
                            <button class="btn-editar"
                                onclick="window.location.href='{{ route('dashboard.productos.editar', $pres->producto->id) }}'">
                                Editar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No hay presentaciones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINACIÓN --}}
    <div class="pagination" style="margin-top:15px;">
        {{ $presentaciones->withQueryString()->links('vendor.pagination.dashboard') }}
    </div>

</div>

{{-- ================= SCRIPT ================= --}}
<script>
function actualizarDatos(select) {
    const fila = select.closest('tr');
    fila.querySelector('.precio').textContent = '$' + parseFloat(select.value).toFixed(2);
}
</script>

{{-- ================= ESTILOS ================= --}}
<style>
.contenedor{
    background:#fceede;
    padding:25px;
    border-radius:14px;
    max-width:1400px;   /* 👈 MÁS ANCHO */
    margin:auto;
}

/* ACCIONES */
.acciones-superior{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:15px;
}

/* FILTROS */
.filtros-linea{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:nowrap;
}

.input-filtro{
    width:220px;
    height:38px;
    padding:6px 12px;
    border-radius:8px;
    border:1px solid #ccc;
}

.btn-filtrar{
    height:38px;
    background:#0F2235;
    color:#fff;
    border:none;
    padding:0 14px;
    border-radius:8px;
    font-weight:700;
}

.btn-limpiar{
    height:38px;
    background:#999;
    color:#fff;
    padding:0 14px;
    border-radius:8px;
    display:flex;
    align-items:center;
    text-decoration:none;
    font-weight:700;
}

/* TABLA */
.tabla-wrapper{
    overflow-x:auto;
}
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:10px;
    overflow:hidden;
}
.tabla th{
    background:#b22b27;
    color:#fff;
    padding:10px;
}
.tabla td{
    padding:10px;
    border-bottom:1px solid #ddd;
    text-align:center;
}

/* BADGES */
.badge{
    padding:4px 8px;
    border-radius:6px;
    color:#fff;
    font-weight:600;
}
.badge.activo{background:#28a745;}
.badge.inactivo{background:#6c757d;}

/* BOTONES */
.btn-menu{background:#999;color:#fff;padding:8px 18px;border-radius:8px;border:none;}
.btn-agregar{background:#b22b27;color:#fff;padding:10px 15px;border-radius:8px;border:none;}
.btn-editar{background:#b22b27;color:#fff;padding:6px 14px;border-radius:6px;border:none;}

/* RESPONSIVE */
@media(max-width:900px){
    .filtros-linea{flex-wrap:wrap;}
    .input-filtro{width:10%;}
}

/* FIX: evita iconos enormes en la paginación */
.pagination svg{
    width: 16px !important;
    height: 16px !important;
}
.pagination a, .pagination span{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

</style>
@endsection
