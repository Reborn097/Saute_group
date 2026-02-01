@extends('layouts.dashboard')

@section('titulo', 'Editar Unidad Operativa')

@section('contenido')

<style>
/* ✅ NO ponemos font-family aquí: ya viene del dashboard.css */

.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:600px;
    margin:0 auto;
    box-shadow:0 0 10px rgba(0,0,0,0.08);
}

/* ✅ responsive contenedor */
@media(max-width:720px){
    .contenedor{
        max-width:100%;
        margin:0 auto;
        padding:18px 16px;
        border-radius:12px;
    }
}

.titulo-form{
    text-align:center;
    margin-bottom:25px;
    font-size:22px;
    font-weight:800;
    color:#7c1818;
}

/* ✅ título más compacto en móvil */
@media(max-width:720px){
    .titulo-form{
        font-size:20px;
        margin-bottom:18px;
    }
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

/* ✅ inputs/select uniformes */
.form-grupo input,
.form-grupo select{
    width:100%;
    height:42px;
    padding:0 12px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-size:14px;
    line-height:42px;
    box-sizing:border-box;
}

/* ✅ input texto con line-height normal */
.form-grupo input[type="text"]{
    line-height:normal;
    padding:10px 12px;
    height:auto;
    min-height:42px;
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

/* ✅ botones consistentes */
.btn-accion,
.btn-cancelar{
    border:none;
    padding:0 18px;
    height:42px;
    line-height:42px;
    border-radius:8px;
    cursor:pointer;
    font-weight:800;
    white-space:nowrap;
}

.btn-accion{
    background:#b22b27;
    color:white;
}
.btn-accion:hover{ background:#941c1c; }

.btn-cancelar{
    background:#777;
    color:white;
}
.btn-cancelar:hover{ background:#5f5f5f; }

/* ✅ responsive botones */
@media(max-width:520px){
    .acciones-form{
        flex-direction:column;
        align-items:stretch;
    }
    .btn-accion,
    .btn-cancelar{
        width:100%;
    }
}
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
