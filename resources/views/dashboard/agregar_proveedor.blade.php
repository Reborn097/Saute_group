@extends('layouts.dashboard')

@section('titulo', 'Agregar Proveedor')

@section('contenido')
<div class="contenedor">

    <form action="{{ route('dashboard.proveedores.guardar') }}" method="POST" class="formulario-proveedor">
        @csrf

        <div class="form-group">
            <label for="nombre">Nombre del proveedor</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Distribuidora López" required>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" placeholder="Ej. 228-123-4567">
        </div>

        <div class="form-group">
            <label for="calle">Calle</label>
            <input type="text" id="calle" name="calle" placeholder="Ej. Calle Principal #25">
        </div>

        <div class="form-group">
            <label for="colonia">Colonia</label>
            <input type="text" id="colonia" name="colonia" placeholder="Ej. Centro">
        </div>

        <div class="form-group">
            <label for="codigo_postal">Código Postal</label>
            <input type="text" id="codigo_postal" name="codigo_postal" placeholder="Ej. 91000">
        </div>

        <div class="form-group">
            <label for="num_direccion">Número de Dirección</label>
            <input type="text" id="num_direccion" name="num_direccion" placeholder="Ej. 10-B">
        </div>

        <div class="form-group">
            <label for="rfc">RFC</label>
            <input type="text" id="rfc" name="rfc" placeholder="Ej. LOPE890123JKL">
        </div>

        <div class="acciones">
            <a href="{{ route('dashboard.admin') }}" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-guardar">Guardar Proveedor</button>
        </div>
    </form>
</div>

<style>
.contenedor {
    background-color: #fceede;
    padding: 30px;
    border-radius: 15px;
    max-width: 600px;
    margin: auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 {
    color: #ffffffff;
    font-weight: bold;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}
.form-group input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
}
.acciones {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}
.btn-guardar {
    background-color: #941c1c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: bold;
}
.btn-cancelar {
    background-color: transparent;
    color: #941c1c;
    text-decoration: none;
    font-weight: bold;
}
.btn-guardar:hover {
    background-color: #b82929;
}
</style>
@endsection
