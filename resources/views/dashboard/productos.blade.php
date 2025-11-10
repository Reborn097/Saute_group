@extends('layouts.dashboard')

@section('titulo', 'Productos')

@section('contenido')
<div class="contenedor">
    <div class="acciones-superior">
        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>

        <div class="busqueda">
            <input type="text" placeholder="Buscar producto">
            <span class="icono-buscar">🔍</span>
        </div>

        <div class="botones-derecha">
            <button class="btn-agregar" onclick="window.location.href='{{ route('dashboard.categorias.crear') }}'">Agregar categoría</button>
            <button class="btn-agregar" onclick="window.location.href='{{ route('dashboard.productos.crear') }}'">Agregar producto</button>
        </div>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th>Proveedor</th>
                <th>Precio</th>
                <th>Vigencia</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($productos as $producto)
                <tr>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->categoria->nombre ?? 'Sin categoría' }}</td>
                    <td>{{ $producto->valor_medida ?? '—' }}</td>
                    <td>{{ $producto->unidad_medida ?? '—' }}</td>

                    {{-- 🔹 Select dinámico de proveedores --}}
                    <td>
                        @if($producto->proveedores->isNotEmpty())
                            <select class="selector-proveedor" onchange="actualizarDatos(this)">
                                @foreach($producto->proveedores as $prov)
                                    <option 
                                        value="{{ $prov->pivot->precio }}"
                                        data-inicio="{{ \Carbon\Carbon::parse($prov->pivot->fecha_vigencia_inicio)->format('d/m/Y') }}"
                                        data-fin="{{ \Carbon\Carbon::parse($prov->pivot->fecha_vigencia_final)->format('d/m/Y') }}">
                                        {{ $prov->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <span>Sin proveedor</span>
                        @endif
                    </td>

                    {{-- 🔹 Precio dinámico --}}
                    <td class="precio">
                        @if($producto->proveedores->isNotEmpty())
                            ${{ number_format($producto->proveedores->first()->pivot->precio, 2) }}
                        @else
                            —
                        @endif
                    </td>

                    {{-- 🔹 Vigencia dinámica --}}
                    <td class="vigencia">
                        @if($producto->proveedores->isNotEmpty())
                            {{ \Carbon\Carbon::parse($producto->proveedores->first()->pivot->fecha_vigencia_inicio)->format('d/m/Y') }}
                            -
                            {{ \Carbon\Carbon::parse($producto->proveedores->first()->pivot->fecha_vigencia_final)->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>

                    {{-- Estado --}}
                    <td>
                        @if($producto->estado == 1)
                            <span class="badge activo">Activo</span>
                        @else
                            <span class="badge inactivo">Inactivo</span>
                        @endif
                    </td>

                    {{-- Acciones --}}
                    <td class="acciones">
                        <button class="btn-editar" onclick="window.location.href='{{ route('dashboard.productos.editar', $producto->id) }}'">Editar</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No hay productos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ================== SCRIPT ================== --}}
<script>
function actualizarDatos(selectElement) {
    const precio = selectElement.value;
    const inicio = selectElement.options[selectElement.selectedIndex].dataset.inicio;
    const fin = selectElement.options[selectElement.selectedIndex].dataset.fin;

    const fila = selectElement.closest('tr');
    fila.querySelector('.precio').textContent = `$${parseFloat(precio).toFixed(2)}`;
    fila.querySelector('.vigencia').textContent = `${inicio} - ${fin}`;
}
</script>

{{-- ================== ESTILOS ================== --}}
<style>
.contenedor {
    background-color: #fceede;
    padding: 25px;
    border-radius: 12px;
    max-width: 1150px;
    margin: auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.acciones-superior {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 15px;
    gap: 15px;
}
.btn-menu {
    background-color: #999;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 1em;
    font-family: 'Poppins';
    cursor: pointer;
}
.btn-menu:hover {
    background-color: #8f1f1c;
}
.busqueda {
    position: relative;
    flex-grow: 1;
    max-width: 280px;
}
.busqueda input {
    width: 100%;
    padding: 8px 35px 8px 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
}
.icono-buscar {
    position: absolute;
    right: 10px;
    top: 7px;
    font-size: 1.2em;
    opacity: 0.6;
}
.botones-derecha {
    display: flex;
    gap: 10px;
}
.btn-agregar {
    background-color: #b22b27;
    color: #fff;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    border: none;
    cursor: pointer;
}
.btn-agregar:hover {
    background-color: #941c1c;
}

/* === TABLA === */
.tabla {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}
.tabla th {
    background-color: #b22b27;
    color: white;
    padding: 10px;
    text-align: center;
    font-weight: 600;
}
.tabla td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}
.tabla tr:hover {
    background-color: #fff7f7;
}

/* === SELECT === */
.selector-proveedor {
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    background-color: #fff;
    cursor: pointer;
}

/* === BADGES === */
.badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.9em;
    color: white;
    font-weight: 600;
}
.badge.activo { background-color: #28a745; }
.badge.inactivo { background-color: #6c757d; }

/* === ACCIONES === */
.acciones {
    display: flex;
    justify-content: center;
}
.btn-editar {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 6px 15px;
    border-radius: 6px;
    font-size: 0.9em;
    font-weight: 600;
    cursor: pointer;
}
.btn-editar:hover {
    background-color: #941c1c;
}
.text-center { text-align: center; }
</style>
@endsection
