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
            <label for="pdfSolicitud"><strong>📄 Solicitud del cliente</strong></label>
            <input type="file" id="pdfSolicitud" accept="application/pdf">
            <button class="btn-ver">Ver PDF</button>
        </div>

        <div class="pdf-card">
            <label for="pdfCotizacion"><strong>📄 Cotización generada</strong></label>
            <input type="file" id="pdfCotizacion" accept="application/pdf">
            <button class="btn-ver">Ver PDF</button>
        </div>

        <div class="pdf-card">
            <label for="pdfAutorizacion"><strong>📄 Aceptación del cliente</strong></label>
            <input type="file" id="pdfAutorizacion" accept="application/pdf">
            <button class="btn-ver">Ver PDF</button>
        </div>

    </div>

    {{-- =====================================================
                TABLA / BUSCADOR DE PRODUCTOS
    ====================================================== --}}
    <h3 class="titulo-seccion">Productos disponibles</h3>

    {{-- ✅ BUSCADOR SOLO NO-ADMIN --}}
    <div id="bloqueBuscador" style="display:none; margin-top:10px; margin-bottom:10px;">
        <div class="buscador-wrap">
            <input type="text" id="buscadorProductos" placeholder="Buscar producto por nombre o categoría...">
            <span class="lupa">🔎</span>
        </div>

        <div class="tabla-contenedor" style="margin-top:12px;">
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
                <tbody id="tbodyResultadosBusqueda"></tbody>
            </table>
        </div>
    </div>

    {{-- ✅ TABLA COMPLETA SOLO ADMIN --}}
    <div id="bloqueTablaCompleta" class="tabla-contenedor">
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
        <button class="btn-confirmar" id="btnConfirmarEspecial">Previsualizar Pedido Especial</button>
    </div>


    <div id="modalAdvertencia" class="modal">
        <div class="modal-contenido">
            <h3 style="color:#b22b27;">⚠ Faltan datos obligatorios</h3>
            <p id="mensajeFaltantes"></p>
            <button class="btn" onclick="cerrarAdvertencia()">Aceptar</button>
        </div>
    </div>

</div>



{{-- =====================================================
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



{{-- ======================================================
                        ESTILOS
====================================================== --}}
<style>
/* CONTENEDOR PRINCIPAL */
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* SECCIONES */
.titulo-seccion{
    margin-top:25px;
    margin-bottom:10px;
    font-size:20px;
    font-weight:700;
}

/* FILTROS */
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

/* BUSCADOR */
.buscador-wrap{
    position:relative;
    max-width:520px;
}
.buscador-wrap input{
    width:100%;
    padding:10px 40px 10px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    background:#fff;
}
.buscador-wrap .lupa{
    position:absolute;
    right:12px;
    top:9px;
    opacity:.65;
    font-size:18px;
}

/* TABLAS */
.tabla-contenedor{
    margin-top:10px;
}

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

.tabla tr:hover{
    background:#f5d6d6;
}

/* BOTONES */
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

.btn:hover{
    background:#941c1c;
}

.btn-cancelar{
    background:#777;
}

/* MODALES */
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

.warning-title{
    color:#b22b27;
}

/* --- Tarjetas de PDFs mejoradas --- */
.pdf-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-top: 15px;
}

.pdf-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 15px 18px;
    border: 1px solid #d8d8d8;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.pdf-card input[type="file"] {
    border: 1px solid #ccc;
    padding: 8px;
    border-radius: 6px;
    background: #fafafa;
}

.btn-ver {
    background: #b22b27;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
    width: 100%;
    text-align: center;
}

.btn-ver:hover {
    background: #8d1f1f;
}

.text-center{ text-align:center; }
</style>




{{-- =====================================================
                SCRIPTS FUNCIONALES
====================================================== --}}
<script>
let productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
let productoSeleccionado = null;
let productoEditandoIndex = null;

const productosData = @json($productos);
const esAdmin = @json(auth()->user()->role === 'admin');

