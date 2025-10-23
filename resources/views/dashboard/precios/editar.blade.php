@extends('layouts.dashboard')

@section('titulo', 'Actualizar Precio')

@section('contenido')
<div class="contenedor-form">

    <h2 class="titulo-seccion">Actualizar Precio</h2>

    <form action="{{ route('producto_proveedor.actualizar_precio', $relacion->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Nombre del producto --}}
        <div class="form-grupo">
            <label>Producto</label>
            <input type="text" value="{{ $relacion->producto->nombre }}" disabled>
        </div>

        {{-- Proveedor (solo visible para admin) --}}
        @if(auth()->user()->rol === 'admin')
            <div class="form-grupo">
                <label>Proveedor</label>
                <input type="text" value="{{ $relacion->proveedor->nombre }}" disabled>
            </div>
        @endif

        {{-- Precio actual --}}
        <div class="form-grupo">
            <label for="precio">Nuevo precio</label>
            <input 
                type="number" 
                id="precio" 
                name="precio" 
                step="0.01" 
                value="{{ old('precio', $relacion->precio) }}" 
                required>
        </div>

        {{-- Fecha de inicio --}}
        <div class="form-grupo">
            <label for="fecha_vigencia_inicio">Fecha de inicio de validez</label>
            <input 
                type="date" 
                id="fecha_vigencia_inicio" 
                name="fecha_vigencia_inicio" 
                value="{{ old('fecha_vigencia_inicio', $relacion->fecha_vigencia_inicio) }}" 
                required>
        </div>

        {{-- Fecha final --}}
        <div class="form-grupo">
            <label for="fecha_vigencia_final">Fecha final de validez</label>
            <input 
                type="date" 
                id="fecha_vigencia_final" 
                name="fecha_vigencia_final" 
                value="{{ old('fecha_vigencia_final', $relacion->fecha_vigencia_final) }}">
        </div>

        {{-- Botones --}}
        <div class="botones">
            <a href="{{ route('dashboard.precios') }}" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-guardar">Guardar Cambios</button>
        </div>
    </form>
</div>

<style>
/* mantiene el mismo diseño que los demás formularios */
.contenedor-form {
    max-width: 900px;
    margin: 40px auto;
    background-color: #fbe9d7;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
}
.titulo-seccion {
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.8rem;
    color: #b22b27;
    font-weight: 700;
}
.form-grupo {
    margin-bottom: 20px;
}
label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}
input[type="text"],
input[type="number"],
input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    background-color: #fff;
}
.botones {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 25px;
}
.btn-cancelar {
    background-color: #aaa;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s;
}
.btn-cancelar:hover {
    background-color: #888;
}
.btn-guardar {
    background-color: #b22b27;
    color: white;
    padding: 10px 25px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
}
.btn-guardar:hover {
    background-color: #8c1f1b;
}
</style>
@endsection
