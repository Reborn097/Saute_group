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

        {{-- Cantidad o tamaño --}}
        <div class="form-grupo">
            <label for="valor_medida">Cantidad o tamaño</label>
            <input 
                type="number" 
                id="valor_medida" 
                name="valor_medida" 
                placeholder="Ej. 1, 500" 
                step="0.01" 
                min="0" 
                required>
        </div>

        {{-- Tipo de unidad --}}
        <div class="form-grupo">
            <label for="unidad_medida">Tipo de unidad</label>
            <select id="unidad_medida" name="unidad_medida" required>
                <option value="">Seleccione una unidad</option>
                <option value="kg">kg – kilogramo</option>
                <option value="g">g – gramo</option>
                <option value="mg">mg – miligramo</option>
                <option value="lb">lb – libra </option>
                <option value="L">L – litro</option>
                <option value="mL">mL – mililitro</option>
                <option value="gal">gal – galón </option>
                <option value="pieza">pieza – unidad individual </option>
                <option value="paquete">paquete – conjunto cerrado</option>
                <option value="docena">docena – 12 unidades</option>
                <option value="media docena">media docena – 6 unidades</option>
                <option value="unidad">unidad – genérico</option>
                <option value="bote">bote / frasco / botella / lata / caja / bolsa / sobre</option>
                <option value="saco">saco – 25 o 50 kg</option>
                <option value="bulto">bulto</option>
            </select>
        </div>

        {{-- 🔹 Proveedores y precios dinámicos --}}
        <div class="form-grupo">
            <label>Proveedores y precios</label>

            <div id="proveedores-container">
                <div class="proveedor-item">
                    <select name="proveedores[0][id]" required>
                        <option value="">Seleccione un proveedor</option>
                        @foreach(\App\Models\Proveedor::all() as $proveedor)
                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                        @endforeach
                    </select>

                    <input type="number" step="0.01" name="proveedores[0][precio]" placeholder="Precio" required>
                    <input type="date" name="proveedores[0][fecha_vigencia_inicio]" required>
                    <input type="date" name="proveedores[0][fecha_vigencia_final]" required>

                    <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">✖</button>
                </div>
            </div>

            <button type="button" class="btn-agregar" onclick="agregarProveedor()">+ Agregar otro proveedor</button>
        </div>

        {{-- Botones --}}
        <div class="botones">
            <a href="{{ route('dashboard.productos') }}" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-guardar">Guardar Producto</button>
        </div>
    </form>
</div>

{{-- ===================== SCRIPTS ===================== --}}
<script>
let index = 1;
function agregarProveedor() {
    const contenedor = document.getElementById('proveedores-container');
    const nuevo = document.createElement('div');
    nuevo.classList.add('proveedor-item');
    nuevo.innerHTML = `
        <select name="proveedores[${index}][id]" required>
            <option value="">Seleccione un proveedor</option>
            @foreach(\App\Models\Proveedor::all() as $proveedor)
                <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
            @endforeach
        </select>

        <input type="number" step="0.01" name="proveedores[${index}][precio]" placeholder="Precio" required>
        <input type="date" name="proveedores[${index}][fecha_vigencia_inicio]" required>
        <input type="date" name="proveedores[${index}][fecha_vigencia_final]" required>

        <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">✖</button>
    `;
    contenedor.appendChild(nuevo);
    index++;
}
</script>

{{-- ===================== ESTILOS ===================== --}}
<style>
.contenedor-form {
    max-width: 900px;
    margin: 40px auto;
    background-color: #fbe9d7;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
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
.proveedor-item {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 8px;
}
.btn-agregar {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}
.btn-quitar {
    background-color: #888;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
}
.btn-agregar:hover {
    background-color: #8c1f1b;
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
