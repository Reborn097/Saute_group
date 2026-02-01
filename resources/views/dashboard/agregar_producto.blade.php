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
                value="{{ old('nombre') }}"
                placeholder="Ej. Queso Oaxaca"
                required>
        </div>

        {{-- Marca --}}
        <div class="form-grupo">
            <label for="marca">Marca</label>
            <input
                type="text"
                id="marca"
                name="marca"
                value="{{ old('marca') }}"
                placeholder="Ej. Lala, Nestlé, La Costeña"
                required>
        </div>


        {{-- Categoría --}}
        <div class="form-grupo">
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id" required>
                <option value="">Seleccione una categoría</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tipo de unidad (PRIMERO) --}}
        <div class="form-grupo">
            <label for="unidad_medida">Tipo de unidad</label>
            <select id="unidad_medida" name="unidad_medida" required>
                <option value="">Seleccione una unidad</option>
                @php
                    $unidadesBase = [
                        'unidad' => 'unidad',
                        'pieza' => 'pieza',
                        'paquete' => 'paquete',
                        'docena' => 'docena',
                        'media docena' => 'media docena',
                        'bote' => 'bote / frasco / botella / lata',
                        'saco' => 'saco',
                        'bulto' => 'bulto',
                    ];
                    $unidadesContenido = [
                        'g' => 'g - gramo',
                        'kg' => 'kg - kilogramo',
                        'mL' => 'mL - mililitro',
                        'L' => 'L - litro',
                    ];
                @endphp
                @foreach($unidadesBase as $key => $label)
                    <option value="{{ $key }}" {{ old('unidad_medida') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        {{-- Presentaciones y proveedores --}}
        <div class="form-grupo">
            <label>Presentaciones</label>

            <div id="presentaciones-container">
                <div class="presentacion-item" data-index="0">
                    <div class="presentacion-grid">
                        <input
                            type="text"
                            name="presentaciones[0][descripcion]"
                            value="{{ old('presentaciones.0.descripcion') }}"
                            placeholder="Descripcion"
                            required>

                        <select name="presentaciones[0][unidad_base]">
                            <option value="">Unidad base</option>
                            @foreach($unidadesBase as $key => $label)
                                <option value="{{ $key }}" {{ old('presentaciones.0.unidad_base') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="presentaciones[0][contenido]"
                            value="{{ old('presentaciones.0.contenido') }}"
                            placeholder="Contenido">

                        <select name="presentaciones[0][unidad_contenido]">
                            <option value="">Unidad contenido</option>
                            @foreach($unidadesContenido as $key => $label)
                                <option value="{{ $key }}" {{ old('presentaciones.0.unidad_contenido') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="proveedores-container" data-presentacion="0">
                        <div class="proveedor-item" style="position: relative;">
                            <select name="presentaciones[0][proveedores][0][id]" required>
                                <option value="">Seleccione un proveedor</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}"
                                        {{ old('presentaciones.0.proveedores.0.id') == $proveedor->id ? 'selected' : '' }}>
                                        {{ $proveedor->nombre }}
                                    </option>
                                @endforeach
                            </select>

                            <input
                                type="number"
                                step="0.01"
                                name="presentaciones[0][proveedores][0][precio]"
                                value="{{ old('presentaciones.0.proveedores.0.precio') }}"
                                placeholder="Precio"
                                required>

                            <input
                                type="date"
                                name="presentaciones[0][proveedores][0][fecha_vigencia_inicio]"
                                value="{{ old('presentaciones.0.proveedores.0.fecha_vigencia_inicio', date('Y-m-d')) }}"
                                required>

                            <input
                                type="date"
                                name="presentaciones[0][proveedores][0][fecha_vigencia_final]"
                                value="{{ old('presentaciones.0.proveedores.0.fecha_vigencia_final') }}"
                                placeholder="(opcional)">

                            <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">x</button>
                        </div>
                    </div>

                    <div class="presentacion-acciones">
                        <button type="button" class="btn-agregar" onclick="agregarProveedor(0)">+ Agregar proveedor</button>
                        <button type="button" class="btn-quitar" onclick="this.closest('.presentacion-item').remove()">Quitar presentacion</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-agregar" onclick="agregarPresentacion()">+ Agregar presentacion</button>
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
    display: flex; gap: 10px; margin-bottom: 8px; align-items:center;
}
.presentacion-item {
    border: 1px solid #f1d3b8;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
    background: #fff7ef;
}
.presentacion-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 10px;
}
.presentacion-acciones {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.proveedores-container {
    margin-bottom: 8px;
}
.btn-agregar {
    background: #b22b27; color: white;
    padding: 8px 12px; border-radius: 6px; cursor: pointer; border:none;
}
.btn-quitar {
    background: #777; color: white; padding: 6px 10px;
    border-radius: 6px; cursor: pointer; border:none;
}
.botones { display: flex; justify-content: flex-end; gap: 15px; margin-top: 10px; }
.btn-cancelar {
    background: #aaa; padding: 10px 20px; border-radius: 8px; color: white;
    text-decoration:none;
}
.btn-guardar {
    background: #b22b27; padding: 10px 25px;
    color: white; border: none; border-radius: 8px; cursor: pointer;
}

</style>

{{-- ===================== SCRIPT ===================== --}}
<script>
let presentacionIndex = 1;

function agregarPresentacion() {
    const contenedor = document.getElementById('presentaciones-container');
    const pIndex = presentacionIndex;

    const nuevo = document.createElement('div');
    nuevo.classList.add('presentacion-item');
    nuevo.setAttribute('data-index', pIndex);

    const hoy = new Date().toISOString().split("T")[0];

    nuevo.innerHTML = `
        <div class="presentacion-grid">
            <input
                type="text"
                name="presentaciones[${pIndex}][descripcion]"
                placeholder="Descripcion"
                required>

            <select name="presentaciones[${pIndex}][unidad_base]">
                <option value="">Unidad base</option>
                @foreach($unidadesBase as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <input
                type="number"
                step="0.01"
                min="0"
                name="presentaciones[${pIndex}][contenido]"
                placeholder="Contenido">

            <select name="presentaciones[${pIndex}][unidad_contenido]">
                <option value="">Unidad contenido</option>
                @foreach($unidadesContenido as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="proveedores-container" data-presentacion="${pIndex}">
            <div class="proveedor-item" style="position: relative;">
                <select name="presentaciones[${pIndex}][proveedores][0][id]" required>
                    <option value="">Seleccione un proveedor</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>

                <input type="number" step="0.01" name="presentaciones[${pIndex}][proveedores][0][precio]" placeholder="Precio" required>
                <input type="date" name="presentaciones[${pIndex}][proveedores][0][fecha_vigencia_inicio]" value="${hoy}" required>
                <input type="date" name="presentaciones[${pIndex}][proveedores][0][fecha_vigencia_final]" placeholder="(opcional)">

                <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">x</button>
            </div>
        </div>

        <div class="presentacion-acciones">
            <button type="button" class="btn-agregar" onclick="agregarProveedor(${pIndex})">+ Agregar proveedor</button>
            <button type="button" class="btn-quitar" onclick="this.closest('.presentacion-item').remove()">Quitar presentacion</button>
        </div>
    `;

    contenedor.appendChild(nuevo);
    presentacionIndex += 1;
}

function agregarProveedor(pIndex) {
    const hoy = new Date().toISOString().split("T")[0];
    const contenedor = document.querySelector(`.proveedores-container[data-presentacion="${pIndex}"]`);
    if (!contenedor) return;

    const idx = contenedor.querySelectorAll('.proveedor-item').length;
    const nuevo = document.createElement('div');
    nuevo.classList.add('proveedor-item');
    nuevo.style.position = "relative";

    nuevo.innerHTML = `
        <select name="presentaciones[${pIndex}][proveedores][${idx}][id]" required>
            <option value="">Seleccione un proveedor</option>
            @foreach($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
            @endforeach
        </select>

        <input type="number" step="0.01" name="presentaciones[${pIndex}][proveedores][${idx}][precio]" placeholder="Precio" required>
        <input type="date" name="presentaciones[${pIndex}][proveedores][${idx}][fecha_vigencia_inicio]" value="${hoy}" required>
        <input type="date" name="presentaciones[${pIndex}][proveedores][${idx}][fecha_vigencia_final]" placeholder="(opcional)">

        <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">x</button>
    `;

    contenedor.appendChild(nuevo);
}
</script>

@endsection













