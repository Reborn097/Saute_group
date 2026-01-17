@extends('layouts.dashboard')

@section('titulo', 'Editar Unidad Operativa')

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

    <h2 class="titulo-form">Editar Unidad Operativa</h2>

    <form action="{{ route('unidades.update', $unidad->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- NOMBRE --}}
        <div class="form-grupo">
            <label>Nombre</label>
            <input type="text"
                   name="nombre"
                   value="{{ old('nombre', $unidad->nombre) }}"
                   required
                   placeholder="Ej. Comedor Central">
        </div>

        {{-- TIPO --}}
        <div class="form-grupo">
            <label>Tipo</label>
            <select name="tipo" required>
                @php $tipo = old('tipo', $unidad->tipo); @endphp
                <option value="">Seleccione tipo…</option>
                <option value="comedor"   {{ $tipo === 'comedor' ? 'selected' : '' }}>Comedor</option>
                <option value="cafeteria" {{ $tipo === 'cafeteria' ? 'selected' : '' }}>Cafetería</option>
                <option value="bodega"    {{ $tipo === 'bodega' ? 'selected' : '' }}>Bodega</option>
                <option value="externo"   {{ $tipo === 'externo' ? 'selected' : '' }}>Externo</option>
            </select>
        </div>

        {{-- UBICACIÓN --}}
        <div class="form-grupo">
            <label>Ubicación</label>
            <input type="text"
                   name="ubicacion"
                   value="{{ old('ubicacion', $unidad->ubicacion) }}"
                   placeholder="Ej. Planta baja / Edificio A">
        </div>

        {{-- ACCIONES --}}
        <div class="acciones-form">
            <button type="submit" class="btn-accion">
                Actualizar
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
