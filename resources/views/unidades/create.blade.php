@extends('layouts.dashboard')

@section('titulo', 'Crear Unidad Operativa')

@section('contenido')

<div class="contenedor" style="max-width: 600px; margin: 0 auto;">

    <h2 style="text-align:center; margin-bottom:20px;">Registrar Unidad Operativa</h2>

    <form action="{{ route('unidades.store') }}" method="POST">
        @csrf

        <label><b>Nombre:</b></label>
        <input type="text" name="nombre" required>

        <label><b>Tipo:</b></label>
        <select name="tipo" required>
            <option value="">Seleccione tipo…</option>
            <option value="comedor">Comedor</option>
            <option value="cafeteria">Cafetería</option>
            <option value="bodega">Bodega</option>
            <option value="externo">Externo</option>
        </select>

        <label><b>Ubicación:</b></label>
        <input type="text" name="ubicacion">

        <div style="text-align:center; margin-top:20px;">
            <button class="btn-guardar">Registrar</button>
            <button type="button" class="btn-cancelar" onclick="window.location.href='{{ route('unidades.index') }}'">
                Cancelar
            </button>
        </div>

    </form>

</div>

@endsection
