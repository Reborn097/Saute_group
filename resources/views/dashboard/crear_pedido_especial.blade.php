@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido Especial')

@section('contenido')
@php
    $esAdmin = $esAdmin ?? (auth()->user()->role === 'admin');
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button type="button" class="btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
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

    @if($esAdmin)
        <div class="filtros" style="grid-template-columns:1fr;">
            <div class="campo">
                <label>Unidad operativa:</label>
                <select id="unidadOperativaSelect">
                    <option value="">Selecciona una unidad...</option>
                    @foreach(($unidadesOperativas ?? collect()) as $u)
                        <option value="{{ $u->id }}">{{ $u->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    {{-- =====================================================
                PDFs
    ====================================================== --}}
    <h3 class="titulo-seccion">Documentos del pedido especial (PDF)</h3>

    <div class="pdf-grid">
        <div class="pdf-card">
            <label for="pdfSolicitud" class="pdf-label"><strong>📄 Solicitud del cliente</strong></label>
            <input type="file" id="pdfSolicitud" accept="application/pdf">
            <button type="button" class="btn-ver" data-pdf="pdf_solicitud">Ver PDF</button>
        </div>

        <div class="pdf-card">
            <label for="pdfCotizacion" class="pdf-label"><strong>📄 Cotización generada</strong></label>
            <input type="file" id="pdfCotizacion" accept="application/pdf">
            <button type="button" class="btn-ver" data-pdf="pdf_cotizacion">Ver PDF</button>
        </div>

        <div class="pdf-card">
            <label for="pdfAutorizacion" class="pdf-label"><strong>📄 Aceptación del cliente</strong></label>
            <input type="file" id="pdfAutorizacion" accept="application/pdf">
            <button type="button" class="btn-ver" data-pdf="pdf_autorizacion">Ver PDF</button>
        </div>
    </div>

    {{-- =====================================================
                PRODUCTOS DISPONIBLES
    ====================================================== --}}
    <h3 class="titulo-seccion">Productos disponibles</h3>

    @if($esAdmin)
        {{-- ✅ ADMIN: catálogo (paginado + filtros) --}}
        <form method="GET" action="{{ route('dashboard.pedidos.especial.crear') }}" class="filtros" id="formFiltrosAdmin">

            <div class="campo">
                <label>Proveedor:</label>
                <select name="proveedor_id" id="proveedorFiltroAdmin">
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
                <select name="categoria_id" id="categoriaFiltroAdmin">
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
                <input type="text" name="q" id="qAdmin" value="{{ request('q') }}" placeholder="Ej. Leche, harina...">
            </div>

            {{-- ✅ (Opcional) botón buscar: lo dejamos por si quieres, pero ya NO es necesario --}}
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
                    @php
                        $primero = $p->proveedores->first();
                        $precioPrimero = $primero ? (float)$primero->pivot->precio : null;
                    @endphp
                    <tr>
                        <td>{{ $p->nombre }}</td>
                        <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                        <td>{{ $p->unidad_medida ?? 'N/A' }}</td>
                        <td>
                            @if($precioPrimero !== null)
                                ${{ number_format($precioPrimero,2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn-seleccionar" onclick="abrirModalProducto({{ $p->id }})">
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
        {{-- ✅ NO-ADMIN: solo búsqueda AJAX --}}
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
        <button type="button" class="btn-confirmar" id="btnConfirmarEspecial">Previsualizar Pedido Especial</button>
    </div>

    <div id="modalAdvertencia" class="modal">
        <div class="modal-contenido">
            <h3 style="color:#b22b27;">⚠ Faltan datos obligatorios</h3>
            <p id="mensajeFaltantes"></p>
            <button type="button" class="btn" onclick="cerrarAdvertencia()">Aceptar</button>
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
            <input type="number" id="cantidadInput" min="1" step="1" value="1">
        </div>

        <p class="precio-linea">
            <strong>Precio unitario:</strong>
            <span id="precioProveedor">$0.00</span>
        </p>

        <div class="modal-acciones">
            <button type="button" class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button type="button" class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarCantidad()">Actualizar</button>
            <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<style>
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
.btn-cancelar{ background:#777; color:#fff; border:none; padding:8px 13px; border-radius:8px; cursor:pointer; }

.acciones-final{ margin-top:22px; padding-top:12px; display:flex; justify-content:flex-start; }

.modal{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); align-items:center; justify-content:center; z-index:5000; }
.modal-contenido{ background:white; width:380px; padding:20px; border-radius:12px; text-align:center; }
.modal-acciones{ display:flex; justify-content:center; gap:10px; margin-top:15px; }

.pdf-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-top:15px; }
.pdf-card { background:#fff; border-radius:10px; padding:15px 18px; border:1px solid #d8d8d8; box-shadow:0 1px 3px rgba(0,0,0,0.08); display:flex; flex-direction:column; gap:10px; }
.pdf-card input[type="file"] { border:1px solid #ccc; padding:8px; border-radius:6px; background:#fafafa; }
.btn-ver { background:#b22b27; color:#fff; padding:8px 14px; border-radius:6px; border:none; cursor:pointer; width:100%; }
.btn-ver:hover { background:#8d1f1f; }

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
// ===== Refs
const fechaSolicitud = document.getElementById('fechaSolicitud');
const fechaEntrega   = document.getElementById('fechaEntrega');

const modalCantidad     = document.getElementById('modalCantidad');
const modalAdvertencia  = document.getElementById('modalAdvertencia');
const mensajeFaltantes  = document.getElementById('mensajeFaltantes');

const modalTitulo       = document.getElementById('modalTitulo');
const proveedorSelect   = document.getElementById('proveedorSelect');
const cantidadInput     = document.getElementById('cantidadInput');
const precioProveedor   = document.getElementById('precioProveedor');

const btnAgregarModal    = document.getElementById('btnAgregarModal');
const btnActualizarModal = document.getElementById('btnActualizarModal');

const btnConfirmarEspecial = document.getElementById('btnConfirmarEspecial');

const esAdmin = @json($esAdmin);
const unidadSelect = document.getElementById('unidadOperativaSelect');

const productosDataAdmin = @json($productos ? $productos->items() : []);

let resultadosBusqueda = [];

let productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
let productoSeleccionado = null;
let productoEditandoIndex = null;

// ====== ADMIN: auto-submit filtros (como tu vista normal)
const formFiltrosAdmin = document.getElementById('formFiltrosAdmin');
const proveedorFiltroAdmin = document.getElementById('proveedorFiltroAdmin');
const categoriaFiltroAdmin = document.getElementById('categoriaFiltroAdmin');
// input qAdmin se deja manual (enter o botón), pero si quieres también auto, te lo pongo
const qAdmin = document.getElementById('qAdmin');

function escapeHtml(str){
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

function numInt(v, def = 0){
    const n = parseInt(v, 10);
    return Number.isFinite(n) ? n : def;
}

function money(n){
    const val = Number(n);
    if(!Number.isFinite(val)) return '$0.00';
    return '$' + val.toFixed(2);
}

// ===== Fechas
function persistirFechas(){
    localStorage.setItem('fechaSolicitud', fechaSolicitud.value || '');
    localStorage.setItem('fechaEntrega', fechaEntrega.value || '');
}

function restaurarFechas(){
    const hoy = new Date().toISOString().split("T")[0];

    const fs = localStorage.getItem('fechaSolicitud') || hoy;
    fechaSolicitud.value = fs;

    const fe = localStorage.getItem('fechaEntrega') || fs;
    fechaEntrega.value = fe;

    persistirFechas();
}

// ===== Unidad
function guardarUnidadLS(){
    if(!esAdmin || !unidadSelect) return;
    const id = (unidadSelect.value || '').trim();
    const nombre = id ? (unidadSelect.options[unidadSelect.selectedIndex]?.text || '') : '';
    localStorage.setItem('unidad_operativa_id', id);
    localStorage.setItem('unidad_operativa_nombre', nombre);
}

function restaurarUnidadLS(){
    if(!esAdmin || !unidadSelect) return;
    const id = localStorage.getItem('unidad_operativa_id') || '';
    if(id){
        unidadSelect.value = id;
        guardarUnidadLS();
    }
}

// ===== PDFs
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

function setPdfLabel(inputId, nombreKey){
    const label = document.querySelector(`label[for='${inputId}']`);
    if(!label) return;

    const nombre = localStorage.getItem(nombreKey);
    const base = label.innerText.split("—")[0].trim();

    if(nombre){
        label.innerText = base + " — " + nombre;
    }else{
        label.innerText = base;
    }
}

function restaurarNombresPDF(){
    setPdfLabel('pdfSolicitud', 'pdf_solicitud_nombre');
    setPdfLabel('pdfCotizacion', 'pdf_cotizacion_nombre');
    setPdfLabel('pdfAutorizacion', 'pdf_autorizacion_nombre');
}

async function guardarPDF(inputId, keyBase, keyNombre) {
    const input = document.getElementById(inputId);
    if(!input) return;

    input.addEventListener("change", async e => {
        const file = e.target.files[0];
        if (!file) return;

        const base64 = await fileToBase64(file);
        localStorage.setItem(keyBase, base64);
        localStorage.setItem(keyNombre, file.name);

        restaurarNombresPDF();
        alert("PDF cargado correctamente");
    });
}

function verPDF(key){
    const pdf = localStorage.getItem(key);
    if(!pdf) return alert("No se ha cargado un PDF para este campo.");
    const win = window.open("");
    win.document.write(`<iframe width="100%" height="100%" src="${pdf}"></iframe>`);
}

// ===== Validaciones
function mostrarAdvertencia(html){
    mensajeFaltantes.innerHTML = html;
    modalAdvertencia.style.display = "flex";
}

function cerrarAdvertencia(){ modalAdvertencia.style.display = "none"; }

function validarDatosRequeridos(){
    let faltantes = [];

    if (!fechaEntrega.value) faltantes.push("Seleccionar la fecha de entrega");

    if (!localStorage.getItem("pdf_solicitud")) faltantes.push("Cargar PDF de solicitud del cliente");
    if (!localStorage.getItem("pdf_cotizacion")) faltantes.push("Cargar PDF de cotización");
    if (!localStorage.getItem("pdf_autorizacion")) faltantes.push("Cargar PDF de aceptación del cliente");

    if (esAdmin) {
        const unidadId = (localStorage.getItem("unidad_operativa_id") || '').trim();
        if (!unidadId) faltantes.push("Seleccionar la unidad operativa");
    }

    if (faltantes.length > 0) {
        mostrarAdvertencia("Debes completar lo siguiente:<br><br>• " + faltantes.join("<br>• "));
        return false;
    }

    return true;
}

// ===== Pedido tabla
function actualizarTablaPedido() {
    const tbody = document.querySelector("#tablaPedidoEspecial tbody");
    tbody.innerHTML = "";

    productosPedido.forEach((p, i) => {
        const precio = Number(p.precio) || 0;
        const cantidad = Number(p.cantidad) || 0;
        p.subtotal = precio * cantidad;

        tbody.innerHTML += `
            <tr>
                <td>${escapeHtml(p.nombre)}</td>
                <td>${escapeHtml(p.categoria)}</td>
                <td>${escapeHtml(p.unidad)}</td>
                <td>${cantidad}</td>
                <td>${escapeHtml(p.proveedor)}</td>
                <td>${money(precio)}</td>
                <td>${money(p.subtotal)}</td>
                <td>
                    <button type="button" class="btn" onclick="editarProducto(${i})">Editar</button>
                    <button type="button" class="btn-cancelar" onclick="eliminarProducto(${i})">Eliminar</button>
                </td>
            </tr>
        `;
    });

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
}

function eliminarProducto(i) {
    productosPedido.splice(i, 1);
    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
}

// ===== Modal producto
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
    if (proveedores.length === 0) {
        alert("Este producto no tiene proveedores asignados");
        return;
    }

    proveedorSelect.innerHTML = "";

    proveedores.forEach(prov => {
        const precio = Number(prov?.pivot?.precio) || 0;

        const data = {
            producto_id: producto.id,
            proveedor_id: prov.id,
            proveedor: prov.nombre,
            precio: precio
        };

        const option = document.createElement("option");
        option.value = JSON.stringify(data);
        option.textContent = `${prov.nombre} — $${precio.toFixed(2)}`;
        proveedorSelect.appendChild(option);
    });

    const datos = JSON.parse(proveedorSelect.value);

    productoSeleccionado = {
        nombre: producto.nombre ?? '',
        categoria: producto.categoria?.nombre ?? '',
        unidad: producto.unidad_medida ?? '',
        producto_id: producto.id,
        proveedor_id: datos.proveedor_id,
        proveedor: datos.proveedor,
        precio: Number(datos.precio) || 0
    };

    precioProveedor.textContent = money(productoSeleccionado.precio);

    productoEditandoIndex = null;
    cantidadInput.value = 1;

    modalTitulo.textContent = "Agregar " + (producto.nombre ?? '');
    btnAgregarModal.style.display = "inline-block";
    btnActualizarModal.style.display = "none";

    modalCantidad.style.display = "flex";
}

function cerrarModal(){
    modalCantidad.style.display = "none";
    productoEditandoIndex = null;
    cantidadInput.value = 1;
}

function actualizarPrecioProveedor() {
    const datos = JSON.parse(proveedorSelect.value);

    productoSeleccionado.proveedor_id = datos.proveedor_id;
    productoSeleccionado.proveedor = datos.proveedor;
    productoSeleccionado.precio = Number(datos.precio) || 0;

    precioProveedor.textContent = money(productoSeleccionado.precio);
}

function agregarProducto() {
    if(!productoSeleccionado) return;

    const nuevaCantidad = Math.max(1, numInt(cantidadInput.value, 1));

    const existente = productosPedido.find(p =>
        p.producto_id === productoSeleccionado.producto_id &&
        p.proveedor_id === productoSeleccionado.proveedor_id
    );

    if (existente) {
        existente.cantidad += nuevaCantidad;
        existente.subtotal = existente.cantidad * (Number(existente.precio) || 0);
    } else {
        productosPedido.push({
            ...productoSeleccionado,
            cantidad: nuevaCantidad,
            subtotal: nuevaCantidad * (Number(productoSeleccionado.precio) || 0)
        });
    }

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
    cerrarModal();
}

function editarProducto(index) {
    const p = productosPedido[index];
    productoEditandoIndex = index;

    abrirModalProducto(p.producto_id);

    cantidadInput.value = p.cantidad;

    [...proveedorSelect.options].forEach(opt => {
        const obj = JSON.parse(opt.value);
        if (obj.proveedor_id == p.proveedor_id) {
            proveedorSelect.value = opt.value;
        }
    });

    actualizarPrecioProveedor();

    modalTitulo.textContent = "Editar " + (p.nombre ?? '');
    btnAgregarModal.style.display = "none";
    btnActualizarModal.style.display = "inline-block";
}

function actualizarCantidad() {
    if (productoEditandoIndex === null) return;

    const nuevaCantidad = Math.max(1, numInt(cantidadInput.value, 1));
    const datos = JSON.parse(proveedorSelect.value);

    const p = productosPedido[productoEditandoIndex];

    p.cantidad = nuevaCantidad;
    p.proveedor_id = datos.proveedor_id;
    p.proveedor = datos.proveedor;
    p.precio = Number(datos.precio) || 0;
    p.subtotal = p.cantidad * p.precio;

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
    cerrarModal();
}

// ===== NO-ADMIN AJAX
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

    tbody.innerHTML = `<tr><td colspan="5" style="padding:14px; text-align:center;">Buscando...</td></tr>`;

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
                    <button type="button" class="btn-seleccionar" onclick="abrirModalProducto(${p.id})">
                        Seleccionar
                    </button>
                </td>
            </tr>
        `;
    }).join('');

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

    const maxBtns = 7;
    let start = Math.max(1, current - Math.floor(maxBtns/2));
    let end   = Math.min(last, start + maxBtns - 1);
    start = Math.max(1, end - maxBtns + 1);

    let html = '';

    if(current > 1){
        html += `<button type="button" onclick="buscarAjax(${current-1})">«</button>`;
    }

    for(let i=start; i<=end; i++){
        html += `<button type="button" class="${i===current?'activo':''}" onclick="buscarAjax(${i})">${i}</button>`;
    }

    if(current < last){
        html += `<button type="button" onclick="buscarAjax(${current+1})">»</button>`;
    }

    pagDiv.innerHTML = html;
}

// ===== Confirmar
btnConfirmarEspecial.onclick = () => {
    if (productosPedido.length === 0) {
        alert("Agrega productos antes de continuar");
        return;
    }
    persistirFechas();
    guardarUnidadLS();
    window.location.href = "{{ route('dashboard.pedidos.especial.previsualizar') }}";
};

// ===== Limpieza
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
        "pdf_autorizacion_nombre",
        "unidad_operativa_id",
        "unidad_operativa_nombre",
    ];
    claves.forEach(k => localStorage.removeItem(k));
}

function irMenuPrincipal() {
    limpiarPedidoEspecialStorage();
    window.location.href = "{{ route('dashboard.admin') }}";
}

// ===== INIT
document.addEventListener("DOMContentLoaded", () => {
    restaurarFechas();
    restaurarUnidadLS();
    restaurarNombresPDF();

    productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
    actualizarTablaPedido();

    fechaEntrega.addEventListener('change', persistirFechas);

    if (unidadSelect) {
        unidadSelect.addEventListener('change', guardarUnidadLS);
    }

    guardarPDF("pdfSolicitud", "pdf_solicitud", "pdf_solicitud_nombre");
    guardarPDF("pdfCotizacion", "pdf_cotizacion", "pdf_cotizacion_nombre");
    guardarPDF("pdfAutorizacion", "pdf_autorizacion", "pdf_autorizacion_nombre");

    document.querySelectorAll(".btn-ver").forEach(btn => {
        btn.addEventListener("click", () => {
            const key = btn.getAttribute('data-pdf');
            verPDF(key);
        });
    });

    // ✅ ADMIN: auto-submit al cambiar proveedor/categoría (como la otra vista)
    if(esAdmin && formFiltrosAdmin){
        proveedorFiltroAdmin?.addEventListener('change', () => formFiltrosAdmin.submit());
        categoriaFiltroAdmin?.addEventListener('change', () => formFiltrosAdmin.submit());

        // (opcional) Enter ya manda el form normal por default, pero si quieres auto-submit al teclear:
        // let t=null; qAdmin?.addEventListener('input',()=>{ clearTimeout(t); t=setTimeout(()=>formFiltrosAdmin.submit(),350); });
    }

    // NO-ADMIN AJAX
    if(!esAdmin){
        const input = document.getElementById('buscadorProductos');
        const selProv = document.getElementById('filtroProveedorNoAdmin');
        const selCat  = document.getElementById('filtroCategoriaNoAdmin');

        let t = null;
        input?.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => buscarAjax(1), 220);
        });

        selProv?.addEventListener('change', () => buscarAjax(1));
        selCat?.addEventListener('change', () => buscarAjax(1));
    }
});
</script>

@endsection
