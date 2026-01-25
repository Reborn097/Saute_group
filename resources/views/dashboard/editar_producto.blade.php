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

        {{-- Cantidad o tamaño --}}
        <div class="form-grupo">
            <label for="valor_medida">Cantidad o tamaño</label>
            <input
                type="number"
                id="valor_medida"
                name="valor_medida"
                value="{{ old('valor_medida', $producto->valor_medida) }}"
                step="0.01"
                min="0"
                required>
        </div>

        {{-- Tipo de unidad --}}
        <div class="form-grupo">
            <label for="unidad_medida">Tipo de unidad</label>
            <select id="unidad_medida" name="unidad_medida" required>
                <option value="">Seleccione una unidad</option>
                @php
                    $unidades = ['kg', 'g', 'mg', 'lb', 'L', 'mL', 'gal', 'pieza', 'paquete', 'docena', 'media docena', 'unidad', 'bote', 'saco', 'bulto'];
                @endphp
                @foreach($unidades as $unidad)
                    <option value="{{ $unidad }}" {{ $producto->unidad_medida == $unidad ? 'selected' : '' }}>
                        {{ $unidad }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Proveedores y precios dinámicos --}}
        <div class="form-grupo">
            <label>Proveedores y precios</label>

            <div id="proveedores-container">
                @forelse($producto->proveedores as $i => $prov)
                    <div class="proveedor-item">
                        <select name="proveedores[{{ $i }}][id]" required>
                            <option value="">Seleccione un proveedor</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ $prov->id == $p->id ? 'selected' : '' }}>
                                    {{ $p->nombre }}
                                </option>
                            @endforeach
                        </select>

                        <input type="number" step="0.01" name="proveedores[{{ $i }}][precio]"
                               value="{{ $prov->pivot->precio }}" placeholder="Precio" required>

                        <input type="date" name="proveedores[{{ $i }}][fecha_vigencia_inicio]"
                               value="{{ $prov->pivot->fecha_vigencia_inicio }}" required>

                        <input type="date" name="proveedores[{{ $i }}][fecha_vigencia_final]"
                               value="{{ $prov->pivot->fecha_vigencia_final }}" required>

                        <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">✖</button>
                    </div>
                @empty
                    <div class="proveedor-item">
                        <select name="proveedores[0][id]" required>
                            <option value="">Seleccione un proveedor</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>

                        <input type="number" step="0.01" name="proveedores[0][precio]" placeholder="Precio" required>
                        <input type="date" name="proveedores[0][fecha_vigencia_inicio]" required>
                        <input type="date" name="proveedores[0][fecha_vigencia_final]" required>

                        <button type="button" class="btn-quitar" onclick="this.parentElement.remove()">✖</button>
                    </div>
                @endforelse
            </div>

            <button type="button" class="btn-agregar" onclick="agregarProveedor()">+ Agregar otro proveedor</button>
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
let index = {{ count($producto->proveedores) }};
function agregarProveedor() {
    const contenedor = document.getElementById('proveedores-container');
    const nuevo = document.createElement('div');
    nuevo.classList.add('proveedor-item');
    nuevo.innerHTML = `
        <select name="proveedores[${index}][id]" required>
            <option value="">Seleccione un proveedor</option>
            @foreach($proveedores as $p)
                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
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