/* =====================================================
    INICIALIZACIÓN
===================================================== */
document.addEventListener("DOMContentLoaded", () => {

    // ✅ UI: admin vs no-admin
    const bloqueBuscador = document.getElementById('bloqueBuscador');
    const bloqueTablaCompleta = document.getElementById('bloqueTablaCompleta');

    if(esAdmin){
        if(bloqueTablaCompleta) bloqueTablaCompleta.style.display = 'block';
        if(bloqueBuscador) bloqueBuscador.style.display = 'none';
    }else{
        if(bloqueTablaCompleta) bloqueTablaCompleta.style.display = 'none';
        if(bloqueBuscador) bloqueBuscador.style.display = 'block';
        renderResultadosBusqueda(''); // pinta mensaje inicial
    }

    // FECHA SOLICITUD SIEMPRE NUEVA
    fechaSolicitud.value = new Date().toISOString().split("T")[0];
    localStorage.setItem("fechaSolicitud", fechaSolicitud.value);

    // RESTAURAR FECHA ENTREGA
    if (localStorage.getItem("fechaEntrega")) {
        fechaEntrega.value = localStorage.getItem("fechaEntrega");
    }

    // RESTAURAR PDFs
    restaurarNombresPDF();

    // RESTAURAR PRODUCTOS
    productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
    actualizarTablaPedido();

    // ✅ Activar buscador solo no-admin
    if(!esAdmin){
        const input = document.getElementById('buscadorProductos');
        let t = null;

        input.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => {
                renderResultadosBusqueda(input.value);
            }, 180);
        });
    }
});

