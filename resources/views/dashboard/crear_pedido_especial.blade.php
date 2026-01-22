@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido Especial')

@section('contenido')
@php
    // viene desde el controller
    $esAdmin = $esAdmin ?? (auth()->user()->role === 'admin');
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
    </div>

    {{-- =====================================================
                FECHAS
    ====================================================== --}}
    <div class="filtros" style="grid-template-columns:1fr 1fr;">
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
                PRODUCTOS DISPONIBLES
    ====================================================== --}}
    <h3 class="titulo-seccion">Productos disponibles</h3>

    @if($esAdmin)
        {{-- ✅ ADMIN: puede ver catálogo (paginado + filtros) --}}
        <form method="GET" action="{{ route('dashboard.pedidos.especial.crear') }}" class="filtros">

            <div class="campo">
                <label>Proveedor:</label>
                <select name="proveedor_id" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                            {{ $prov->nombre }}
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

            <div class="campo">
                <label>Buscar por nombre:</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Ej. Leche, harina...">
            </div>

            <div class="campo" style="display:flex; gap:10px; align-items:flex-end;">
                <button type="submit" class="btn" style="width:auto;">Buscar</button>

                <a href="{{ route('dashboard.pedidos.especial.crear') }}"
                   class="btn-cancelar"
                   style="padding:8px 13px; border-radius:8px; text-decoration:none; color:white;">
                    Limpiar
                </a>
            </div>
        </form>

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

            <div class="paginacion" style="margin-top:12px;">
                {{ $productos->links('vendor.pagination.dashboard') }}
            </div>
        </div>

    @else
        {{-- ✅ NO-ADMIN: NO catálogo. Solo resultados por búsqueda (AJAX + paginado) --}}
        <div class="filtros">
            <div class="campo">
                <label>Proveedor:</label>
                <select id="filtroProveedorNoAdmin">
                    <option value="">Todos</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label>Categoría:</label>
                <select id="filtroCategoriaNoAdmin">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label>Buscar producto:</label>
                <input type="text" id="buscadorProductos" placeholder="Escribe al menos 2 letras...">
            </div>
        </div>

        <small style="display:block; opacity:.75; margin-top:8px;">
            No se muestra el catálogo. Solo verás productos al buscar.
        </small>

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
                <tbody id="tbodyResultadosBusqueda">
                    <tr>
                        <td colspan="5" style="padding:14px; text-align:center;">
                            Escribe para buscar productos.
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- ✅ paginación de resultados (AJAX) --}}
            <div id="paginacionAjax" class="paginacion" style="margin-top:12px; display:none;"></div>
        </div>
    @endif

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
                MODAL PRODUCTO
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

