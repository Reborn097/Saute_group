@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido Especial')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos'], true);
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button type="button" class="btn btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
    </div>

    {{-- =====================================================
                FECHAS
    ====================================================== --}}
    <div class="filtros filtros-2">
        <div class="campo">
            <label>Fecha de solicitud:</label>
            <input type="date" id="fechaSolicitud" readonly>
        </div>

        <div class="campo">
            <label>Fecha de entrega:</label>
            <input type="date" id="fechaEntrega">
        </div>
    </div>

    {{-- ✅ SOLO ADMIN/ENCARGADO PEDIDOS --}}
    @if($esAdminPedidos)
        <div class="filtros filtros-1">
            <div class="campo">
                <label>Unidad operativa:</label>
                <select id="unidadOperativaSelect">
                    <option value="">Selecciona una unidad...</option>
                    @foreach(($unidadesOperativas ?? collect()) as $u)
                        <option value="{{ $u->id }}">{{ $u->nombre }}</option>
                    @endforeach
                </select>
                <small class="ayuda">
                    * Esta unidad se usará para registrar el pedido especial.
                </small>
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
            <button type="button" class="btn btn-ver" data-pdf="pdf_solicitud">Ver PDF</button>
            <small class="pdf-hint" id="hint_pdfSolicitud"></small>
        </div>

        <div class="pdf-card">
            <label for="pdfCotizacion" class="pdf-label"><strong>📄 Cotización generada</strong></label>
            <input type="file" id="pdfCotizacion" accept="application/pdf">
            <button type="button" class="btn btn-ver" data-pdf="pdf_cotizacion">Ver PDF</button>
            <small class="pdf-hint" id="hint_pdfCotizacion"></small>
        </div>

        <div class="pdf-card">
            <label for="pdfAutorizacion" class="pdf-label"><strong>📄 Aceptación del cliente</strong></label>
            <input type="file" id="pdfAutorizacion" accept="application/pdf">
            <button type="button" class="btn btn-ver" data-pdf="pdf_autorizacion">Ver PDF</button>
            <small class="pdf-hint" id="hint_pdfAutorizacion"></small>
        </div>
    </div>

    {{-- =====================================================
                PRODUCTOS DISPONIBLES
    ====================================================== --}}
    <h3 class="titulo-seccion">Presentaciones disponibles</h3>

    @if($esAdminPedidos)
        <form method="GET" action="{{ route('dashboard.pedidos.especial.crear') }}" class="filtros" id="formFiltrosAdmin">

            <div class="campo">
                <label>Proveedor:</label>
                <select name="proveedor_id" id="proveedorFiltroAdmin">
                    <option value="">Todos</option>
                    @foreach(($proveedores ?? []) as $prov)
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
                    @foreach(($categorias ?? []) as $cat)
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

            <div class="campo acciones-inline">
                <button type="submit" class="btn btn-filtro">Buscar</button>
                <a href="{{ route('dashboard.pedidos.especial.crear') }}" class="btn btn-cancelar btn-filtro">Limpiar</a>
            </div>
        </form>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                <tr>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Descripción - Contenido</th>
                    <th>Unidad contenido</th>
                    <th>Precio</th>
                    <th>Seleccionar</th>
                </tr>
                </thead>
                <tbody id="tbodyProductosDisponibles">
                @forelse(($presentaciones ?? collect()) as $p)
                    @php
                        $precioDefault = isset($p->pp_default_precio) ? (float)$p->pp_default_precio : null;
                        $descContenido = $p->descripcion ?? '—';
                        if (!empty($p->contenido)) {
                            $descContenido = trim($p->descripcion ?? '') . ' - ' . $p->contenido;
                        }
                    @endphp
                    <tr data-presentacion-id="{{ $p->id }}">
                        <td>{{ $p->producto->nombre ?? '—' }}</td>
                        <td>{{ $p->producto->marca ?? '—' }}</td>
                        <td>{{ $descContenido }}</td>
                        <td>{{ $p->unidad_contenido ?? '—' }}</td>
                        <td>
                            @if($precioDefault !== null)
                                ${{ number_format($precioDefault,2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-seleccionar" onclick="abrirModalPresentacion({{ $p->id }})">
                                Seleccionar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">
                            No hay presentaciones con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            @if(isset($presentaciones) && method_exists($presentaciones, 'links'))
                <div class="paginacion">
                    {{ $presentaciones->links('vendor.pagination.dashboard') }}
                </div>
            @endif
        </div>

    @else
        <div class="filtros">
            <div class="campo">
                <label>Proveedor:</label>
                <select id="filtroProveedorNoAdmin">
                    <option value="">Todos</option>
                    @foreach(($proveedores ?? []) as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label>Categoría:</label>
                <select id="filtroCategoriaNoAdmin">
                    <option value="">Todas</option>
                    @foreach(($categorias ?? []) as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label>Buscar presentación:</label>
                <input type="text" id="buscadorProductos" placeholder="Escribe al menos 2 letras...">
            </div>
        </div>

        <small class="nota">
            No se muestra el catálogo. Solo verás presentaciones al buscar.
        </small>

        <div class="tabla-contenedor" style="margin-top:12px;">
            <table class="tabla">
                <thead>
                <tr>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Descripción - Contenido</th>
                    <th>Unidad contenido</th>
                    <th>Precio</th>
                    <th>Seleccionar</th>
                </tr>
                </thead>
                <tbody id="tbodyResultadosBusqueda">
                <tr>
                    <td colspan="6" class="text-center">
                        Escribe para buscar presentaciones.
                    </td>
                </tr>
                </tbody>
            </table>

            <div id="paginacionAjax" class="paginacion" style="display:none;"></div>
        </div>
    @endif

    {{-- =====================================================
                TABLA DEL PEDIDO ESPECIAL
    ====================================================== --}}
    <h3 class="titulo-seccion">Presentaciones en el pedido</h3>

    <div class="tabla-contenedor">
        <table class="tabla" id="tablaPedidoEspecial">
            <thead>
            <tr>
                <th>Producto</th>
                <th>Marca</th>
                <th>Descripción - Contenido</th>
                <th>Unidad contenido</th>
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
        <button type="button" class="btn btn-confirmar" id="btnConfirmarEspecial">
            Previsualizar Pedido Especial
        </button>
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

        @if($esAdminPedidos)
            <div class="grupo">
                <label>Proveedor</label>
                <select id="proveedorSelect" onchange="actualizarPrecioProveedor()"></select>
            </div>
        @endif

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
            <button type="button" class="btn btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<style>
/* =========================
   FIX: BOTONES NO SE DEFORMAN
========================= */
*{ box-sizing:border-box; }

.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
    font-family:'Poppins', sans-serif;
}

.titulo-seccion{ margin-top:25px; margin-bottom:10px; font-size:20px; font-weight:800; }

.acciones-superior{ display:flex; gap:10px; flex-wrap:wrap; }

.filtros{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:18px;
    margin-bottom:22px;
}
.filtros-2{ grid-template-columns:1fr 1fr; }
.filtros-1{ grid-template-columns:1fr; }

.campo label{ display:block; font-weight:700; margin-bottom:6px; }
input, select{
    width:100%;
    padding:9px 10px;
    border-radius:10px;
    border:1px solid #ccc;
    background:#fff;
    outline:none;
}

.ayuda{ display:block; margin-top:6px; color:#555; }
.nota{ display:block; opacity:.8; margin-top:8px; }
.text-center{ padding:14px; text-align:center; }

.acciones-inline{ display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
.acciones-inline .btn-filtro{
    width:auto !important;
    min-width:130px;
    height:auto;
    min-height:40px;
    padding:9px 14px !important;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    line-height:1;
}
@media (max-width:720px){
    .acciones-inline .btn-filtro{ width:100%; }
}

.tabla-contenedor{
    margin-top:10px;
    width:100%;
    overflow-x:auto;                 /* ✅ evita que todo se aplaste */
    -webkit-overflow-scrolling:touch;
    border-radius:12px;
}
.tabla{
    width:100%;
    min-width:980px;                 /* ✅ mantiene columnas y evita “aplastar” botones */
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
}
.tabla th{
    background:#b22b27;
    color:white;
    padding:12px;
    text-align:center;
    white-space:nowrap;
    font-weight:900;
}
.tabla td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}
.tabla tr:hover{ background:#f5d6d6; }

/* ✅ BOTONES: inline-flex + nowrap + line-height */
.btn{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:10px 14px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    white-space:nowrap;
    line-height:1;
    min-height:40px;
}
.btn:hover{ background:#941c1c; }

.btn-menu{ background:#999; }
.btn-menu:hover{ background:#777; }

.btn-cancelar{
    background:#777 !important;
    text-decoration:none;
}
.btn-cancelar:hover{ opacity:.9; }

.btn-seleccionar{ padding:10px 14px; }
.btn-confirmar{ padding:12px 16px; }

.btn-ver{
    width:100%;
    justify-content:center;
}

/* PDFs */
.pdf-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-top:15px;
}
.pdf-card{
    background:#fff;
    border-radius:12px;
    padding:15px 18px;
    border:1px solid #d8d8d8;
    box-shadow:0 1px 3px rgba(0,0,0,0.08);
    display:flex;
    flex-direction:column;
    gap:10px;
    min-width:0;
}
.pdf-card input[type="file"]{
    border:1px solid #ccc;
    padding:10px;
    border-radius:10px;
    background:#fafafa;
}
.pdf-hint{
    display:block;
    margin-top:4px;
    font-size:12px;
    color:#6b7280;
    min-height:16px;
}
@media (max-width: 980px){
    .pdf-grid{ grid-template-columns:1fr; }
}

/* Modal */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    align-items:center;
    justify-content:center;
    z-index:5000;
    padding:14px;
}
.modal-contenido{
    background:#fff;
    width:min(420px, 100%);
    padding:20px;
    border-radius:12px;
    text-align:center;
}
.modal-acciones{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:15px;
    flex-wrap:wrap;
}
.modal-acciones .btn{ width:auto; }
@media (max-width: 520px){
    .filtros{ grid-template-columns:1fr; }
    .tabla{ min-width:900px; }
    .modal-acciones .btn{ width:100%; }
}

/* paginación */
.paginacion{ margin-top:12px; display:flex; justify-content:center; flex-wrap:wrap; gap:8px; }
.paginacion nav{ display:flex; justify-content:center; align-items:center; gap:6px; flex-wrap:wrap; }
.paginacion svg{ width:18px !important; height:18px !important; }
.paginacion a, .paginacion span{
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    line-height:1 !important;
    padding:8px 10px !important;
    border-radius:10px;
}
.paginacion .hidden{ display:none !important; }

#paginacionAjax button{
    background:#fff;
    border:1px solid #ccc;
    padding:8px 10px;
    border-radius:10px;
    cursor:pointer;
}
#paginacionAjax button.activo{
    background:#b22b27;
    color:#fff;
    border-color:#b22b27;
}

.acciones-final{
    margin-top:22px;
    padding-top:12px;
    display:flex;
    justify-content:flex-start;
    gap:10px;
    flex-wrap:wrap;
}
.acciones-final .btn{ width:auto; }
@media (max-width:720px){
    .acciones-final .btn{ width:100%; }
}
</style>

<script>
/* =========================================================
   CONFIG
========================================================= */
const ES_ADMIN_PEDIDOS = @json($esAdminPedidos);
const presentacionesDataAdmin = @json(isset($presentaciones) && method_exists($presentaciones, 'items') ? $presentaciones->items() : []);
let resultadosBusqueda = [];

/* =========================================================
   REFS
========================================================= */
const fechaSolicitud = document.getElementById('fechaSolicitud');
const fechaEntrega   = document.getElementById('fechaEntrega');

const modalCantidad     = document.getElementById('modalCantidad');
const modalAdvertencia  = document.getElementById('modalAdvertencia');
const mensajeFaltantes  = document.getElementById('mensajeFaltantes');

const modalTitulo       = document.getElementById('modalTitulo');
const cantidadInput     = document.getElementById('cantidadInput');
const precioProveedor   = document.getElementById('precioProveedor');

const btnAgregarModal    = document.getElementById('btnAgregarModal');
const btnActualizarModal = document.getElementById('btnActualizarModal');

const btnConfirmarEspecial = document.getElementById('btnConfirmarEspecial');

const unidadSelect = document.getElementById('unidadOperativaSelect');
const proveedorSelect = document.getElementById('proveedorSelect');

// ADMIN filtros
const formFiltrosAdmin = document.getElementById('formFiltrosAdmin');
const proveedorFiltroAdmin = document.getElementById('proveedorFiltroAdmin');
const categoriaFiltroAdmin = document.getElementById('categoriaFiltroAdmin');

/* =========================================================
   UTILS
========================================================= */
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
function num(v, def = 0){
    if(v === null || v === undefined) return def;
    const n = Number(v);
    return Number.isFinite(n) ? n : def;
}
function money(n){
    const val = Number(n);
    if(!Number.isFinite(val)) return '$0.00';
    return '$' + val.toFixed(2);
}

/* =========================================================
   STORAGE: FECHAS / UNIDAD
========================================================= */
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
function guardarUnidadLS(){
    if(!ES_ADMIN_PEDIDOS || !unidadSelect) return;
    const id = (unidadSelect.value || '').trim();
    const nombre = id ? (unidadSelect.options[unidadSelect.selectedIndex]?.text || '') : '';
    localStorage.setItem('unidad_operativa_id', id);
    localStorage.setItem('unidad_operativa_nombre', nombre);
}
function restaurarUnidadLS(){
    if(!ES_ADMIN_PEDIDOS || !unidadSelect) return;
    const id = localStorage.getItem('unidad_operativa_id') || '';
    if(id){
        unidadSelect.value = id;
        guardarUnidadLS();
    }
}

/* =========================================================
   PDFs: LocalStorage + Preview (BLOB URL)
========================================================= */
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
function formatBytes(bytes){
    const b = Number(bytes || 0);
    if(!b) return '0 B';
    const u = ['B','KB','MB','GB'];
    const i = Math.floor(Math.log(b)/Math.log(1024));
    return (b/Math.pow(1024,i)).toFixed(i===0?0:2)+' '+u[i];
}
function restorePdfHints(){
    const a = localStorage.getItem('pdf_solicitud_nombre') || '';
    const b = localStorage.getItem('pdf_cotizacion_nombre') || '';
    const c = localStorage.getItem('pdf_autorizacion_nombre') || '';
    document.getElementById('hint_pdfSolicitud').textContent = a ? `Archivo: ${a}` : '';
    document.getElementById('hint_pdfCotizacion').textContent = b ? `Archivo: ${b}` : '';
    document.getElementById('hint_pdfAutorizacion').textContent = c ? `Archivo: ${c}` : '';
}
function openPdfFromLocalStorage(key){
    const raw = (localStorage.getItem(key) || '').trim();
    if(!raw) return alert("No se ha cargado un PDF para este campo.");

    let base64 = raw;
    let mime = "application/pdf";

    if(raw.startsWith("data:")){
        const parts = raw.split(",");
        if(parts.length < 2 || !parts[1] || !parts[1].trim()){
            return alert("PDF inválido (vacío). Vuelve a adjuntarlo.");
        }
        const m = parts[0].match(/data:(.*?);base64/i);
        if(m && m[1]) mime = m[1];
        base64 = parts[1].trim();
    } else {
        if(base64.length < 50){
            return alert("PDF inválido (vacío). Vuelve a adjuntarlo.");
        }
    }

    try{
        const bytes = atob(base64);
        const arr = new Uint8Array(bytes.length);
        for(let i=0;i<bytes.length;i++) arr[i] = bytes.charCodeAt(i);
        const blob = new Blob([arr], { type: mime });
        const url = URL.createObjectURL(blob);
        window.open(url, "_blank");
        setTimeout(()=>URL.revokeObjectURL(url), 60000);
    }catch(e){
        console.error(e);
        alert("PDF inválido o corrupto. Vuelve a adjuntarlo.");
    }
}
async function bindPdfInput(inputId, keyBase, keyNombre, hintId){
    const input = document.getElementById(inputId);
    if(!input) return;

    input.addEventListener("change", async (e) => {
        const file = e.target.files?.[0];
        if(!file) return;

        if(file.type !== "application/pdf"){
            alert("Solo se permiten archivos PDF.");
            input.value = "";
            return;
        }

        try{
            const dataUrl = await fileToBase64(file);
            const parts = String(dataUrl).split(",");
            if(parts.length < 2 || !parts[1] || !parts[1].trim()){
                alert("No se pudo leer el PDF (vacío). Intenta de nuevo.");
                input.value = "";
                return;
            }

            localStorage.setItem(keyBase, dataUrl);
            localStorage.setItem(keyNombre, file.name);

            const hint = document.getElementById(hintId);
            if(hint) hint.textContent = `Archivo: ${file.name} (${formatBytes(file.size)})`;

        }catch(err){
            console.error(err);
            alert("Error leyendo el PDF. Intenta de nuevo.");
            input.value = "";
        }
    });
}
function verPDF(key){ openPdfFromLocalStorage(key); }

/* =========================================================
   VALIDACIONES
========================================================= */
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

    if (ES_ADMIN_PEDIDOS) {
        const unidadId = (localStorage.getItem("unidad_operativa_id") || '').trim();
        if (!unidadId) faltantes.push("Seleccionar la unidad operativa");
    }

    if (faltantes.length > 0) {
        mostrarAdvertencia("Debes completar lo siguiente:<br><br>• " + faltantes.join("<br>• "));
        return false;
    }

    return true;
}

/* =========================================================
   PEDIDO: TABLA
========================================================= */
let productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
let productoSeleccionado = null;
let productoEditandoIndex = null;

function getPresentacionesEnPedido(){
    const ids = new Set();
    (productosPedido || []).forEach(p => {
        const id = p.presentacion_id ?? null;
        if (id) ids.add(String(id));
    });
    return ids;
}

function ocultarPresentacionesEnCatalogo(){
    const ids = getPresentacionesEnPedido();
    document.querySelectorAll('[data-presentacion-id]').forEach(tr => {
        const id = tr.getAttribute('data-presentacion-id');
        tr.style.display = (id && ids.has(String(id))) ? 'none' : '';
    });
}

function actualizarTablaPedido() {
    const tbody = document.querySelector("#tablaPedidoEspecial tbody");
    tbody.innerHTML = "";

    (productosPedido || []).forEach((p, i) => {
        const precio = Number(p.precio) || 0;
        const cantidad = Number(p.cantidad) || 0;
        const subtotal = precio * cantidad;

        tbody.innerHTML += `
            <tr>
                <td>${escapeHtml(p.producto || '')}</td>
                <td>${escapeHtml(p.marca || '')}</td>
                <td>${escapeHtml(p.descripcion_contenido || '')}</td>
                <td>${escapeHtml(p.unidad_contenido || '')}</td>
                <td>${cantidad}</td>
                <td>${escapeHtml(p.proveedor || (ES_ADMIN_PEDIDOS ? '—' : 'Asignado'))}</td>
                <td>${money(precio)}</td>
                <td>${money(subtotal)}</td>
                <td>
                    <button type="button" class="btn" onclick="editarProducto(${i})">Editar</button>
                    <button type="button" class="btn btn-cancelar" onclick="eliminarProducto(${i})">Eliminar</button>
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
    ocultarPresentacionesEnCatalogo();
}

/* =========================================================
   MODAL PRODUCTO
========================================================= */
function abrirModalPresentacion(presentacion_id) {
    if (!validarDatosRequeridos()) return;

    const ids = getPresentacionesEnPedido();
    if (productoEditandoIndex === null && ids.has(String(presentacion_id))) {
        alert("Esta presentación ya está agregada en el pedido.");
        return;
    }

    let presentacion = null;

    if(ES_ADMIN_PEDIDOS){
        presentacion = (presentacionesDataAdmin || []).find(p => p.id == presentacion_id);
        if(!presentacion){
            alert("Producto no encontrado en esta página. Cambia de página o ajusta filtros.");
            return;
        }
    }else{
        presentacion = (resultadosBusqueda || []).find(p => p.id == presentacion_id);
        if(!presentacion){
            alert("Primero busca el producto y selecciónalo desde los resultados.");
            return;
        }
    }

    const descPresenta = presentacion.descripcion ?? '—';
    const descContenido = presentacion.contenido ? `${descPresenta} - ${presentacion.contenido}` : descPresenta;

    if(!ES_ADMIN_PEDIDOS){
        const ppIdDefault = presentacion.pp_default_id ?? null;
        const precioDefault = num(presentacion.pp_default_precio, 0);

        if(!ppIdDefault){
            alert("Este producto no tiene proveedor principal asignado.");
            return;
        }

        productoSeleccionado = {
            presentacion_id: presentacion.id,
            producto: presentacion.producto?.nombre ?? '',
            marca: presentacion.producto?.marca ?? '',
            descripcion_contenido: descContenido,
            unidad_contenido: presentacion.unidad_contenido ?? '—',
            producto_proveedor_id: ppIdDefault,
            proveedor_id: null,
            proveedor: null,
            precio: precioDefault
        };

        precioProveedor.textContent = money(productoSeleccionado.precio);
    }else{
        const proveedores = Array.isArray(presentacion.proveedores) ? presentacion.proveedores : [];
        const proveedoresValidos = proveedores.filter(pr => pr?.id);

        if (proveedoresValidos.length === 0) {
            alert("Este producto no tiene proveedores válidos.");
            return;
        }

        proveedorSelect.innerHTML = "";
        proveedoresValidos.forEach(pp => {
            const data = {
                producto_proveedor_id: pp.id,
                proveedor_id: pp.proveedor_id,
                proveedor: pp.proveedor?.nombre ?? pp.nombre ?? '—',
                precio: num(pp.precio_vigente, 0)
            };
            const opt = document.createElement("option");
            opt.value = JSON.stringify(data);
            opt.textContent = `${data.proveedor} — $${Number(data.precio).toFixed(2)}`;
            proveedorSelect.appendChild(opt);
        });

        const datos = JSON.parse(proveedorSelect.value);

        productoSeleccionado = {
            presentacion_id: presentacion.id,
            producto: presentacion.producto?.nombre ?? '',
            marca: presentacion.producto?.marca ?? '',
            descripcion_contenido: descContenido,
            unidad_contenido: presentacion.unidad_contenido ?? '—',
            producto_proveedor_id: datos.producto_proveedor_id,
            proveedor_id: datos.proveedor_id,
            proveedor: datos.proveedor,
            precio: num(datos.precio, 0)
        };

        precioProveedor.textContent = money(productoSeleccionado.precio);
    }

    cantidadInput.value = 1;
    modalTitulo.textContent = "Agregar " + (productoSeleccionado.producto ?? '');

    btnAgregarModal.style.display = "inline-flex";
    btnActualizarModal.style.display = "none";
    modalCantidad.style.display = "flex";
}

function cerrarModal(){
    modalCantidad.style.display = "none";
    productoEditandoIndex = null;
    cantidadInput.value = 1;
}

function actualizarPrecioProveedor() {
    if(!ES_ADMIN_PEDIDOS || !proveedorSelect || !productoSeleccionado) return;

    const datos = JSON.parse(proveedorSelect.value);
    productoSeleccionado.producto_proveedor_id = datos.producto_proveedor_id;
    productoSeleccionado.proveedor_id = datos.proveedor_id;
    productoSeleccionado.proveedor = datos.proveedor;
    productoSeleccionado.precio = num(datos.precio, 0);

    precioProveedor.textContent = money(productoSeleccionado.precio);
}

function agregarProducto() {
    if(!productoSeleccionado) return;

    const nuevaCantidad = Math.max(1, numInt(cantidadInput.value, 1));
    const keyPres = productoSeleccionado.presentacion_id;

    const idx = productosPedido.findIndex(p => String(p.presentacion_id) === String(keyPres));
    if (idx >= 0) {
        productosPedido[idx].cantidad += nuevaCantidad;
    } else {
        productosPedido.push({ ...productoSeleccionado, cantidad: nuevaCantidad });
    }

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
    ocultarPresentacionesEnCatalogo();
    cerrarModal();
}

function editarProducto(index) {
    const p = productosPedido[index];
    productoEditandoIndex = index;

    // Abrimos modal pero sin bloquear por “ya existe”
    abrirModalPresentacion(p.presentacion_id);

    // Si por validación no abre, regresamos
    if (modalCantidad.style.display !== 'flex') {
        productoEditandoIndex = null;
        return;
    }

    cantidadInput.value = p.cantidad ?? 1;

    if(ES_ADMIN_PEDIDOS && proveedorSelect && p.producto_proveedor_id){
        const opts = Array.from(proveedorSelect.options);
        const match = opts.find(o => {
            try{ return JSON.parse(o.value).producto_proveedor_id == p.producto_proveedor_id; }
            catch(e){ return false; }
        });
        if(match){
            proveedorSelect.value = match.value;
            actualizarPrecioProveedor();
        }
    }

    modalTitulo.textContent = "Editar " + (p.producto ?? '');
    btnAgregarModal.style.display = "none";
    btnActualizarModal.style.display = "inline-flex";
}

function actualizarCantidad() {
    if (productoEditandoIndex === null || !productoSeleccionado) return;

    const nuevaCantidad = Math.max(1, numInt(cantidadInput.value, 1));
    const p = productosPedido[productoEditandoIndex];

    p.cantidad = nuevaCantidad;

    if(ES_ADMIN_PEDIDOS && proveedorSelect){
        const datos = JSON.parse(proveedorSelect.value);
        p.producto_proveedor_id = datos.producto_proveedor_id;
        p.proveedor_id = datos.proveedor_id;
        p.proveedor = datos.proveedor;
        p.precio = num(datos.precio, 0);
    } else {
        p.producto_proveedor_id = productoSeleccionado.producto_proveedor_id;
        p.precio = productoSeleccionado.precio;
    }

    localStorage.setItem("pedidoEspecial", JSON.stringify(productosPedido));
    actualizarTablaPedido();
    cerrarModal();
}

/* =========================================================
   NO-ADMIN AJAX
========================================================= */
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
            <tr><td colspan="6" class="text-center">Escribe al menos <b>2 letras</b> para buscar.</td></tr>`;
        return;
    }

    tbody.innerHTML = `<tr><td colspan="6" class="text-center">Buscando...</td></tr>`;

    const params = new URLSearchParams({ q, page });
    if(proveedor_id) params.append('proveedor_id', proveedor_id);
    if(categoria_id) params.append('categoria_id', categoria_id);

    const url = `{{ route('dashboard.pedidos.especial.buscar_productos') }}?${params.toString()}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const json = await res.json();

    resultadosBusqueda = json.data || [];
    const ids = getPresentacionesEnPedido();
    resultadosBusqueda = resultadosBusqueda.filter(p => !ids.has(String(p.id)));

    if(resultadosBusqueda.length === 0){
        pagDiv.style.display = 'none';
        tbody.innerHTML = `<tr><td colspan="6" class="text-center">Sin resultados.</td></tr>`;
        return;
    }

    tbody.innerHTML = resultadosBusqueda.map(p => {
        const precio = num(p.pp_default_precio, 0);
        const descPresenta = p.descripcion ?? '—';
        const descContenido = p.contenido ? `${descPresenta} - ${p.contenido}` : descPresenta;

        return `
            <tr>
                <td>${escapeHtml(p.producto?.nombre ?? '')}</td>
                <td>${escapeHtml(p.producto?.marca ?? '')}</td>
                <td>${escapeHtml(descContenido)}</td>
                <td>${escapeHtml(p.unidad_contenido ?? '—')}</td>
                <td>${money(precio)}</td>
                <td><button type="button" class="btn btn-seleccionar" onclick="abrirModalPresentacion(${p.id})">Seleccionar</button></td>
            </tr>`;
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
    if(current > 1) html += `<button type="button" onclick="buscarAjax(${current-1})">«</button>`;
    for(let i=start; i<=end; i++){
        html += `<button type="button" class="${i===current?'activo':''}" onclick="buscarAjax(${i})">${i}</button>`;
    }
    if(current < last) html += `<button type="button" onclick="buscarAjax(${current+1})">»</button>`;
    pagDiv.innerHTML = html;
}

/* =========================================================
   CONFIRMAR / LIMPIEZA
========================================================= */
btnConfirmarEspecial?.addEventListener('click', () => {
    if ((productosPedido || []).length === 0) {
        alert("Agrega productos antes de continuar");
        return;
    }
    if(!validarDatosRequeridos()) return;

    persistirFechas();
    guardarUnidadLS();
    window.location.href = "{{ route('dashboard.pedidos.especial.previsualizar') }}";
});

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

/* =========================================================
   INIT
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    restaurarFechas();
    restaurarUnidadLS();

    try{
        productosPedido = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
        if(!Array.isArray(productosPedido)) productosPedido = [];
    }catch(e){ productosPedido = []; }

    actualizarTablaPedido();
    ocultarPresentacionesEnCatalogo();

    fechaEntrega.addEventListener('change', persistirFechas);
    if (unidadSelect) unidadSelect.addEventListener('change', guardarUnidadLS);

    restorePdfHints();
    bindPdfInput("pdfSolicitud", "pdf_solicitud", "pdf_solicitud_nombre", "hint_pdfSolicitud");
    bindPdfInput("pdfCotizacion", "pdf_cotizacion", "pdf_cotizacion_nombre", "hint_pdfCotizacion");
    bindPdfInput("pdfAutorizacion", "pdf_autorizacion", "pdf_autorizacion_nombre", "hint_pdfAutorizacion");

    document.querySelectorAll(".btn-ver").forEach(btn => {
        btn.addEventListener("click", () => {
            const key = btn.getAttribute('data-pdf');
            verPDF(key);
        });
    });

    if(ES_ADMIN_PEDIDOS && formFiltrosAdmin){
        proveedorFiltroAdmin?.addEventListener('change', () => formFiltrosAdmin.submit());
        categoriaFiltroAdmin?.addEventListener('change', () => formFiltrosAdmin.submit());
    }

    if(!ES_ADMIN_PEDIDOS){
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
