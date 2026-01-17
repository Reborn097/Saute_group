@extends('layouts.dashboard')

@section('titulo', 'Crear Unidad Operativa')

@section('contenido')

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:600px;
    margin:0 auto;
    box-shadow:0 0 10px rgba(0,0,0,0.08);
    font-family:'Poppins', sans-serif;
}

.titulo-form{
    text-align:center;
    margin-bottom:25px;
    font-size:22px;
    font-weight:800;
    color:#7c1818;
}

.form-grupo{
    margin-bottom:18px;
}

.form-grupo label{
    display:block;
    font-weight:700;
    margin-bottom:6px;
    color:#333;
}

.form-grupo input,
.form-grupo select{
    width:100%;
    padding:10px 12px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-size:14px;
}

.form-grupo input:focus,
.form-grupo select:focus{
    outline:none;
    border-color:#b22b27;
    box-shadow:0 0 0 2px rgba(178,43,39,0.15);
}

.acciones-form{
    display:flex;
    justify-content:center;
    gap:12px;
    margin-top:25px;
    flex-wrap:wrap;
}

/* Botones (misma línea que index) */
.btn-accion{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
}

.btn-accion:hover{ background:#941c1c; }

.btn-cancelar{
    background:#777;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
}

.btn-cancelar:hover{ background:#5f5f5f; }
</style>

<div class="contenedor">

    <h2 class="titulo-form">Registrar Unidad Operativa</h2>

    <form action="{{ route('unidades.store') }}" method="POST">
        @csrf

        {{-- NOMBRE --}}
        <div class="form-grupo">
            <label>Nombre</label>
            <input type="text" name="nombre" required placeholder="Ej. Comedor Central">
        </div>

        {{-- TIPO --}}
        <div class="form-grupo">
            <label>Tipo</label>
            <select name="tipo" required>
                <option value="">Seleccione tipo…</option>
                <option value="comedor">Comedor</option>
                <option value="cafeteria">Cafetería</option>
                <option value="bodega">Bodega</option>
                <option value="externo">Externo</option>
            </select>
        </div>

        {{-- UBICACIÓN --}}
        <div class="form-grupo">
            <label>Ubicación</label>
            <input type="text" name="ubicacion" placeholder="Ej. Planta baja / Edificio A">
        </div>

        {{-- ACCIONES --}}
        <div class="acciones-form">
            <button type="submit" class="btn-accion">
                Registrar unidad
            </button>

            <button type="button"
                    class="btn-cancelar"
                    onclick="window.location.href='{{ route('unidades.index') }}'">
                Cancelar
            </button>
        </div>

    </form>

</div>

@endsection
