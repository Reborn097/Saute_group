@extends('layouts.dashboard')

@section('titulo', 'Productos')

@section('contenido')
<div class="contenedor">
    <div class="acciones-superior">
        <button class="btn" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú</button>

        <div class="busqueda">
            <input type="text" placeholder="Buscar producto">
            <span class="icono-buscar">🔍</span>
        </div>

        <div class="botones-derecha">
            <button class="btn" onclick="window.location.href='{{ route('dashboard.agregar.categoria') }}'">Agregar categoría</button>
            <button class="btn" onclick="window.location.href='{{ route('dashboard.agregar.producto') }}'">Agregar producto</button>
        </div>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla-productos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Unidad</th>
                    <th>Medida</th>
                    <th>Proveedor</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Editar</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td>{{ $producto->id }}</td>
                        <td>{{ $producto->nombre }}</td>
                        <td>{{ $producto->valor_medida }}</td>
                        <td>{{ $producto->unidad_medida }}</td>

                        {{-- Proveedor (ahora sí lo muestra correctamente) --}}
                        <td>
                            {{ $producto->proveedores->first()->nombre ?? 'Sin proveedor' }}
                        </td>

                        {{-- Precio --}}
                        <td>
                            @if($producto->proveedores->isNotEmpty())
                                ${{ number_format($producto->proveedores->first()->pivot->precio, 2) }}
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

                        {{-- Botón Editar --}}
                        <td>
                            <button class="btn-admin" onclick="window.location.href='{{ route('dashboard.editar.producto', $producto->id) }}'">Editar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;">(Sin productos registrados)</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>
</div>

<style>
    .acciones-superior {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }

    /* ======= BUSCADOR ======= */
    .busqueda {
        position: relative;
        flex-grow: 1;
        max-width: 300px;
    }

    .busqueda input {
        width: 100%;
        padding: 8px 35px 8px 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 1em;
        font-family: 'Poppins';
    }

    .icono-buscar {
        position: absolute;
        right: 10px;
        top: 7px;
        font-size: 1.2em;
        opacity: 0.6;
        pointer-events: none;
    }

    .botones-derecha {
        display: flex;
        gap: 10px;
    }

    /* ======= TABLA ======= */
    .tabla-contenedor {
        border: 2px solid #000000ff;
        border-radius: 8px;
        overflow: hidden;
        background-color: white;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }

    .tabla-productos {
        width: 100%;
        border-collapse: collapse;
    }

    .tabla-productos th, .tabla-productos td {
        padding: 10px 15px;
        text-align: left;
    }

    .tabla-productos th {
        background-color: #f0f0f0;
        font-weight: bold;
        color: #333;
        border-bottom: 2px solid #000000ff;
    }

    .tabla-productos tr:hover td {
        background-color: #f8f2ec;
    }

    /* ======= BOTÓN ADMINISTRAR ======= */
    .btn-admin {
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9em;
        cursor: pointer;
    }

    .btn-admin:hover {
        background-color: #911f1d;
    }

    /* ======= AJUSTES ======= */
    .contenedor {
        background-color: #fae7d0;
        padding: 25px 35px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        max-width: 1000px;
        margin: 0 auto;
    }
</style>
@endsection
