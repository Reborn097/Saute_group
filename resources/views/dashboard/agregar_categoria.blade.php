@extends('layouts.dashboard')

@section('titulo', 'Agregar Categoría')

@section('contenido')
<div class="contenedor">
    <form action="{{ route('dashboard.categorias.guardar') }}" method="POST">
        @csrf
        <div class="form-grupo">
            <label for="nombre">Nombre de la categoría</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Lácteos" required>
        </div>

        <div class="form-grupo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Describe brevemente la categoría"></textarea>
        </div>

        <div class="form-grupo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
            </select>
        </div>

        <div class="form-botones">
            <button type="button" class="btn cancelar" onclick="window.location.href='{{ route('dashboard.productos') }}'">Cancelar</button>
            <button type="submit" class="btn">Guardar Categoría</button>
        </div>
    </form>
</div>

<style>
    .contenedor {
        background-color: #fae7d0;
        padding: 35px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        max-width: 800px;
        margin: 0 auto;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-grupo {
        display: flex;
        flex-direction: column;
    }

    label {
        font-weight: 600;
        margin-bottom: 6px;
        color: #333;
    }

    input, textarea, select {
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 10px 14px;
        font-family: 'Poppins';
        font-size: 1em;
        background-color: #fff;
    }

    input:focus, textarea:focus, select:focus {
        border-color: #b22b27;
        outline: none;
        box-shadow: 0 0 4px rgba(178, 43, 39, 0.3);
    }

    textarea {
        resize: none;
    }

    .form-botones {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 10px;
    }

    .btn {
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn:hover {
        background-color: #911f1d;
    }

    .cancelar {
        background-color: #999;
    }

    .cancelar:hover {
        background-color: #777;
    }
</style>
@endsection
