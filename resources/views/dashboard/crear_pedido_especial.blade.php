@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido Especial')

@section('contenido')
<div class="contenedor">

    {{-- =====================================================
                BOTÓN MENÚ
    ====================================================== --}}
    <div class="acciones-superior">
        <button class="btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
    </div>

    <h3 class="titulo-seccion">Crear Pedido Especial</h3>

    {{-- =====================================================
                FECHAS
    ====================================================== --}}
    <div class="filtros">
        <div class="campo">
            <label>Fecha de solicitud:</label>
            <input type="date" id="fechaSolicitud" readonly>
        </div>

        <div class="campo">
            <label>Fecha de entrega:</label>
            <input type="date" id="fechaEntrega">
        </div>
    </div>


    {{-- =====================================================
                PDFs
    ====================================================== --}}
    <h3 class="titulo-seccion">Documentos del pedido especial (PDF)</h3>

    <div class="pdf-grid">

        <div class="pdf-card">
            <label><strong>📄 Solicitud del cliente</strong></label>
            <input type="file" id="pdfSolicitud" accept="application/pdf">
            <button class="btn-ver">Ver PDF</button>
        </div>

        <div class="pdf-card">
            <label><strong>📄 Cotización generada</strong></label>
            <input type="file" id="pdfCotizacion" accept="application/pdf">
            <button class="btn-ver">Ver PDF</button>
        </div>

        <div class="pdf-card">
            <label><strong>📄 Aceptación del cliente</strong></label>
            <input type="file" id="pdfAutorizacion" accept="application/pdf">
            <button class="btn-ver">Ver PDF</button>
        </div>

    </div>

    {{-- =====================================================
                TABLA DE PRODUCTOS DISPONIBLES
    ====================================================== --}}
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
            @foreach($productos as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                    <td>{{ $p->unidad_medida ?? 'N/A' }}</td>
                    <td>${{ number_format($p->proveedores->first()->pivot->precio ?? 0,2) }}</td>
                    <td>
                        <button class="btn-seleccionar" onclick="abrirModalProducto({{ $p->id }})">
                            Seleccionar
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- =====================================================
                TABLA DEL PEDIDO ESPECIAL
    ====================================================== --}}
    <h3 class="titulo-seccion">Productos en el pedido</h3>

    <div class="tabla-contenedor">
        <table class="tabla" id="tablaPedidoEspecial">
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

    {{-- =====================================================
                BOTÓN FINAL
    ====================================================== --}}
    <div class="acciones-final">
        <button class="btn-confirmar" id="btnConfirmarEspecial">Confirmar Pedido Especial</button>
    </div>

</div>




{{-- =====================================================
                MODALES
====================================================== --}}

{{-- MODAL AGREGAR / EDITAR --}}
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
            <button class="btn" id="btnAgregarModal">Agregar</button>
            <button class="btn" id="btnActualizarModal" style="display:none;">Actualizar</button>
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>




{{-- =====================================================
                ESTILOS
====================================================== --}}
<style>

