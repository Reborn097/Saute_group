@extends('layouts.dashboard')

@section('titulo', 'Nuevo almacén')

@section('contenido')

<div class="contenedor" style="max-width:600px; margin:0 auto;">

    <h2 style="text-align:center; margin-bottom:20px;">
        Registrar nuevo almacén para {{ $unidad->nombre }}
    </h2>

    <form action="{{ route('almacenes.store', $unidad->id) }}" method="POST">
        @csrf

        <label><b>Nombre del almacén:</b></label>
        <input type="text" name="nombre" required>

        <label><b>Tipo:</b></label>
        <select name="tipo" required>
            <option value="">Seleccione tipo…</option>
            <option value="seco">Secos</option>
            <option value="refrigeracion">Refrigeración</option>
            <option value="congelado">Congelado</option>
            <option value="varios">Varios</option>
        </select>

        <label><b>Ubicación:</b></label>
        <input type="text" name="ubicacion">

        <div style="text-align:center; margin-top:20px;">
            <button class="btn-guardar">Guardar</button>
            <button type="button" class="btn-cancelar"
                onclick="window.location.href='{{ route('almacenes.index', $unidad->id) }}'">
                Cancelar
            </button>
        </div>
    </form>

</div>

@endsection
