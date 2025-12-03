@extends('layouts.dashboard')

@section('titulo', 'Editar Unidad Operativa')

@section('contenido')

<div class="contenedor">

    <h2 style="margin-bottom:20px;">Editar Unidad Operativa</h2>

    <form action="{{ route('unidades.update', $unidad->id) }}" method="POST" style="max-width:600px;">
        @csrf
        @method('PUT')

        <label><b>Nombre:</b></label>
        <input type="text" name="nombre" value="{{ $unidad->nombre }}" required class="input-buscar" style="margin-bottom:15px;">

        <label><b>Tipo:</b></label>
        <input type="text" name="tipo" value="{{ $unidad->tipo }}" class="input-buscar" style="margin-bottom:15px;">

        <label><b>Ubicación:</b></label>
        <input type="text" name="ubicacion" value="{{ $unidad->ubicacion }}" class="input-buscar" style="margin-bottom:15px;">

        <div style="margin-top:20px;">
            <button class="btn-guardar">Actualizar</button>
            <button type="button" class="btn-cancelar" onclick="window.location.href='{{ route('unidades.index') }}'">
                Cancelar
            </button>
        </div>

    </form>

</div>

@endsection
