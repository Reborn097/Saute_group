@extends('layouts.dashboard')

@section('titulo', 'Editar Producto')

@section('contenido')
<div class="contenedor-form">

    <form action="{{ route('dashboard.productos.actualizar', $producto->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Nombre del producto --}}
        <div class="form-grupo">
            <label for="nombre">Nombre del producto</label>
            <input
                type="text"
                id="nombre"
                name="nombre"
                value="{{ old('nombre', $producto->nombre) }}"
                placeholder="Ej. Queso Oaxaca"
                required>
        </div>

        {{-- Marca del producto --}}
        <div class="form-grupo">
            <label for="marca">Marca</label>
            <input
                type="text"
                id="marca"
                name="marca"
                value="{{ old('marca', $producto->marca) }}"
                placeholder="Ej. Lala, Nestlé, La Costeña"
                required>
        </div>

        {{-- Categoría --}}
        <div class="form-grupo">
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id" required>
                <option value="">Seleccione una categoría</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}"
                        {{ $producto->categoria_id == $categoria->id ? 'selected' : '' }}>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        
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

        {{-- Tipo de unidad --}}
        <div class="form-grupo">
            <label for="unidad_medida">Tipo de unidad</label>
            <select id="unidad_medida" name="unidad_medida" required>
                <option value="">Seleccione una unidad</option>
                @foreach($unidadesBase as $unidad => $label)
                    <option value="{{ $unidad }}" {{ $producto->unidad_medida == $unidad ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Presentaciones y proveedores --}}
        <div class="form-grupo">
            <label>Presentaciones</label>

            @php
                $presentaciones = $producto->presentaciones ?? collect();
                $presentaciones = $presentaciones->count() ? $presentaciones : collect([null]);
            @endphp

            <div id="presentaciones-container">
                @foreach($presentaciones as $pIndex => $pres)
                    <div class="presentacion-item" data-index="{{ $pIndex }}">
                        <input type="hidden" name="presentaciones[{{ $pIndex }}][id]" value="{{ $pres?->id }}">

                        <div class="presentacion-grid">
                            <input
                                type="text"
                                name="presentaciones[{{ $pIndex }}][descripcion]"
                                value="{{ old('presentaciones.'.$pIndex.'.descripcion', $pres?->descripcion) }}"
                                placeholder="Descripcion"
                                required>

                            <select name="presentaciones[{{ $pIndex }}][unidad_base]">
                                <option value="">Unidad base</option>
                                @foreach($unidadesBase as $key => $label)
                                    <option value="{{ $key }}" {{ old('presentaciones.'.$pIndex.'.unidad_base', $pres?->unidad_base) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="presentaciones[{{ $pIndex }}][contenido]"
                                value="{{ old('presentaciones.'.$pIndex.'.contenido', $pres?->contenido) }}"
                                placeholder="Contenido">

                            <select name="presentaciones[{{ $pIndex }}][unidad_contenido]">
                                <option value="">Unidad contenido</option>
                                @foreach($unidadesContenido as $key => $label)
                                    <option value="{{ $key }}" {{ old('presentaciones.'.$pIndex.'.unidad_contenido', $pres?->unidad_contenido) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @php
                            $proveedoresPres = $pres?->proveedores ?? collect();
                            $proveedoresPres = $proveedoresPres->count() ? $proveedoresPres : collect([null]);
                        @endphp

                        <div class="proveedores-container" data-presentacion="{{ $pIndex }}">
                            @foreach($proveedoresPres as $i => $pp)
                                <div class="proveedor-item">
                                    <select name="presentaciones[{{ $pIndex }}][proveedores][{{ $i }}][id]" required>
                                        <option value="">Seleccione un proveedor</option>
                                        @foreach($proveedores as $p)
                                            <option value="{{ $p->id }}" {{ old('presentaciones.'.$pIndex.'.proveedores.'.$i.'.id', $pp?->proveedor_id) == $p->id ? 'selected' : '' }}>
                                                {{ $p->nombre }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="presentaciones[{{ $pIndex }}][proveedores][{{ $i }}][precio]"
                                        value="{{ old('presentaciones.'.$pIndex.'.proveedores.'.$i.'.precio', $pp?->precio_vigente) }}"
                                        placeholder="Precio"
                                        required>

                                    <input
                                        type="date"
                                        name="presentaciones[{{ $pIndex }}][proveedores][{{ $i }}][fecha_vigencia_inicio]"
                                        value="{{ old('presentaciones.'.$pIndex.'.proveedores.'.$i.'.fecha_vigencia_inicio', date('Y-m-d')) }}"
                                        required>

                                    <input
                                        type="date"
                                        name="presentaciones[{{ $pIndex }}][proveedores][{{ $i }}][fecha_vigencia_final]"
                                        value="{{ old('presentaciones.'.$pIndex.'.proveedores.'.$i.'.fecha_vigencia_final') }}"
                                        placeholder="(opcional)">

                                    <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">x</button>
                                </div>
                            @endforeach
                        </div>

                        <div class="presentacion-acciones">
                            <button type="button" class="btn-agregar" onclick="agregarProveedor({{ $pIndex }})">+ Agregar proveedor</button>
                            <button type="button" class="btn-quitar" onclick="this.closest('.presentacion-item').remove()">Quitar presentacion</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" class="btn-agregar" onclick="agregarPresentacion()">+ Agregar presentacion</button>
        </div>

        {{-- Estado --}}
        <div class="form-grupo">
            <label>Estado</label>
            <select name="estado" required>
                <option value="1" {{ $producto->estado == 1 ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ $producto->estado == 0 ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        {{-- Botones --}}
        <div class="botones">
            <a href="{{ route('dashboard.productos') }}" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-guardar">Actualizar Producto</button>
        </div>
    </form>
</div>

<script>
let presentacionIndex = {{ $presentaciones->count() }};

function agregarPresentacion() {
    const contenedor = document.getElementById('presentaciones-container');
    const pIndex = presentacionIndex;
    const hoy = new Date().toISOString().split("T")[0];

    const nuevo = document.createElement('div');
    nuevo.classList.add('presentacion-item');
    nuevo.setAttribute('data-index', pIndex);

    nuevo.innerHTML = `
        <input type="hidden" name="presentaciones[${pIndex}][id]" value="">

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
            <div class="proveedor-item">
                <select name="presentaciones[${pIndex}][proveedores][0][id]" required>
                    <option value="">Seleccione un proveedor</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
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

    nuevo.innerHTML = `
        <select name="presentaciones[${pIndex}][proveedores][${idx}][id]" required>
            <option value="">Seleccione un proveedor</option>
            @foreach($proveedores as $p)
                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
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

label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

/* ✅ Base uniforme para inputs/selects (incluye date) */
input[type="text"],
input[type="number"],
input[type="date"],
select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    background-color: #fff;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
}

/* ✅ select con flecha consistente */
select{
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image:
        linear-gradient(45deg, transparent 50%, #7a2b26 50%),
        linear-gradient(135deg, #7a2b26 50%, transparent 50%);
    background-position:
        calc(100% - 18px) 50%,
        calc(100% - 12px) 50%;
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 36px;
}

/* ✅ date “bonito” y consistente */
input[type="date"]{
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    explain: none;
    padding-right: 38px;
}

/* ✅ ícono del calendario mejor alineado */
input[type="date"]::-webkit-calendar-picker-indicator{
    opacity: .85;
    cursor: pointer;
    padding: 6px;
    margin-right: 2px;
}
input[type="date"]::-webkit-calendar-picker-indicator:hover{
    opacity: 1;
}

/* ✅ focus consistente */
input:focus,
select:focus{
    border-color: rgba(178, 43, 39, .55);
    box-shadow: 0 0 0 4px rgba(178, 43, 39, .15);
}

/* ✅ fila proveedores */
.proveedor-item {
    display: grid;
    grid-template-columns: 1.35fr .8fr .9fr .9fr auto;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
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
    margin-bottom: 6px;
}
.proveedores-container {
    margin-bottom: 6px;
}

/* responsive: que no se rompa en pantallas chicas */
@media (max-width: 900px){
    .proveedor-item{
        grid-template-columns: 1fr 1fr;
    }
    .proveedor-item select,
    .proveedor-item input{
        width: 100%;
    }
    .btn-quitar{
        grid-column: span 2;
        justify-self: end;
    }
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
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
}

.btn-agregar:hover { background-color: #8c1f1b; }

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
.btn-cancelar:hover { background-color: #888; }
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
.btn-guardar:hover { background-color: #8c1f1b; }
</style>
@endsection








