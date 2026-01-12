@extends('layouts.dashboard')

@section('titulo', 'Registrar datos de tarjeta')

@section('contenido')
<div class="contenedor">

    <p style="margin-bottom: 20px; color: #555;">
        Estos datos se guardan temporalmente y se registrarán junto con el proveedor cuando presiones <b>Guardar Proveedor</b>.
    </p>

    @if (session('success'))
        <div style="background:#d4edda; color:#155724; padding:10px; border-radius:8px; margin-bottom:15px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:8px; margin-bottom:15px;">
            <b>Corrige esto:</b>
            <ul style="margin: 8px 0 0 18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.proveedores.tarjetas.guardar') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label><b>Tipo</b></label>
            <select name="tipo" class="form-control" required>
                <option value="empresa">Empresa</option>
                <option value="contacto">Contacto</option>
            </select>
        </div>

        <div class="mb-3">
            <label><b>Alias</b></label>
            <input type="text" name="alias" class="form-control" maxlength="80"
                   placeholder="Ej. Principal / Juan Pérez">
        </div>

        <div class="mb-3">
            <label><b>Banco</b></label>
            <input type="text" name="banco" class="form-control" maxlength="80"
                   placeholder="Ej. BBVA">
        </div>

        <div class="mb-3">
            <label><b>Titular</b></label>
            <input type="text" name="titular" class="form-control" maxlength="120"
                   placeholder="Ej. Distribuidora López SA de CV">
        </div>

        <div class="mb-3">
            <label><b>CLABE (18 dígitos)</b></label>
            <input type="text" name="clabe" id="clabe" class="form-control" maxlength="18"
                   placeholder="Ej. 012345678901234567">
        </div>

        <div class="mb-3">
            <label><b>Número de cuenta</b></label>
            <input type="text" name="cuenta" id="cuenta" class="form-control" maxlength="20"
                   placeholder="Ej. 1234567890">
        </div>

        <div class="mb-3">
            <label><b>Tarjeta (opcional)</b></label>
            <input type="text" name="tarjeta" id="tarjeta" class="form-control" maxlength="20"
                   placeholder="Número completo">
        </div>

        <div style="display:flex; justify-content:space-between; margin-top: 20px;">
            <a href="{{ route('dashboard.proveedores.crear') }}"
               style="color:#b22b27; font-weight:bold; text-decoration:none;">
                Regresar
            </a>

            <button type="submit" class="btn-agregar">
                Guardar datos
            </button>
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
    background-color: #0e2238;
    color: #fff;
    padding: 10px 15px;
    border-radius: 8px;
    border: none;
    font-weight: bold;
    cursor: pointer;
}
.btn-agregar:hover {
    background-color: #13314f;
}
</style>

<script>
function onlyDigits(el, max) {
    el.addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "").substring(0, max);
    });
}
onlyDigits(document.getElementById("clabe"), 18);
onlyDigits(document.getElementById("cuenta"), 20);
onlyDigits(document.getElementById("tarjeta"), 20);
</script>
@endsection
