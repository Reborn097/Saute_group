@extends('layouts.dashboard')

@section('titulo', 'Actualizar Precio')

@section('contenido')
<div class="contenedor-form">

    <form action="{{ route('producto_proveedor.actualizar_precio', $relacion->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Producto --}}
        <div class="form-grupo">
            <label>Producto</label>
            <input type="text" value="{{ optional($relacion->presentacion?->producto)->nombre ?? '-' }}" disabled>
        </div>

        <div class="form-grupo">
            <label>Presentacion</label>
            <input type="text" value="{{ $relacion->presentacion?->descripcion ?? '-' }}" disabled>
        </div>

        {{-- Proveedor (solo admin) --}}
        @if(auth()->user()->role === 'admin')
            <div class="form-grupo">
                <label>Proveedor</label>
                <input type="text" value="{{ $relacion->proveedor->nombre }}" disabled>
            </div>
        @endif

        {{-- Precio --}}
        <div class="form-grupo">
            <label for="precio">Nuevo precio</label>
            <input
                type="number"
                id="precio"
                name="precio"
                step="0.01"
                min="0"
                value="{{ old('precio', $relacion->precio_vigente) }}"
                required>
        </div>

        {{-- Vigencia inicio --}}
        <div class="form-grupo">
            <label for="fecha_vigencia_inicio">Fecha de inicio de validez</label>
            <input
                type="date"
                id="fecha_vigencia_inicio"
                name="fecha_vigencia_inicio"
                value="{{ old('fecha_vigencia_inicio', optional($relacion->historialUltimo)->vigencia_inicio ?? date('Y-m-d')) }}"
                required>
        </div>

        {{-- Vigencia final --}}
        <div class="form-grupo">
            <label for="fecha_vigencia_final">Fecha final de validez</label>
            <input
                type="date"
                id="fecha_vigencia_final"
                name="fecha_vigencia_final"
                value="{{ old('fecha_vigencia_final', optional($relacion->historialUltimo)->vigencia_fin) }}">
        </div>

        {{-- Botones --}}
        <div class="botones">
            @php
                // ✅ A dónde regresa cada rol
                $rutaCancelar = auth()->user()->role === 'admin'
                    ? route('dashboard.precios')
                    : route('proveedor.precios'); // <- la creamos abajo
            @endphp

            <a href="{{ $rutaCancelar }}" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-guardar">Guardar Cambios</button>
        </div>
    </form>
</div>

<style>
.contenedor-form {
    max-width: 900px;
    margin: 40px auto;
    background-color: #fbe9d7;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
}
.form-grupo { margin-bottom: 20px; }
label { display:block; font-weight:600; color:#333; margin-bottom:8px; }
input[type="text"], input[type="number"], input[type="date"] {
    width:100%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:1rem;
    background:#fff;
}
.botones {
    display:flex;
    justify-content:flex-end;
    gap:15px;
    margin-top:25px;
}
.btn-cancelar {
    background:#aaa;
    color:#fff;
    padding:10px 20px;
    border-radius:8px;
    text-decoration:none;
    transition:background-color .3s;
}
.btn-cancelar:hover { background:#888; }
.btn-guardar {
    background:#b22b27;
    color:#fff;
    padding:10px 25px;
    border:none;
    border-radius:8px;
    font-weight:600;
    cursor:pointer;
    transition:background-color .3s;
}
.btn-guardar:hover { background:#8c1f1b; }
</style>
@endsection