/* Contenedor */
.contenedor {
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* Tablas */
.tabla {
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
}
.tabla th {
    background:#b22b27;
    color:white;
    padding:12px;
    text-align:center;
}
.tabla td {
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
}
.tabla tr:hover { background:#f8dede; }

.btn-seleccionar {
    background:#b22b27;
    color:white;
    padding:6px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

/* PDF CARDS */
.pdf-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:20px;
}

.pdf-card {
    background:white;
    border-radius:10px;
    padding:12px;
    border:1px solid #ddd;
}

.pdf-card input { width:100%; margin-bottom:8px; }

.btn-ver {
    background:#b22b27;
    color:white;
    padding:6px 12px;
    border-radius:6px;
    border:none;
}

/* MODALES */
.modal {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.45);
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.modal-contenido {
    background:white;
    padding:25px;
    width:400px;
    border-radius:12px;
    animation:pop 0.25s ease-out;
}

@keyframes pop {
    from { transform:scale(.80); opacity:0; }
    to { transform:scale(1); opacity:1; }
}

.modal-acciones {
    display:flex;
    justify-content:center;
    gap:12px;
    margin-top:15px;
}

.btn {
    background:#b22b27;
    color:white;
    padding:8px 14px;
    border:none;
    border-radius:6px;
}

.btn-cancelar {
    background:#666;
}

.btn-confirmar {
    margin-top:25px;
    background:#b22b27;
    color:white;
    padding:10px 20px;
    border:none;
    border-radius:8px;
    font-size:16px;
}

</style>




{{-- =====================================================
                SCRIPTS FUNCIONALES
====================================================== --}}
<script>

let productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
let productoSeleccionado = null;
let productoEditandoIndex = null;

const productosData = @json($productos);

/* ----------------- FECHA AUTOMÁTICA ----------------- */
document.addEventListener("DOMContentLoaded", () => {
    fechaSolicitud.value = new Date().toISOString().split("T")[0];
    actualizarTablaPedido();
});


/* ------------------- MODAL PRODUCTO ------------------- */
function abrirModalProducto(producto_id) {

    const producto = productosData.find(p => p.id == producto_id);
    const proveedores = producto.proveedores;

    proveedorSelect.innerHTML = "";

    proveedores.forEach(p => {
        const option = document.createElement("option");
        option.value = JSON.stringify({
            producto_id: producto.id,
            proveedor_id: p.id,
            proveedor: p.nombre,
            precio: parseFloat(p.pivot.precio)
        });
        option.textContent = `${p.nombre} — $${parseFloat(p.pivot.precio).toFixed(2)}`;
        proveedorSelect.appendChild(option);
    });

    const datos = JSON.parse(proveedorSelect.value);

    productoSeleccionado = {
        nombre: producto.nombre,
        categoria: producto.categoria?.nombre ?? "",
        unidad: producto.unidad_medida ?? "",
        producto_id: producto.id,
        proveedor_id: datos.proveedor_id,
        proveedor: datos.proveedor,
        precio: datos.precio
    };

    precioProveedor.textContent = `$${datos.precio.toFixed(2)}`;

    modalTitulo.textContent = "Agregar " + producto.nombre;
    btnAgregarModal.style.display = "inline-block";
    btnActualizarModal.style.display = "none";

    modalCantidad.style.display = "flex";
}


function cerrarModal() {
    modalCantidad.style.display = "none";
}

function actualizarPrecioProveedor() {
    const datos = JSON.parse(proveedorSelect.value);
    productoSeleccionado.proveedor_id = datos.proveedor_id;
    productoSeleccionado.proveedor = datos.proveedor;
    productoSeleccionado.precio = datos.precio;
    precioProveedor.textContent = `$${datos.precio.toFixed(2)}`;
}



function agregarProducto() {

    productosPedido.push({
        ...productoSeleccionado,
        cantidad: parseInt(cantidadInput.value),
        subtotal: parseInt(cantidadInput.value) * productoSeleccionado.precio
    });

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));

    actualizarTablaPedido();
    cerrarModal();
}



/* ------------------- MOSTRAR TABLA ------------------- */
function actualizarTablaPedido() {
    const tbody = document.querySelector("#tablaPedidoEspecial tbody");
    tbody.innerHTML = "";

    productosPedido.forEach((p,i) => {
        tbody.innerHTML += `
        <tr>
            <td>${p.nombre}</td>
            <td>${p.categoria}</td>
            <td>${p.unidad}</td>
            <td>${p.cantidad}</td>
            <td>${p.proveedor}</td>
            <td>$${p.precio.toFixed(2)}</td>
            <td>$${p.subtotal.toFixed(2)}</td>
            <td>
                <button class="btn" onclick="editarProducto(${i})">Editar</button>
                <button class="btn-cancelar" onclick="eliminarProducto(${i})">Eliminar</button>
            </td>
        </tr>`;
    });
}

function eliminarProducto(i) {
    productosPedido.splice(i,1);
    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
}



/* ------------------------ FINAL ------------------------ */
document.getElementById("btnConfirmarEspecial").onclick = () => {

    if (productosPedido.length === 0) {
        alert("Agrega productos primero.");
        return;
    }

    alert("Pedido especial listo para enviar. (Aquí conectamos con la siguiente vista)");
};


function irMenuPrincipal() {
    localStorage.removeItem("pedidoEspecial");
    window.location.href = "{{ route('dashboard.admin') }}";
}

</script>

@endsection
