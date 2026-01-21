@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido')

@section('contenido')

@php
    $esAdmin = auth()->user()->role === 'admin';
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
    </div>

    {{-- ============================
            FILTROS SUPERIORES (GET)
    ============================= --}}
    <form method="GET" action="{{ route('dashboard.pedidos.solicitar') }}" class="filtros">

        <div class="campo">
            <label>Fecha de solicitud:</label>
            <input type="date" id="fechaSolicitud" readonly>
        </div>

        <div class="campo">
            <label>Fecha de entrega:</label>
            <input type="date" id="fechaEntrega">
        </div>

        <div class="campo">
            <label>Proveedor:</label>
            <select name="proveedor_id" id="proveedorFiltro" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo">
            <label>Categoría:</label>
            <select name="categoria_id" onchange="this.form.submit()">
                <option value="">Todas</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo" style="grid-column: span 2;">
            <label>Buscar por nombre:</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Ej. Leche, harina, pollo...">
        </div>

        <div class="campo" style="display:flex; gap:10px; align-items:flex-end;">
            <button type="submit" class="btn" style="width:auto;">Buscar</button>

            <a href="{{ route('dashboard.pedidos.solicitar') }}"
               class="btn-cancelar"
               style="padding:8px 13px; border-radius:8px; text-decoration:none; color:white;">
                Limpiar
            </a>
        </div>
    </form>

    {{-- ============================
            PRODUCTOS DISPONIBLES
    ============================= --}}
    <h3 class="titulo-seccion">Productos disponibles</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th>Precio</th>
                <th>Seleccionar</th>
            </tr>
            </thead>
            <tbody id="tbodyProductosDisponibles">
            @forelse($productos as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                    <td>{{ $p->unidad_medida ?? 'N/A' }}</td>
                    <td>${{ number_format($p->proveedores->first()->pivot->precio ?? 0, 2) }}</td>
                    <td>
                        <button class="btn-seleccionar"
                                onclick="abrirModalProducto({{ $p->id }})">
                            Seleccionar
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding:14px; text-align:center;">
                        No hay productos con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        {{-- ✅ Paginación (10 por página) --}}
        <div class="paginacion" style="margin-top:12px;">
            {{ $productos->links() }}
        </div>

    </div>

    {{-- ============================
            TABLA DEL PEDIDO
    ============================= --}}
    <h3 class="titulo-seccion">Productos en el pedido</h3>

    <div class="tabla-contenedor">
        <table class="tabla" id="tablaPedido">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th>Cantidad</th>
                <th>Proveedor</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="acciones-final">
        <button class="btn-confirmar" id="btnHacerPedidoUI">Previsualizar pedido</button>
    </div>

</div>

{{-- ======================================================
                        MODALES
====================================================== --}}

<div id="modalCantidad" class="modal">
    <div class="modal-contenido">
        <h3 id="modalTitulo"></h3>

        <div class="grupo">
            <label>Proveedor</label>
            <select id="proveedorSelect" onchange="actualizarPrecioProveedor()"></select>
        </div>

        <div class="grupo">
            <label>Cantidad</label>
            <input type="number" id="cantidadInput" min="1" value="1">
        </div>

        <p class="precio-linea">
            <strong>Precio unitario:</strong>
            <span id="precioProveedor">$0.00</span>
        </p>

        <div class="modal-acciones">
            <button class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarCantidad()">Actualizar</button>
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<div id="modalAdvertencia" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">⚠️ No se puede continuar</h3>
        <p>Selecciona ambas fechas antes de agregar productos.</p>
        <div class="modal-acciones">
            <button class="btn" onclick="cerrarModalAdvertencia()">Aceptar</button>
        </div>
    </div>
</div>

<div id="modalEliminar" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">Eliminar producto</h3>
        <p>¿Seguro que deseas eliminarlo?</p>
        <div class="modal-acciones">
            <button class="btn" id="btnEliminarSi">Eliminar</button>
            <button class="btn-cancelar" id="btnEliminarNo">Cancelar</button>
        </div>
    </div>
</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}
.titulo-seccion{
    margin-top:25px;
    margin-bottom:10px;
    font-size:20px;
    font-weight:700;
}
.filtros{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:18px;
    margin-bottom:22px;
}
.campo label{
    display:block;
    font-weight:600;
    margin-bottom:4px;
}
input, select{
    width:100%;
    padding:7px;
    border-radius:6px;
    border:1px solid #ccc;
}
.tabla-contenedor{ margin-top:10px; }
.tabla{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
}
.tabla th{
    background:#b22b27;
    color:white;
    padding:12px;
    text-align:center;
}
.tabla td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
}
.tabla tr:hover{ background:#f5d6d6; }

.btn-menu,
.btn,
.btn-seleccionar,
.btn-confirmar{
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 13px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu{ background:#999; }
.btn-menu:hover{ background:#777; }
.btn:hover{ background:#941c1c; }
.btn-cancelar{ background:#777; }
.acciones-final{
    margin-top:22px;
    padding-top:12px;
    display:flex;
    justify-content:flex-start;
}
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    align-items:center;
    justify-content:center;
    z-index:5000;
}
.modal-contenido{
    background:white;
    width:380px;
    padding:20px;
    border-radius:12px;
    text-align:center;
}
.modal-acciones{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:15px;
}
.warning-title{ color:#b22b27; }

/* ===== PAGINACIÓN (ARREGLA ICONOS GIGANTES) ===== */
.paginacion nav {
    display: flex;
    justify-content: center;
}

.paginacion svg {
    width: 18px !important;
    height: 18px !important;
}

.paginacion a,
.paginacion span {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    line-height: 1 !important;
    padding: 6px 10px !important;
    border-radius: 8px;
}

.paginacion .hidden {
    display: none !important; /* quita el bloque "Showing x to y..." si aparece */
}

</style>

<script>
let productosPedido = JSON.parse(localStorage.getItem('pedidoActual') || '[]');
let productoSeleccionado = null;
let productoEditandoIndex = null;

// ✅ solo los items (10) para no mandar el paginator completo
const productosData = @json($productos->items());
const esAdmin = @json($esAdmin);

document.addEventListener('DOMContentLoaded', () => {
    fechaSolicitud.value = localStorage.getItem('fechaSolicitud') || new Date().toISOString().split("T")[0];
    fechaEntrega.value = localStorage.getItem('fechaEntrega') || fechaSolicitud.value;

    fechaEntrega.addEventListener('change', () =>
        localStorage.setItem('fechaEntrega', fechaEntrega.value)
    );

    actualizarTablaPedido();
});

function abrirModalProducto(producto_id) {
    const producto = (productosData || []).find(p => p.id == producto_id);

    if (!producto) {
        alert("Error: Producto no encontrado.");
        return;
    }

    const proveedores = producto.proveedores || [];
    if (proveedores.length === 0) {
        alert("Este producto no tiene proveedores asignados");
        return;
    }

    proveedorSelect.innerHTML = "";
    proveedores.forEach(prov => {
        const obj = {
            producto_proveedor_id: prov.pivot.id,
            producto_id: producto.id,
            proveedor_id: prov.id,
            proveedor: prov.nombre,
            precio: parseFloat(prov.pivot.precio)
        };

        const opt = document.createElement("option");
        opt.value = JSON.stringify(obj);
        opt.textContent = `${prov.nombre} — $${obj.precio.toFixed(2)}`;
        proveedorSelect.appendChild(opt);
    });

    const data = JSON.parse(proveedorSelect.value);

    productoSeleccionado = {
        producto_proveedor_id: data.producto_proveedor_id,
        producto_id: data.producto_id,
        proveedor_id: data.proveedor_id,
        proveedor: data.proveedor,
        precio: data.precio,
        nombre: producto.nombre,
        categoria: producto.categoria?.nombre ?? "",
        unidad: producto.unidad_medida ?? ""
    };

    precioProveedor.textContent = `$${productoSeleccionado.precio.toFixed(2)}`;

    modalTitulo.textContent = `Agregar ${producto.nombre}`;
    btnAgregarModal.style.display = "inline-block";
    btnActualizarModal.style.display = "none";
    cantidadInput.value = 1;

    modalCantidad.style.display = "flex";
}

function actualizarPrecioProveedor() {
    const data = JSON.parse(proveedorSelect.value);

    productoSeleccionado.producto_proveedor_id = data.producto_proveedor_id;
    productoSeleccionado.producto_id           = data.producto_id;
    productoSeleccionado.proveedor_id          = data.proveedor_id;
    productoSeleccionado.proveedor             = data.proveedor;
    productoSeleccionado.precio                = data.precio;

    precioProveedor.textContent = `$${data.precio.toFixed(2)}`;
}

function cerrarModal(){
    modalCantidad.style.display = "none";
}

function cerrarModalAdvertencia(){
    modalAdvertencia.style.display = "none";
}

function agregarProducto(){
    const cant = parseFloat(cantidadInput.value);

    productosPedido.push({
        ...productoSeleccionado,
        cantidad: cant,
        precio: parseFloat(productoSeleccionado.precio),
        subtotal: cant * parseFloat(productoSeleccionado.precio)
    });

    localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
    actualizarTablaPedido();
    cerrarModal();
}

function actualizarCantidad(){
    const nuevaCantidad = parseFloat(cantidadInput.value);
    if (nuevaCantidad <= 0) return;

    let p = productosPedido[productoEditandoIndex];
    const prov = JSON.parse(proveedorSelect.value);

    p.cantidad = nuevaCantidad;
    p.producto_proveedor_id = prov.producto_proveedor_id;
    p.proveedor = prov.proveedor;
    p.precio = parseFloat(prov.precio);
    p.subtotal = nuevaCantidad * p.precio;

    localStorage.setItem("pedidoActual", JSON.stringify(productosPedido));

    actualizarTablaPedido();
    cerrarModal();
}

function actualizarTablaPedido(){
    const tbody = document.querySelector('#tablaPedido tbody');
    tbody.innerHTML = "";

    productosPedido.forEach((p, i) => {
        const precio = parseFloat(p.precio);
        const subtotal = parseFloat(p.subtotal);

        tbody.innerHTML += `
            <tr>
                <td>${p.nombre}</td>
                <td>${p.categoria}</td>
                <td>${p.unidad}</td>
                <td>${p.cantidad}</td>
                <td>${p.proveedor}</td>
                <td>$${precio.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn" onclick="editarProducto(${i})">Editar</button>
                    <button class="btn-cancelar" onclick="eliminarProducto(${i})">Eliminar</button>
                </td>
            </tr>
        `;
    });
}

function eliminarProducto(i){
    productosPedido.splice(i,1);
    localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
    actualizarTablaPedido();
}

btnHacerPedidoUI.onclick = () => {
    if(productosPedido.length === 0){
        modalAdvertencia.style.display = "flex";
        return;
    }

    localStorage.setItem('fechaSolicitud', fechaSolicitud.value);
    localStorage.setItem('fechaEntrega', fechaEntrega.value);

    window.location.href = "{{ route('dashboard.pedidos.previsualizar') }}";
};

function irMenuPrincipal(){
    localStorage.clear();
    window.location.href = "{{ route('dashboard.admin') }}";
}

function editarProducto(i){
    const p = productosPedido[i];
    productoEditandoIndex = i;

    abrirModalProducto(p.producto_id);

    cantidadInput.value = p.cantidad;

    [...proveedorSelect.options].forEach(op => {
        const obj = JSON.parse(op.value);
        if (obj.producto_proveedor_id == p.producto_proveedor_id) {
            proveedorSelect.value = op.value;
        }
    });

    btnAgregarModal.style.display = "none";
    btnActualizarModal.style.display = "inline-block";

    modalTitulo.textContent = `Editar ${p.nombre}`;
}
</script>

@endsection
