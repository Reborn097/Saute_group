@extends('layouts.dashboard')

@section('titulo', 'Editar Producto')

@section('contenido')
<div class="contenedor">

    <form action="{{ route('dashboard.actualizar.producto', $producto->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ $producto->nombre }}" required>
        </div>

        <div class="form-group">
            <label>Valor de medida</label>
            <input type="number" step="0.01" name="valor_medida" value="{{ $producto->valor_medida }}">
        </div>

        <div class="form-group">
            <label>Unidad de medida</label>
            <input type="text" name="unidad_medida" value="{{ $producto->unidad_medida }}">
        </div>

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

        <div class="form-group">
            <label>Proveedor</label>
            <select name="proveedor_id">
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" {{ $producto->proveedor_id == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Precio</label>
            <input type="number" step="0.01" name="precio" value="{{ $producto->precio }}">
        </div>

        <div class="form-group">
            <label>Estado</label>
            <select name="estado">
                <option value="Activo" {{ $producto->estado == 'Activo' ? 'selected' : '' }}>Activo</option>
                <option value="Inactivo" {{ $producto->estado == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <button type="submit" class="btn">Actualizar producto</button>
    </form>
</div>
@endsection