<style>
/* (tus estilos igual, solo agrego paginación y un par de ajustes) */
.contenedor{ background:#fceede; padding:25px 35px; border-radius:12px; max-width:1100px; margin:auto; }
.titulo-seccion{ margin-top:25px; margin-bottom:10px; font-size:20px; font-weight:700; }

.filtros{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; margin-bottom:22px; }
.campo label{ display:block; font-weight:600; margin-bottom:4px; }
input, select{ width:100%; padding:7px; border-radius:6px; border:1px solid #ccc; }

.tabla-contenedor{ margin-top:10px; }
.tabla{ width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; }
.tabla th{ background:#b22b27; color:white; padding:12px; text-align:center; }
.tabla td{ padding:10px; text-align:center; border-bottom:1px solid #eee; }
.tabla tr:hover{ background:#f5d6d6; }

.btn-menu,.btn,.btn-seleccionar,.btn-confirmar{ background:#b22b27; color:white; border:none; padding:8px 13px; border-radius:8px; cursor:pointer; }
.btn-menu{ background:#999; }
.btn-menu:hover{ background:#777; }
.btn:hover{ background:#941c1c; }
.btn-cancelar{ background:#777; }

.acciones-final{ margin-top:22px; padding-top:12px; display:flex; justify-content:flex-start; }

.modal{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); align-items:center; justify-content:center; z-index:5000; }
.modal-contenido{ background:white; width:380px; padding:20px; border-radius:12px; text-align:center; }
.modal-acciones{ display:flex; justify-content:center; gap:10px; margin-top:15px; }

/* PDFs */
.pdf-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-top:15px; }
.pdf-card { background:#fff; border-radius:10px; padding:15px 18px; border:1px solid #d8d8d8; box-shadow:0 1px 3px rgba(0,0,0,0.08); display:flex; flex-direction:column; gap:10px; }
.pdf-card input[type="file"] { border:1px solid #ccc; padding:8px; border-radius:6px; background:#fafafa; }
.btn-ver { background:#b22b27; color:#fff; padding:8px 14px; border-radius:6px; border:none; cursor:pointer; width:100%; }
.btn-ver:hover { background:#8d1f1f; }

/* ✅ PAGINACIÓN: arregla SVG gigantes */
.paginacion nav { display:flex; justify-content:center; }
.paginacion svg { width:18px !important; height:18px !important; }
.paginacion a, .paginacion span {
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    line-height:1 !important;
    padding:6px 10px !important;
    border-radius:8px;
}
.paginacion .hidden { display:none !important; }

/* ✅ Paginación AJAX simple */
#paginacionAjax{
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
}
#paginacionAjax button{
    background:#fff;
    border:1px solid #ccc;
    padding:6px 10px;
    border-radius:8px;
    cursor:pointer;
}
#paginacionAjax button.activo{
    background:#b22b27;
    color:#fff;
    border-color:#b22b27;
}
</style>

<script>
let productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
let productoSeleccionado = null;
let productoEditandoIndex = null;

const esAdmin = @json($esAdmin);

// ✅ ADMIN: puedes usar items de página para modal si quieres (pero admin abre desde tabla actual)
const productosDataAdmin = @json($productos ? $productos->items() : []);

// ✅ NO-ADMIN: cache de resultados de búsqueda para abrir modal
let resultadosBusqueda = []; // se llena con AJAX

document.addEventListener("DOMContentLoaded", () => {

    fechaSolicitud.value = new Date().toISOString().split("T")[0];
    localStorage.setItem("fechaSolicitud", fechaSolicitud.value);

    if (localStorage.getItem("fechaEntrega")) {
        fechaEntrega.value = localStorage.getItem("fechaEntrega");
    }

    restaurarNombresPDF();

    productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
    actualizarTablaPedido();

    if(!esAdmin){
        const input = document.getElementById('buscadorProductos');
        const selProv = document.getElementById('filtroProveedorNoAdmin');
        const selCat  = document.getElementById('filtroCategoriaNoAdmin');

        let t = null;
        input.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => buscarAjax(1), 200);
        });

        selProv.addEventListener('change', () => buscarAjax(1));
        selCat.addEventListener('change', () => buscarAjax(1));
    }
});

/* =====================================================
   BUSCADOR AJAX (NO-ADMIN) + PAGINACIÓN RESULTADOS
===================================================== */
async function buscarAjax(page = 1){
    const tbody = document.getElementById('tbodyResultadosBusqueda');
    const pagDiv = document.getElementById('paginacionAjax');

    const q = (document.getElementById('buscadorProductos')?.value || '').trim();
    const proveedor_id = document.getElementById('filtroProveedorNoAdmin')?.value || '';
    const categoria_id = document.getElementById('filtroCategoriaNoAdmin')?.value || '';

    if(q.length < 2){
        resultadosBusqueda = [];
        pagDiv.style.display = 'none';
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="padding:14px; text-align:center;">
                    Escribe al menos <b>2 letras</b> para buscar.
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = `
        <tr><td colspan="5" style="padding:14px; text-align:center;">Buscando...</td></tr>
    `;

    const params = new URLSearchParams({ q, page });
    if(proveedor_id) params.append('proveedor_id', proveedor_id);
    if(categoria_id) params.append('categoria_id', categoria_id);

    const url = `{{ route('dashboard.pedidos.especial.buscar_productos') }}?${params.toString()}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const json = await res.json();

    resultadosBusqueda = json.data || [];

    if(resultadosBusqueda.length === 0){
        pagDiv.style.display = 'none';
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="padding:14px; text-align:center;">
                    Sin resultados.
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = resultadosBusqueda.map(p => {
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

    // paginación
    const meta = json.meta || {};
    renderPaginacionAjax(meta.current_page || 1, meta.last_page || 1);
}

function renderPaginacionAjax(current, last){
    const pagDiv = document.getElementById('paginacionAjax');
    if(!pagDiv) return;

    if(last <= 1){
        pagDiv.style.display = 'none';
        pagDiv.innerHTML = '';
        return;
    }

    pagDiv.style.display = 'flex';

    const maxBtns = 7; // visible
    let start = Math.max(1, current - Math.floor(maxBtns/2));
    let end   = Math.min(last, start + maxBtns - 1);
    start = Math.max(1, end - maxBtns + 1);

    let html = '';

    if(current > 1){
        html += `<button onclick="buscarAjax(${current-1})">«</button>`;
    }

    for(let i=start; i<=end; i++){
        html += `<button class="${i===current?'activo':''}" onclick="buscarAjax(${i})">${i}</button>`;
    }

    if(current < last){
        html += `<button onclick="buscarAjax(${current+1})">»</button>`;
    }

    pagDiv.innerHTML = html;
}

/* =====================================================
   MODAL / SELECCIÓN PRODUCTO
   - ADMIN: usa productosDataAdmin (solo página)
   - NO-ADMIN: usa resultadosBusqueda (solo resultados)
===================================================== */
function abrirModalProducto(producto_id) {

    if (!validarDatosRequeridos()) return;

    let producto = null;

    if(esAdmin){
        producto = (productosDataAdmin || []).find(p => p.id == producto_id);
        if(!producto){
            alert("Producto no encontrado en esta página. Cambia de página o ajusta filtros.");
            return;
        }
    }else{
        producto = (resultadosBusqueda || []).find(p => p.id == producto_id);
        if(!producto){
            alert("Primero busca el producto y selecciónalo desde los resultados.");
            return;
        }
    }

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

/* ===== resto de tu JS: lo dejo igual (sin cambios funcionales) ===== */

function escapeHtml(str){
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

function irMenuPrincipal() {
    limpiarPedidoEspecialStorage();
    window.location.href = "{{ route('dashboard.admin') }}";
}

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

function cerrarAdvertencia(){ modalAdvertencia.style.display = "none"; }
function cerrarModal(){ modalCantidad.style.display = "none"; }

function actualizarPrecioProveedor() {
    const datos = JSON.parse(proveedorSelect.value);
    productoSeleccionado.proveedor_id = datos.proveedor_id;
    productoSeleccionado.proveedor = datos.proveedor;
    productoSeleccionado.precio = datos.precio;
    precioProveedor.textContent = `$${datos.precio.toFixed(2)}`;
}

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
                <td>$${Number(p.precio).toFixed(2)}</td>
                <td>$${Number(p.subtotal).toFixed(2)}</td>
                <td>
                    <button class="btn" onclick="editarProducto(${i})">Editar</button>
                    <button class="btn-cancelar" onclick="eliminarProducto(${i})">Eliminar</button>
                </td>
            </tr>
        `;
    });
}

function eliminarProducto(i) {
    productosPedido.splice(i, 1);
    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
}

function editarProducto(index) {
    const p = productosPedido[index];
    productoEditandoIndex = index;

    // Para editar, abrimos modal y luego acomodamos cantidad.
    // Requiere encontrar el producto en resultados (no-admin) o en página actual (admin).
    abrirModalProducto(p.producto_id);

    cantidadInput.value = p.cantidad;

    // seleccionar proveedor correcto si existe en options
    [...proveedorSelect.options].forEach(opt => {
        const obj = JSON.parse(opt.value);
        if (obj.proveedor_id == p.proveedor_id) {
            proveedorSelect.value = opt.value;
        }
    });

    const datos = JSON.parse(proveedorSelect.value);

    productoSeleccionado.proveedor_id = datos.proveedor_id;
    productoSeleccionado.proveedor = datos.proveedor;
    productoSeleccionado.precio = datos.precio;

    precioProveedor.textContent = `$${productoSeleccionado.precio.toFixed(2)}`;

    modalTitulo.textContent = "Editar " + p.nombre;
    btnAgregarModal.style.display = "none";
    btnActualizarModal.style.display = "inline-block";
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

btnConfirmarEspecial.onclick = () => {
    if (productosPedido.length === 0) {
        alert("Agrega productos antes de continuar");
        return;
    }
    localStorage.setItem("fechaEntrega", fechaEntrega.value);
    window.location.href = "{{ route('dashboard.pedidos.especial.previsualizar') }}";
};

/* PDFs */
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

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

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