/* =====================================================
    BUSCADOR FRONT (NO ADMIN)
===================================================== */
function renderResultadosBusqueda(query){
    const tbody = document.getElementById('tbodyResultadosBusqueda');
    if(!tbody) return;

    const q = (query || '').trim().toLowerCase();

    if(q.length < 2){
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center" style="padding:14px;">
                    Escribe al menos <b>2 letras</b> para buscar productos.
                </td>
            </tr>
        `;
        return;
    }

    const resultados = (productosData || []).filter(p => {
        const nombre = (p.nombre || '').toLowerCase();
        const cat = (p.categoria?.nombre || '').toLowerCase();
        return nombre.includes(q) || cat.includes(q);
    }).slice(0, 25);

    if(resultados.length === 0){
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center" style="padding:14px;">
                    Sin resultados para <b>${escapeHtml(q)}</b>.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = resultados.map(p => {
        const primero = (p.proveedores && p.proveedores.length) ? p.proveedores[0] : null;
        const precio = primero?.pivot?.precio ? Number(primero.pivot.precio) : 0;

        return `
            <tr>
                <td>${escapeHtml(p.nombre ?? '')}</td>
                <td>${escapeHtml(p.categoria?.nombre ?? 'Sin categoría')}</td>
                <td>${escapeHtml(p.unidad_medida ?? 'N/A')}</td>
                <td>$${precio.toFixed(2)}</td>
                <td>
                    <button class="btn-seleccionar" onclick="abrirModalProducto(${p.id})">
                        Seleccionar
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function escapeHtml(str){
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

/* =====================================================
      BOTÓN MENÚ PRINCIPAL (LIMPIA TODO)
===================================================== */
function irMenuPrincipal() {
    limpiarPedidoEspecialStorage();  
    window.location.href = "{{ route('dashboard.admin') }}";
}

/* =====================================================
      VALIDACIÓN DE DATOS OBLIGATORIOS
===================================================== */
function validarDatosRequeridos() {

    let faltantes = [];

    if (!fechaEntrega.value) faltantes.push("Seleccionar la fecha de entrega");
    if (!localStorage.getItem("pdf_solicitud")) faltantes.push("Cargar PDF de solicitud del cliente");
    if (!localStorage.getItem("pdf_cotizacion")) faltantes.push("Cargar PDF de cotización");
    if (!localStorage.getItem("pdf_autorizacion")) faltantes.push("Cargar PDF de aceptación del cliente");

    if (faltantes.length > 0) {
        mensajeFaltantes.innerHTML =
            "Debes completar lo siguiente:<br><br>• " + faltantes.join("<br>• ");
        modalAdvertencia.style.display = "flex";
        return false;
    }

    return true;
}

function cerrarAdvertencia() {
    modalAdvertencia.style.display = "none";
}

/* =====================================================
      SELECCIONAR PRODUCTO (MODAL)
===================================================== */
function abrirModalProducto(producto_id) {

    if (!validarDatosRequeridos()) return;

    const producto = productosData.find(p => p.id == producto_id);
    if (!producto) return;

    const proveedores = producto.proveedores || [];
    if (proveedores.length === 0) return alert("No tiene proveedores");

    proveedorSelect.innerHTML = "";

    proveedores.forEach(p => {
        const data = {
            producto_id: producto.id,
            proveedor_id: p.id,
            proveedor: p.nombre,
            precio: parseFloat(p.pivot.precio)
        };

        const option = document.createElement("option");
        option.value = JSON.stringify(data);
        option.textContent = `${p.nombre} — $${data.precio.toFixed(2)}`;
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

    precioProveedor.textContent = `$${productoSeleccionado.precio.toFixed(2)}`;

    productoEditandoIndex = null;
    cantidadInput.value = 1;

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

/* =====================================================
      AGREGAR PRODUCTO
===================================================== */
function agregarProducto() {

    const nuevaCantidad = parseInt(cantidadInput.value);

    let existente = productosPedido.find(p =>
        p.producto_id === productoSeleccionado.producto_id &&
        p.proveedor_id === productoSeleccionado.proveedor_id
    );

    if (existente) {
        existente.cantidad += nuevaCantidad;
        existente.subtotal = existente.cantidad * existente.precio;
    } else {
        productosPedido.push({
            ...productoSeleccionado,
            cantidad: nuevaCantidad,
            subtotal: nuevaCantidad * productoSeleccionado.precio
        });
    }

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
    cerrarModal();
}

/* =====================================================
      MOSTRAR TABLA
===================================================== */
function actualizarTablaPedido() {

    const tbody = document.querySelector("#tablaPedidoEspecial tbody");
    tbody.innerHTML = "";

    productosPedido.forEach((p, i) => {

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
            </tr>
        `;
    });
}

/* =====================================================
      ELIMINAR PRODUCTO
===================================================== */
function eliminarProducto(i) {
    productosPedido.splice(i, 1);
    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
}

/* =====================================================
      EDITAR PRODUCTO
===================================================== */
function editarProducto(index) {

    const p = productosPedido[index];
    productoEditandoIndex = index;

    const producto = productosData.find(prod => prod.id == p.producto_id);

    proveedorSelect.innerHTML = "";

    producto.proveedores.forEach(prov => {
        const data = {
            producto_id: producto.id,
            proveedor_id: prov.id,
            proveedor: prov.nombre,
            precio: parseFloat(prov.pivot.precio)
        };

        const option = document.createElement("option");
        option.value = JSON.stringify(data);
        option.textContent = `${data.proveedor} — $${data.precio.toFixed(2)}`;
        proveedorSelect.appendChild(option);
    });

    [...proveedorSelect.options].forEach(opt => {
        if (JSON.parse(opt.value).proveedor_id == p.proveedor_id) {
            proveedorSelect.value = opt.value;
        }
    });

    const datos = JSON.parse(proveedorSelect.value);

    productoSeleccionado = {
        ...p,
        proveedor_id: datos.proveedor_id,
        proveedor: datos.proveedor,
        precio: datos.precio
    };

    cantidadInput.value = p.cantidad;
    precioProveedor.textContent = `$${productoSeleccionado.precio.toFixed(2)}`;

    modalTitulo.textContent = "Editar " + p.nombre;
    btnAgregarModal.style.display = "none";
    btnActualizarModal.style.display = "inline-block";

    modalCantidad.style.display = "flex";
}

function actualizarCantidad() {

    const nuevaCantidad = parseInt(cantidadInput.value);
    const datos = JSON.parse(proveedorSelect.value);

    const p = productosPedido[productoEditandoIndex];

    p.cantidad = nuevaCantidad;
    p.proveedor_id = datos.proveedor_id;
    p.proveedor = datos.proveedor;
    p.precio = datos.precio;
    p.subtotal = nuevaCantidad * datos.precio;

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));

    actualizarTablaPedido();
    cerrarModal();
}

/* =====================================================
      BOTÓN FINAL: PREVISUALIZAR
===================================================== */
btnConfirmarEspecial.onclick = () => {

    if (productosPedido.length === 0) {
        alert("Agrega productos antes de continuar");
        return;
    }

    localStorage.setItem("fechaEntrega", fechaEntrega.value);

    window.location.href = "{{ route('dashboard.pedidos.especial.previsualizar') }}";
};

/* =====================================================
      PDFs EN BASE64
===================================================== */
function guardarPDF(inputId, keyBase, keyNombre, labelSelector) {

    document.getElementById(inputId).addEventListener("change", async e => {

        const file = e.target.files[0];
        if (!file) return;

        const base64 = await fileToBase64(file);

        localStorage.setItem(keyBase, base64);
        localStorage.setItem(keyNombre, file.name);

        document.querySelector(labelSelector).innerText =
            document.querySelector(labelSelector).innerText.split("—")[0] +
            ` — ${file.name}`;

        alert("PDF cargado correctamente");
    });
}

guardarPDF("pdfSolicitud", "pdf_solicitud", "pdf_solicitud_nombre", "label[for='pdfSolicitud']");
guardarPDF("pdfCotizacion", "pdf_cotizacion", "pdf_cotizacion_nombre", "label[for='pdfCotizacion']");
guardarPDF("pdfAutorizacion", "pdf_autorizacion", "pdf_autorizacion_nombre", "label[for='pdfAutorizacion']");

function restaurarNombresPDF() {

    if (localStorage.getItem("pdf_solicitud_nombre")) {
        document.querySelector("label[for='pdfSolicitud']").innerText =
            `📄 Solicitud del cliente — ${localStorage.getItem("pdf_solicitud_nombre")}`;
    }

    if (localStorage.getItem("pdf_cotizacion_nombre")) {
        document.querySelector("label[for='pdfCotizacion']").innerText =
            `📄 Cotización generada — ${localStorage.getItem("pdf_cotizacion_nombre")}`;
    }

    if (localStorage.getItem("pdf_autorizacion_nombre")) {
        document.querySelector("label[for='pdfAutorizacion']").innerText =
            `📄 Aceptación del cliente — ${localStorage.getItem("pdf_autorizacion_nombre")}`;
    }
}

/* =====================================================
      VER PDF EN VENTANA
===================================================== */
document.querySelectorAll(".btn-ver").forEach(btn => {

    btn.addEventListener("click", () => {

        let input = btn.previousElementSibling.id;
        let key = "";

        if (input === "pdfSolicitud") key = "pdf_solicitud";
        if (input === "pdfCotizacion") key = "pdf_cotizacion";
        if (input === "pdfAutorizacion") key = "pdf_autorizacion";

        let pdf = localStorage.getItem(key);

        if (!pdf) return alert("No se ha cargado un PDF para este campo.");

        const win = window.open("");
        win.document.write(`<iframe width="100%" height="100%" src="${pdf}"></iframe>`);
    });
});

/* =====================================================
      UTILIDAD
===================================================== */
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

/* =====================================================
      LIMPIAR COMPLETAMENTE EL PEDIDO
===================================================== */
function limpiarPedidoEspecialStorage() {

    const claves = [
        "pedidoEspecial",
        "fechaSolicitud",
        "fechaEntrega",
        "pdf_solicitud",
        "pdf_solicitud_nombre",
        "pdf_cotizacion",
        "pdf_cotizacion_nombre",
        "pdf_autorizacion",
        "pdf_autorizacion_nombre"
    ];

    claves.forEach(k => localStorage.removeItem(k));
}
</script>

@endsection
