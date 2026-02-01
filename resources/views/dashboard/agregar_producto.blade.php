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
/* ===== CONTENEDOR ===== */
.contenedor-form {
    max-width: 900px;
    margin: 30px auto;
    background-color: #fbe9d7;
    padding: 34px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.form-grupo { margin-bottom: 18px; }

label {
    font-weight: 700;
    margin-bottom: 8px;
    display: block;
}

/* inputs/select consistentes */
input, select {
    width: 100%;
    height: 42px;
    padding: 0 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background: #fff;
    box-sizing: border-box;
    font-size: 14px;
}

/* ===== PRESENTACIONES ===== */
.presentacion-item {
    border: 1px solid #f1d3b8;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 14px;
    background: #fff7ef;
}

.presentacion-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 12px;
}

/* proveedores */
.proveedores-container {
    margin-bottom: 10px;
}

/* ✅ Proveedor item en grid (ya no se rompe en móvil) */
.proveedor-item {
    display: grid;
    grid-template-columns: 1.4fr .8fr 1fr 1fr 44px; /* proveedor | precio | inicio | fin | X */
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.proveedor-item .btn-quitar{
    height: 42px;
    width: 44px;
    padding: 0;
    border-radius: 10px;
}

/* acciones de presentación */
.presentacion-acciones {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

/* ===== BOTONES ===== */
.btn-agregar,
.btn-quitar,
.btn-cancelar,
.btn-guardar{
    height: 42px;
    padding: 0 14px;
    border-radius: 10px;
    cursor: pointer;
    border: none;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    box-sizing: border-box;
    white-space: nowrap;
}

.btn-agregar { background: #b22b27; color: #fff; }
.btn-agregar:hover { background:#941c1c; }

.btn-quitar { background: #777; color: #fff; }
.btn-quitar:hover { filter: brightness(.95); }

.botones {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.btn-cancelar { background: #aaa; color: white; }
.btn-cancelar:hover { filter: brightness(.95); }

.btn-guardar { background: #b22b27; color: white; }
.btn-guardar:hover { background:#941c1c; }

/* ===========================
   RESPONSIVE
   =========================== */

/* Tablet */
@media (max-width: 900px){
    .contenedor-form{
        max-width: 100%;
        margin: 18px auto;
        padding: 22px;
    }
}

/* Celular */
@media (max-width: 600px){
    .contenedor-form{
        padding: 16px 14px;
        margin: 12px auto;
        border-radius: 12px;
    }

    /* grid de presentacion a una columna */
    .presentacion-grid{
        grid-template-columns: 1fr;
    }

    /* ✅ cada proveedor se vuelve 2 columnas */
    .proveedor-item{
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    /* botón X ocupa toda la fila en móvil para que sea tocable */
    .proveedor-item .btn-quitar{
        grid-column: 1 / -1;
        width: 100%;
    }

    /* acciones: todo full width */
    .presentacion-acciones{
        justify-content: stretch;
    }
    .presentacion-acciones .btn-agregar,
    .presentacion-acciones .btn-quitar{
        width: 100%;
    }

    /* botones finales full width */
    .botones{
        justify-content: stretch;
    }
    .botones .btn-cancelar,
    .botones .btn-guardar{
        width: 100%;
    }
}

/* Muy pequeño */
@media (max-width: 380px){
    input, select{ font-size: 13px; }
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













