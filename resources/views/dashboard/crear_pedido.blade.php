@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn" onclick="irMenuPrincipal()">Menú principal</button>
    </div>


    {{-- Encabezado --}}
    <div class="filtros">
        <div class="campo">
            <label>Fecha de solicitud:</label>
            <input type="date" id="fechaSolicitud" required>
        </div>
        <div class="campo">
            <label>Fecha de entrega:</label>
            <input type="date" id="fechaEntrega" required>
        </div>
        <div class="campo">
            <label>Proveedor:</label>
            <select id="proveedorFiltro">
                <option value="todos">Todos</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->nombre }}">{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tabla de productos --}}
    <h3>Productos disponibles</h3>
    <div class="tabla-contenedor">
        <table class="tabla-productos" id="tablaProductos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Unidad de medida</th>
                    <th>Precio</th>
                    <th>Seleccionar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $p)
                <tr data-proveedor="{{ $p->proveedores->first()->nombre ?? 'N/A' }}">
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                    <td>{{ $p->unidad_medida ?? 'N/A' }}</td>
                    <td>${{ number_format($p->proveedores->first()->pivot->precio ?? 0, 2) }}</td>
                    <td>
                        <button class="btn-seleccionar" 
                            onclick="abrirModal(
                                '{{ $p->id }}',
                                '{{ $p->nombre }}',
                                '{{ $p->categoria->nombre ?? '' }}',
                                '{{ $p->unidad_medida }}',
                                '{{ $p->proveedores->first()->pivot->precio ?? 0 }}'
                            )">
                            Seleccionar
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Tabla de productos en el pedido --}}
    <h3>Productos en el pedido</h3>
    <div class="tabla-contenedor">
        <table class="tabla-productos" id="tablaPedido">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Unidad</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    {{-- Botón final --}}
    <div class="acciones">
        <button class="btn-confirmar" id="btnHacerPedido">Hacer pedido</button>
    </div>
</div>

