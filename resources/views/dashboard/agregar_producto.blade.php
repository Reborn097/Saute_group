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
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tipo de unidad (PRIMERO) --}}
        <div class="form-grupo">
            <label for="unidad_medida">Tipo de unidad</label>
            <select id="unidad_medida" name="unidad_medida" required>
                <option value="">Seleccione una unidad</option>
                <option value="kg">kg – kilogramo</option>
                <option value="g">g – gramo</option>
                <option value="mg">mg – miligramo</option>
                <option value="lb">lb – libra</option>
                <option value="L">L – litro</option>
                <option value="mL">mL – mililitro</option>
                <option value="gal">gal – galón</option>
                <option value="pieza">pieza</option>
                <option value="paquete">paquete</option>
                <option value="docena">docena</option>
                <option value="media docena">media docena</option>
                <option value="unidad">unidad</option>
                <option value="bote">bote / frasco / botella / lata</option>
                <option value="saco">saco</option>
                <option value="bulto">bulto</option>
            </select>
        </div>

        {{-- Cantidad o tamaño (DESPUÉS) --}}
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

        {{-- Proveedores y precios dinámicos --}}
        <div class="form-grupo">
            <label>Proveedores y precios</label>

            <div id="proveedores-container">
                <div class="proveedor-item" style="position: relative;">
                    <select name="proveedores[0][id]" required>
                        <option value="">Seleccione un proveedor</option>
                        @foreach(\App\Models\Proveedor::all() as $proveedor)
                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                        @endforeach
                    </select>

                    <input type="number" step="0.01" name="proveedores[0][precio]" placeholder="Precio" required>

                    <input 
                        type="date" 
                        name="proveedores[0][fecha_vigencia_inicio]" 
                        value="{{ date('Y-m-d') }}" 
                        required>

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

{{-- ===================== ESTILOS ===================== --}}
<style>
.contenedor-form {
    max-width: 900px;
    margin: 40px auto;
    background-color: #fbe9d7;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
.form-grupo { margin-bottom: 20px; }
label { font-weight: 600; margin-bottom: 8px; display: block; }
input, select {
    width: 100%; padding: 10px; border: 1px solid #ccc;
    border-radius: 6px; background: #fff;
}
.proveedor-item {
    display: flex; gap: 10px; margin-bottom: 8px;
}
.btn-agregar {
    background: #b22b27; color: white;
    padding: 8px 12px; border-radius: 6px; cursor: pointer;
}
.btn-quitar {
    background: #777; color: white; padding: 6px 10px;
    border-radius: 6px; cursor: pointer;
}
.botones { display: flex; justify-content: flex-end; gap: 15px; }
.btn-cancelar {
    background: #aaa; padding: 10px 20px; border-radius: 8px; color: white;
}
.btn-guardar {
    background: #b22b27; padding: 10px 25px;
    color: white; border-radius: 8px; cursor: pointer;
}

.mensaje-nueva-linea {
    position: absolute;
    top: -25px;
    left: 5px;
    background: #d6f5d6;
    border: 1px solid #4caf50;
    padding: 4px 10px;
    border-radius: 5px;
    color: #2e7d32;
    font-size: 0.85rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.6s ease-in-out;
}
</style>

{{-- ===================== SCRIPT ===================== --}}
<script>
let index = 1;

function agregarProveedor() {
    const hoy = new Date().toISOString().split("T")[0];

    const contenedor = document.getElementById('proveedores-container');
    const nuevo = document.createElement('div');
    nuevo.classList.add('proveedor-item');
    nuevo.style.position = "relative";

    nuevo.innerHTML = `
        <select name="proveedores[${index}][id]" required>
            <option value="">Seleccione un proveedor</option>
            @foreach(\App\Models\Proveedor::all() as $proveedor)
                <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
            @endforeach
        </select>

        <input type="number" step="0.01" name="proveedores[${index}][precio]" placeholder="Precio" required>
        <input type="date" name="proveedores[${index}][fecha_vigencia_inicio]" value="${hoy}" required>
        <input type="date" name="proveedores[${index}][fecha_vigencia_final]" required>

        <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">✖</button>

        <div class="mensaje-nueva-linea">✔ Nueva línea de proveedor creada</div>
    `;

    contenedor.appendChild(nuevo);
    index++;

    const mensaje = nuevo.querySelector(".mensaje-nueva-linea");

    mensaje.style.opacity = "1";
    setTimeout(() => {
        mensaje.style.opacity = "0";
    }, 2000);
}
</script>

@endsection
