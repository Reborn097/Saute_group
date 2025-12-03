@extends('layouts.dashboard')

@section('titulo', 'Editar almacén')

@section('contenido')

<div class="contenedor" style="max-width:600px; margin:0 auto;">

    <h2 style="text-align:center; margin-bottom:20px;">
        Editar almacén {{ $almacen->nombre }}
    </h2>

    <form action="{{ route('almacenes.update', [$unidad->id, $almacen->id]) }}" method="POST">
        @csrf
        @method('PUT')

        <label><b>Nombre del almacén:</b></label>
        <input type="text" name="nombre" value="{{ $almacen->nombre }}" required>

        <label><b>Tipo:</b></label>
        <select name="tipo" required>
            <option value="seco"         {{ $almacen->tipo=='seco' ? 'selected' : '' }}>Secos</option>
            <option value="refrigeracion" {{ $almacen->tipo=='refrigeracion' ? 'selected' : '' }}>Refrigeración</option>
            <option value="congelado"    {{ $almacen->tipo=='congelado' ? 'selected' : '' }}>Congelado</option>
            <option value="varios"       {{ $almacen->tipo=='varios' ? 'selected' : '' }}>Varios</option>
        </select>

        <label><b>Ubicación:</b></label>
        <input type="text" name="ubicacion" value="{{ $almacen->ubicacion }}">

        <div style="text-align:center; margin-top:20px;">
            <button class="btn-guardar">Actualizar</button>
            <button type="button" class="btn-cancelar"
                onclick="window.location.href='{{ route('almacenes.index', $unidad->id) }}'">
                Cancelar
            </button>
        </div>
    </form>

</div>

@endsection