{{-- Modal para ingresar cantidad --}}
<div id="modalCantidad" class="modal">
    <div class="modal-contenido">
        <h3 id="modalTitulo"></h3>
        <label>Cantidad:</label>
        <input type="number" id="cantidadInput" min="1" value="1">
        <div class="modal-acciones">
            <button class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarCantidad()">Actualizar</button>
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<style>
.contenedor {
    background-color: #fae7d0;
    padding: 25px 35px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    max-width: 1100px;
    margin: 0 auto;
}
.filtros {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}
.campo label { display: block; font-weight: bold; }
.campo input, .campo select {
    width: 180px; padding: 6px; border-radius: 6px; border: 1px solid #aaa;
}
.tabla-contenedor { margin-top: 15px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; background: white; }
th, td { border: 1px solid #aaa; padding: 10px; text-align: left; }
th { background-color: #f0f0f0; }
.tabla-productos td:last-child { text-align: center; }
.btn-seleccionar, .btn-editar, .btn-eliminar {
    background-color: #b22b27; color: white; border: none;
    padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 0.9em;
}
.btn-seleccionar:hover, .btn-editar:hover, .btn-eliminar:hover {
    background-color: #911f1d;
}
.btn-eliminar { background-color: #555; }
.btn-eliminar:hover { background-color: #333; }
.btn-confirmar {
    background-color: #b22b27; color: white; border: none;
    padding: 10px 15px; border-radius: 8px; cursor: pointer;
    float: right; margin-top: 15px;
}
.modal {
    display: none; position: fixed; z-index: 999; left: 0; top: 0;
    width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4);
    justify-content: center; align-items: center;
}
.modal-contenido {
    background: #fff; padding: 25px 35px; border-radius: 12px;
    width: 350px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.modal h3 { font-size: 1.2em; color: #333; margin-bottom: 10px; }
.modal label { font-size: 0.9em; color: #444; display: block; margin-bottom: 8px; }
.modal input {
    width: 80%; padding: 8px; margin-bottom: 15px;
    border-radius: 6px; border: 1px solid #ccc; text-align: center; font-size: 1em;
}
.modal-acciones { display: flex; justify-content: center; gap: 15px; }
.btn-cancelar { background-color: #ccc; color: black; }
.btn-cancelar:hover { background-color: #aaa; }
</style>

<script>
let productoSeleccionado = null;
let productoEditandoIndex = null;
const productosPedido = [];

// Abrir modal (agregar o editar)
function abrirModal(id, nombre, categoria, unidad, precio, editar = false) {
    const fechaSolicitud = document.getElementById('fechaSolicitud').value;
    const fechaEntrega = document.getElementById('fechaEntrega').value;

    if (!fechaSolicitud || !fechaEntrega) {
        alert("Por favor selecciona las fechas antes de agregar productos.");
        return;
    }

    productoSeleccionado = { id, nombre, categoria, unidad, precio: parseFloat(precio) };
    document.getElementById('modalTitulo').innerText = editar ? `Editar ${nombre}` : `Agregar ${nombre}`;
    document.getElementById('btnAgregarModal').style.display = editar ? 'none' : 'inline-block';
    document.getElementById('btnActualizarModal').style.display = editar ? 'inline-block' : 'none';
    document.getElementById('modalCantidad').style.display = 'flex';
}

// Cerrar modal
function cerrarModal() {
    document.getElementById('modalCantidad').style.display = 'none';
    productoEditandoIndex = null;
}

// Agregar producto
function agregarProducto() {
    const cantidad = parseFloat(document.getElementById('cantidadInput').value);
    if (cantidad <= 0) return alert("Cantidad inválida");

    const subtotal = cantidad * productoSeleccionado.precio;
    productosPedido.push({ ...productoSeleccionado, cantidad, subtotal });

    actualizarTablaPedido();
    cerrarModal();
}

// Editar cantidad
function editarProducto(index) {
    const p = productosPedido[index];
    productoSeleccionado = p;
    productoEditandoIndex = index;
    document.getElementById('cantidadInput').value = p.cantidad;
    abrirModal(p.id, p.nombre, p.categoria, p.unidad, p.precio, true);
}

// Actualizar cantidad
function actualizarCantidad() {
    const nuevaCantidad = parseFloat(document.getElementById('cantidadInput').value);
    if (nuevaCantidad <= 0) return alert("Cantidad inválida");

    const p = productosPedido[productoEditandoIndex];
    p.cantidad = nuevaCantidad;
    p.subtotal = p.precio * nuevaCantidad;

    actualizarTablaPedido();
    cerrarModal();
}

// Eliminar producto
function eliminarProducto(index) {
    if (confirm("¿Seguro que deseas eliminar este producto del pedido?")) {
        productosPedido.splice(index, 1);
        actualizarTablaPedido();
    }
}

// Actualizar tabla de productos
function actualizarTablaPedido() {
    const cuerpo = document.querySelector('#tablaPedido tbody');
    cuerpo.innerHTML = "";

    if (productosPedido.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="6" style="text-align:center;">No hay productos seleccionados.</td></tr>`;
        return;
    }

    productosPedido.forEach((p, index) => {
        const fila = `
            <tr>
                <td>${p.nombre}</td>
                <td>${p.categoria}</td>
                <td>${p.unidad}</td>
                <td>${p.cantidad}</td>
                <td>$${p.subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn-editar" onclick="editarProducto(${index})">Editar</button>
                    <button class="btn-eliminar" onclick="eliminarProducto(${index})">Eliminar</button>
                </td>
            </tr>`;
        cuerpo.innerHTML += fila;
    });

    localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
}

// Enviar pedido
document.getElementById('btnHacerPedido').addEventListener('click', () => {
    const fechaSolicitud = document.getElementById('fechaSolicitud').value;
    const fechaEntrega = document.getElementById('fechaEntrega').value;

    if (!fechaSolicitud || !fechaEntrega) {
        alert("Por favor selecciona las fechas antes de hacer el pedido.");
        return;
    }

    if (productosPedido.length === 0) {
        alert("Agrega al menos un producto al pedido antes de continuar.");
        return;
    }

    localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
    localStorage.setItem('fechaSolicitud', fechaSolicitud);
    localStorage.setItem('fechaEntrega', fechaEntrega);

    window.location.href = "{{ route('dashboard.pedidos.previsualizar') }}";
});

// 🔄 Recuperar datos guardados si se regresa desde previsualización
document.addEventListener('DOMContentLoaded', () => {
    const productosGuardados = JSON.parse(localStorage.getItem('pedidoActual')) || [];
    const fechaSolicitudGuardada = localStorage.getItem('fechaSolicitud');
    const fechaEntregaGuardada = localStorage.getItem('fechaEntrega');

    // Si existen productos previos, recargarlos
    if (productosGuardados.length > 0) {
        productosGuardados.forEach(p => productosPedido.push(p));
        actualizarTablaPedido();
    }

    // Si existen fechas previas, asignarlas
    if (fechaSolicitudGuardada) {
        document.getElementById('fechaSolicitud').value = fechaSolicitudGuardada;
    }
    if (fechaEntregaGuardada) {
        document.getElementById('fechaEntrega').value = fechaEntregaGuardada;
    }
});

function irMenuPrincipal() {
    // Limpiar toda la información del pedido
    localStorage.removeItem('pedidoActual');
    localStorage.removeItem('fechaSolicitud');
    localStorage.removeItem('fechaEntrega');
    // Redirigir al menú principal
    window.location.href = "{{ route('dashboard.admin') }}";
};



</script>
@endsection
