@extends('layouts.dashboard')

@section('titulo', 'Agregar Producto')

@section('contenido')
<div class="contenedor-form">

    <form action="{{ route('dashboard.productos.guardar') }}" method="POST">
        @csrf

        {{-- Nombre del producto --}}
        <div class="form-grupo">
            <label for="nombre">Nombre del producto</label>
            <input 
                type="text" 
                id="nombre" 
                name="nombre" 
                placeholder="Ej. Queso Oaxaca" 
                required>
        </div>

        {{-- Categoría --}}
        <div class="form-grupo">
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id" required>
                <option value="">Seleccione una categoría</option>
            @forelse($categorias as $categoria)
                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
            @empty
                <option disabled>No hay categorías registradas</option>
            @endforelse
            </select>
        </div>

        {{-- Valor de medida --}}
        <div class="form-grupo">
            <label for="valor_medida">Valor de medida</label>
            <input 
                type="text" 
                id="valor_medida" 
                name="valor_medida" 
                placeholder="Ej. 1 kg, 500 ml">
        </div>

        {{-- Unidad de medida --}}
        <div class="form-grupo">
            <label for="unidad_medida">Unidad de medida</label>
            <input 
                type="text" 
                id="unidad_medida" 
                name="unidad_medida" 
                placeholder="Ej. kg, L, pieza">
        </div>

        <div class="form-group">
            <label for="proveedor_id">Proveedor</label>
            <select id="proveedor_id" name="proveedor_id" required>
                <option value="">Seleccione un proveedor</option>
                @foreach (\App\Models\Proveedor::all() as $proveedor)
                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
        </div>



        {{-- Precio --}}
        <div class="form-grupo">
            <label for="precio">Precio</label>
            <input 
                type="number" 
                id="precio" 
                name="precio" 
                step="0.01" 
                placeholder="Ej. 25.50">
        </div>

        {{-- Botones --}}
        <div class="botones">
            <a href="{{ route('dashboard.productos') }}" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-guardar">Guardar Producto</button>
        </div>
    </form>
</div>

<style>
/* ======== FORMULARIO ======== */
.contenedor-form {
    max-width: 900px;
    margin: 40px auto;
    background-color: #fbe9d7;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
}

.titulo-seccion {
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.8rem;
    color: #b22b27;
    font-weight: 700;
}

.form-grupo {
    margin-bottom: 20px;
}

label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

input[type="text"],
input[type="number"],
select,
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    background-color: #fff;
}

textarea {
    resize: none;
}

.botones {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 25px;
}

.btn-cancelar {
    background-color: #aaa;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s;
}

.btn-cancelar:hover {
    background-color: #888;
}

.btn-guardar {
    background-color: #b22b27;
    color: white;
    padding: 10px 25px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-guardar:hover {
    background-color: #8c1f1b;
}
</style>
@endsection
