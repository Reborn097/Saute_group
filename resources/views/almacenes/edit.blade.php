@extends('layouts.dashboard')

@section('titulo', 'Editar almacen')

@section('contenido')
@php
    $esAdmin = (auth()->user()->role ?? '') === 'admin';
@endphp

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

.subtitulo{
    display:block;
    font-size:15px;
    font-weight:600;
    color:#555;
    margin-top:4px;
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

.btn-accion{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
}

.btn-accion:hover{
    background:#941c1c;
}

.btn-cancelar{
    background:#777;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
}

.btn-cancelar:hover{
    background:#5f5f5f;
}
</style>

<div class="contenedor">

    <h2 class="titulo-form">
        Editar almacen
        <span class="subtitulo">
            {{ $almacen->nombre }} - {{ $unidad->nombre }}
        </span>
    </h2>

    <form action="{{ route('almacenes.update', [$unidad->id, $almacen->id]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-grupo">
            <label>Nombre del almacen</label>
            <input type="text"
                   name="nombre"
                   value="{{ old('nombre', $almacen->nombre) }}"
                   required>
        </div>

        <div class="form-grupo">
            <label>Tipo</label>
            <select name="tipo" required>
                <option value="seco" {{ old('tipo', $almacen->tipo) === 'seco' ? 'selected' : '' }}>Secos</option>
                <option value="refrigeracion" {{ old('tipo', $almacen->tipo) === 'refrigeracion' ? 'selected' : '' }}>Refrigeracion</option>
                <option value="congelado" {{ old('tipo', $almacen->tipo) === 'congelado' ? 'selected' : '' }}>Congelado</option>
                <option value="varios" {{ old('tipo', $almacen->tipo) === 'varios' ? 'selected' : '' }}>Varios</option>
                @if($esAdmin)
                    <option value="cedis" {{ old('tipo', $almacen->tipo) === 'cedis' ? 'selected' : '' }}>CEDIS / Externo</option>
                @endif
            </select>
        </div>

        @if($esAdmin)
            <div class="form-grupo">
                <label style="display:flex; align-items:center; gap:8px; font-weight:700;">
                    <input type="checkbox" name="es_cedis" value="1"
                        {{ old('es_cedis', $almacen->es_cedis) ? 'checked' : '' }} style="width:auto;">
                    Marcar como almacen CEDIS (solo visible/operable para administradores)
                </label>
            </div>
        @endif

        <div class="form-grupo">
            <label>Ubicacion</label>
            <input type="text"
                   name="ubicacion"
                   value="{{ old('ubicacion', $almacen->ubicacion) }}">
        </div>

        <div class="acciones-form">
            <button type="submit" class="btn-accion">
                Actualizar
            </button>

            <button type="button"
                    class="btn-cancelar"
                    onclick="window.location.href='{{ route('almacenes.index', $unidad->id) }}'">
                Cancelar
            </button>
        </div>

    </form>

</div>

@endsection
