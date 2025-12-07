@extends('layouts.dashboard')

@section('titulo', 'Agregar Proveedor')

@section('contenido')
<div class="contenedor">
    <form action="{{ route('dashboard.proveedores.guardar') }}" method="POST">
        @csrf

        {{-- ===========================
            Nombre del proveedor
        ============================ --}}
        <div class="mb-3">
            <label><b>Nombre del proveedor</b></label>
            <input type="text" name="nombre" class="form-control"
                placeholder="Ej. Distribuidora López" required>
        </div>

        {{-- ===========================
            Teléfono del proveedor (13 max)
        ============================ --}}
        <div class="mb-3">
            <label><b>Teléfono del proveedor</b></label>
            <input type="text" name="telefono" id="telefono"
                maxlength="13" class="form-control" placeholder="Ej. 2283654321">
        </div>

        {{-- ===========================
            Nombre del contacto
        ============================ --}}
        <div class="mb-3">
            <label><b>Nombre del contacto</b></label>
            <input type="text" name="nombre_contacto" class="form-control"
                placeholder="Ej. Juan Pérez">
        </div>

        {{-- ===========================
            Teléfono del contacto (13 max)
        ============================ --}}
        <div class="mb-3">
            <label><b>Teléfono del contacto</b></label>
            <input type="text" name="telefono_contacto" id="telefono_contacto"
                maxlength="13" class="form-control" placeholder="Ej. 2298745632">
        </div>

        {{-- ===========================
            Código Postal
        ============================ --}}
        <div class="mb-3">
            <label><b>Código Postal</b></label>
            <input type="text" name="codigo_postal" maxlength="5"
                class="form-control" placeholder="Ej. 91017" required>
        </div>

        {{-- ===========================
            Colonia (usuario escribe)
        ============================ --}}
        <div class="mb-3">
            <label><b>Colonia</b></label>
            <input type="text" name="colonia" class="form-control"
                placeholder="Ej. Centro" required>
        </div>

        {{-- ===========================
            Calle
        ============================ --}}
        <div class="mb-3">
            <label><b>Calle</b></label>
            <input type="text" name="calle" class="form-control"
                placeholder="Ej. Calle Principal #25">
        </div>

        {{-- ===========================
            Número de Dirección (5 max)
        ============================ --}}
        <div class="mb-3">
            <label><b>Número de Dirección</b></label>
            <input type="text" name="num_direccion" id="num_direccion"
                maxlength="5" class="form-control" placeholder="Ej. 10-B">
        </div>

        {{-- ===========================
            RFC (13 max)
        ============================ --}}
        <div class="mb-3">
            <label><b>RFC</b></label>
            <input type="text" name="rfc" id="rfc" maxlength="13"
                class="form-control" placeholder="Ej. LOPE890123JKL">
        </div>

        {{-- BOTONES --}}
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <a href="{{ route('dashboard.proveedores') }}"
                style="color: #b22b27; font-weight: bold; text-decoration: none;">
                Cancelar
            </a>
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

{{-- ===========================
    SCRIPTS
=========================== --}}
<script>
document.getElementById("telefono").addEventListener("input", function() {
    this.value = this.value.substring(0, 13);
});

document.getElementById("telefono_contacto").addEventListener("input", function() {
    this.value = this.value.substring(0, 13);
});

document.getElementById("num_direccion").addEventListener("input", function() {
    this.value = this.value.substring(0, 5);
});

document.getElementById("rfc").addEventListener("input", function() {
    this.value = this.value.substring(0, 13).toUpperCase();
});
</script>

@endsection
