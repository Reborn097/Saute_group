@extends('layouts.dashboard')

@section('titulo', 'Agregar Proveedor')

@section('contenido')
<div class="contenedor">
    <form action="{{ route('dashboard.proveedores.guardar') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label><b>Nombre del proveedor</b></label>
            <input type="text" name="nombre" class="form-control" placeholder="Ej. Distribuidora López" required>
        </div>

        <div class="mb-3">
            <label><b>Nombre del contacto</b></label>
            <input type="text" name="nombre_contacto" class="form-control" placeholder="Ej. Juan Pérez">
        </div>

        <div class="mb-3">
            <label><b>Teléfono del contacto</b></label>
            <input type="text" name="telefono_contacto" class="form-control" placeholder="Ej. 228-987-6543">
        </div>

        <div class="mb-3">
            <label><b>Teléfono</b></label>
            <input type="text" name="telefono" class="form-control" placeholder="Ej. 228-123-4567">
        </div>

        <div class="mb-3">
            <label><b>Calle</b></label>
            <input type="text" name="calle" class="form-control" placeholder="Ej. Calle Principal #25">
        </div>

        <div class="mb-3">
            <label><b>Colonia</b></label>
            <input type="text" name="colonia" class="form-control" placeholder="Ej. Centro">
        </div>

        <div class="mb-3">
            <label><b>Código Postal</b></label>
            <input type="text" name="codigo_postal" class="form-control" placeholder="Ej. 91000">
        </div>

        <div class="mb-3">
            <label><b>Número de Dirección</b></label>
            <input type="text" name="num_direccion" class="form-control" placeholder="Ej. 10-B">
        </div>

        <div class="mb-3">
            <label><b>RFC</b></label>
            <input type="text" name="rfc" class="form-control" placeholder="Ej. LOPE890123JKL">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <a href="{{ route('dashboard.proveedores') }}" style="color: #b22b27; font-weight: bold; text-decoration: none;">Cancelar</a>
            <button type="submit" class="btn-agregar">Guardar Proveedor</button>
        </div>
    </form>
</div>

<style>
.contenedor {
    background-color: #fceede;
    padding: 25px;
    border-radius: 12px;
    max-width: 800px;
    margin: auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 {
    color: #ffffff;
    font-weight: bold;
}
.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-top: 5px;
    margin-bottom: 15px;
}
.btn-agregar {
    background-color: #941c1c;
    color: #fff;
    padding: 10px 15px;
    border-radius: 8px;
    border: none;
    font-weight: bold;
    cursor: pointer;
}
.btn-agregar:hover {
    background-color: #b82929;
}
</style>
@endsection
