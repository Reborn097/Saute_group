@extends('layouts.dashboard')

@section('titulo', 'Editar proveedor')

@section('contenido')
<div class="contenedor-formulario" style="max-width:600px; margin:auto; background-color:#f9e3cc; padding:20px; border-radius:12px;">

    <form action="{{ route('dashboard.proveedores.actualizar', $proveedor->id) }}" method="POST" style="display:flex; flex-direction:column; gap:10px;">
        @csrf
        @method('PUT')

        <label><b>Nombre del proveedor:</b></label>
        <input type="text" name="nombre" value="{{ $proveedor->nombre }}" required>

        <label><b>Nombre del contacto:</b></label>
        <input type="text" name="nombre_contacto" value="{{ $proveedor->nombre_contacto }}">

        <label><b>Teléfono del contacto:</b></label>
        <input type="text" name="telefono_contacto" value="{{ $proveedor->telefono_contacto }}">

        <label><b>Teléfono del proveedor:</b></label>
        <input type="text" name="telefono" value="{{ $proveedor->telefono }}">

        <label><b>Calle:</b></label>
        <input type="text" name="calle" value="{{ $proveedor->calle }}">

        <label><b>Colonia:</b></label>
        <input type="text" name="colonia" value="{{ $proveedor->colonia }}">

        <label><b>Código postal:</b></label>
        <input type="text" name="codigo_postal" value="{{ $proveedor->codigo_postal }}">

        <label><b>Número de dirección:</b></label>
        <input type="text" name="num_direccion" value="{{ $proveedor->num_direccion }}">

        <label><b>RFC:</b></label>
        <input type="text" name="rfc" value="{{ $proveedor->rfc }}">

        <div style="display:flex; justify-content:center; gap:10px; margin-top:15px;">
            <button type="submit" class="btn btn-rojo">Guardar cambios</button>
            <a href="{{ route('dashboard.proveedores') }}" class="btn btn-gris">Cancelar</a>
        </div>
    </form>
</div>

<style>
    input {
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-family: Poppins, sans-serif;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }

    .btn-rojo {
        background-color: #b91c1c;
        color: #fff;
    }

    .btn-gris {
        background-color: #6b7280;
        color: #fff;
    }

    .btn-rojo:hover {
        background-color: #991b1b;
    }

    .btn-gris:hover {
        background-color: #4b5563;
    }
</style>
@endsection
