@extends('layouts.dashboard')

@section('titulo', 'Editar Producto')

@section('contenido')
<div class="contenedor">

    <form action="{{ route('dashboard.actualizar.producto', $producto->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Nombre --}}
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ $producto->nombre }}" required>
        </div>

        {{-- Valor de medida --}}
        <div class="form-group">
            <label>Valor de medida</label>
            <input type="number" step="0.01" name="valor_medida" value="{{ $producto->valor_medida }}">
        </div>

        {{-- Unidad de medida --}}
        <div class="form-group">
            <label>Unidad de medida</label>
            <input type="text" name="unidad_medida" value="{{ $producto->unidad_medida }}">
        </div>

        {{-- Categoría --}}
        <div class="form-group">
            <label>Categoría</label>
            <select name="categoria_id">
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" {{ $producto->categoria_id == $categoria->id ? 'selected' : '' }}>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Proveedor --}}
        @php
            $proveedorSeleccionado = optional($producto->proveedores->first())->id;
        @endphp
        <div class="form-group">
            <label>Proveedor</label>
            <select name="proveedor_id">
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" {{ $proveedorSeleccionado == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Precio --}}
        <div class="form-group">
            <label>Precio</label>
            <input type="number" name="precio" step="0.01" value="{{ old('precio', $precioActual) }}">
        </div>

        {{-- Estado --}}
        <div class="form-group">
            <label>Estado</label>
            <select name="estado">
                <option value="1" {{ $producto->estado == 1 ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ $producto->estado == 0 ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        {{-- Botón de enviar --}}
        <button type="submit" class="btn">Actualizar producto</button>
    </form>
</div>

{{-- Estilos --}}
<style>
    .contenedor {
        background-color: #fae7d0;
        padding: 25px 35px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        max-width: 700px;
        margin: 30px auto;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    input[type="text"],
    input[type="number"],
    select {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 1em;
        font-family: 'Poppins', sans-serif;
    }

    .btn {
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1em;
    }

    .btn:hover {
        background-color: #911f1d;
    }
</style>
@endsection
