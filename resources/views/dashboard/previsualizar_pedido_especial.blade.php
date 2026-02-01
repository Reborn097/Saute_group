@extends('layouts.dashboard')

@section('titulo', 'Previsualización del Pedido Especial')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    $esAdmin = in_array($role, ['admin', 'encargado_pedidos'], true);
@endphp

<div class="contenedor">

    <button type="button" class="btn-menu" onclick="regresar()">Regresar</button>

    <h2 style="margin:10px 0 14px;">Previsualización del Pedido Especial</h2>

    <p><strong>Número de pedido:</strong> <span id="codigoPedido">Se genera al confirmar</span></p>
    <p><strong>Fecha de solicitud:</strong> <span id="fechaSolicitudTxt"></span></p>
    <p><strong>Fecha de entrega:</strong> <span id="fechaEntregaTxt"></span></p>

    <p id="wrapUnidad" style="display:none;">
        <strong>Unidad operativa:</strong>
        <span id="unidadTxt"></span>
    </p>

    <h3 class="titulo-seccion">Documentos adjuntos</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Archivo</th>
                    <th>Ver</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>PDF del cliente</td>
                    <td id="pdfClienteNombre"></td>
                    <td><button type="button" class="btn" onclick="abrirPDF('pdf_solicitud')">Ver PDF</button></td>
                </tr>
                <tr>
                    <td>PDF cotización</td>
                    <td id="pdfCotizacionNombre"></td>
                    <td><button type="button" class="btn" onclick="abrirPDF('pdf_cotizacion')">Ver PDF</button></td>
                </tr>
                <tr>
                    <td>PDF aceptación</td>
                    <td id="pdfAceptacionNombre"></td>
                    <td><button type="button" class="btn" onclick="abrirPDF('pdf_autorizacion')">Ver PDF</button></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="titulo-seccion">Presentaciones en el pedido</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Descripción - Contenido</th>
                    <th>Unidad contenido</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="tbodyPrevio"></tbody>
        </table>
    </div>

    <h3 class="total-titulo">Total: $<span id="totalGeneral">0.00</span></h3>

    {{-- ===========================
        COTIZADOR POR COMENSAL
    =========================== --}}
    <div class="cotizador-box" id="cotizadorBox">
        <div class="cotizador-header">
            <div class="cotizador-title">
                <span class="cotizador-icon">💡</span>
                <div>
                    <h3>Cotizador por comensal</h3>
                    <p>Simulación para medir costo por persona. No modifica el pedido.</p>
                </div>
            </div>

            <div class="cotizador-total">
                <span>Total del pedido</span>
                <strong id="cotTotalDinero">$ 0.00</strong>
            </div>
        </div>

        <div class="cotizador-grid">
            <div class="cotizador-field">
                <label for="comensalesEstimados">Comensales estimados</label>
                <input type="number"
                       id="comensalesEstimados"
                       min="1"
                       step="1"
                       placeholder="Ej. 120"
                       class="cotizador-input">
                <small class="cotizador-help">Solo enteros. Ej: 80, 120, 250.</small>
            </div>

            <div class="cotizador-field">
                <label for="costoDeseado">Costo deseado por comensal (MXN)</label>
                <input type="number"
                       id="costoDeseado"
                       min="0"
                       step="0.01"
                       placeholder="Ej. 90.00"
                       class="cotizador-input">
                <small class="cotizador-help">Opcional. Muestra excedente y cuánto reducir del total.</small>
            </div>

            <div class="cotizador-result">
                <div class="cotizador-kpi">
                    <span>Costo actual por comensal</span>
                    <strong id="kpiActual">—</strong>
                </div>

                <div class="cotizador-kpi" id="kpiDiffWrap" style="display:none;">
                    <span id="kpiDiffLabel">Diferencia</span>
                    <strong id="kpiDiff">—</strong>
                </div>

                <div class="cotizador-kpi cotizador-kpi-compact" id="kpiExcesoWrap" style="display:none;">
                    <span id="kpiExcesoLabel">Exceso total</span>
                    <strong id="kpiExceso">—</strong>
                </div>

                <div class="cotizador-alert" id="alertAjuste" style="display:none;">
                    <div class="cotizador-alert-title">Para cumplir el objetivo</div>
                    <div class="cotizador-alert-body">
                        Debes reducir el pedido en:
                        <strong id="kpiReducir">—</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="acciones-final">
        <button type="button" class="btn-confirmar" onclick="confirmarPedido()">Confirmar pedido especial</button>
    </div>

</div>

{{-- MODAL ERROR --}}
<div id="modalError" class="modal">
    <div class="modal-contenido">
        <h3 style="color:#b22b27;">✖ Error</h3>
        <p id="modalErrorTxt">No se pudo conectar con el servidor.</p>
        <button type="button" class="btn" onclick="cerrarError()">Aceptar</button>
    </div>
</div>

{{-- MODAL ÉXITO --}}
<div id="modalExito" class="modal">
    <div class="modal-contenido">
        <h3 style="color:#2a7a2a;">✔ Pedido guardado</h3>
        <p>El pedido especial se guardó correctamente.</p>
        <p><strong>Código:</strong> <span id="codigoReal">—</span></p>
        <button type="button" class="btn" onclick="cerrarExito()">Aceptar</button>
    </div>
</div>

<style>
*{ box-sizing:border-box; }

.contenedor{
    background:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
    font-family:'Poppins', sans-serif;
}

.titulo-seccion{ margin-top:22px; margin-bottom:10px; font-size:18px; font-weight:900; }
.total-titulo{ margin-top:18px; margin-bottom:10px; }

/* Tabla (con scroll horizontal si se necesita) */
.tabla-contenedor{
    width:100%;
    overflow-x:auto;
    border-radius:12px;
}
.tabla{
    width:100%;
    min-width:820px;
    background:white;
    border-collapse:collapse;
    border-radius:10px;
    overflow:hidden;
}
.tabla th{
    background:#b22b27;
    color:white;
    padding:10px;
    text-align:center;
    white-space:nowrap;
    font-weight:900;
}
.tabla td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
}

/* Botones */
.btn-menu, .btn-confirmar, .btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    white-space:nowrap;
    line-height:1;
    min-height:40px;
}
.btn-menu{ background:#999; }
.btn-menu:hover{ background:#777; }
.btn:hover, .btn-confirmar:hover{ background:#941c1c; }

.acciones-final{ margin-top: 12px; display:flex; flex-wrap:wrap; gap:10px; }

/* Modal */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
    z-index:900;
    padding:14px;
}
.modal-contenido{
    background:white;
    padding:30px;
    border-radius:12px;
    text-align:center;
    width:min(380px, 100%);
}

/* ===========================
   Cotizador
=========================== */
.cotizador-box{
    margin: 14px 0 10px;
    border-radius: 12px;
    padding: 16px;
    background: #fff;
    border: 1px solid rgba(178, 43, 39, .18);
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
}
.cotizador-header{
    display:flex;
    gap:14px;
    align-items:flex-start;
    justify-content:space-between;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(0,0,0,.06);
}
.cotizador-title{ display:flex; gap:10px; align-items:flex-start; }
.cotizador-title h3{ margin:0; font-size: 16px; font-weight: 900; color:#1f2937; }
.cotizador-title p{ margin:3px 0 0; font-size: 12.5px; color:#6b7280; line-height: 1.35; }

.cotizador-icon{
    width:34px;height:34px;
    display:grid;place-items:center;
    border-radius: 10px;
    background: rgba(178, 43, 39, .10);
    font-size: 18px;
}
.cotizador-total{ text-align:right; min-width: 170px; }
.cotizador-total span{ display:block; font-size: 12px; color:#6b7280; margin-bottom: 2px; }
.cotizador-total strong{ display:block; font-size: 16px; font-weight: 900; color:#111827; }

.cotizador-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items: stretch;
}
.cotizador-field label{
    display:block;
    font-weight: 800;
    font-size: 12.5px;
    color:#374151;
    margin-bottom: 6px;
}
.cotizador-input{
    width:100%;
    height:42px;
    border: 1px solid rgba(0,0,0,.12);
    border-radius: 10px;
    padding: 11px 12px;
    outline: none;
    font-size: 14px;
    background: #fff;
    transition: .15s ease;
}
.cotizador-input:focus{
    border-color: rgba(178, 43, 39, .55);
    box-shadow: 0 0 0 4px rgba(178, 43, 39, .12);
}
.cotizador-help{ display:block; margin-top: 6px; font-size: 11.5px; color:#6b7280; }

.cotizador-result{
    grid-column: 2 / 3;
    grid-row: 1 / span 2;
    border-radius: 10px;
    padding: 12px;
    background: #fceede;
    border: 1px dashed rgba(178, 43, 39, .25);
}
.cotizador-kpi{
    display:flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 10px;
    padding: 7px 0;
    border-bottom: 1px solid rgba(0,0,0,.06);
}
.cotizador-kpi:last-child{ border-bottom: none; }
.cotizador-kpi span{ font-size: 12px; color:#4b5563; font-weight: 800; }
.cotizador-kpi strong{ font-size: 15px; color:#111827; font-weight: 900; white-space: nowrap; }
.cotizador-kpi-compact strong{ font-size: 14px; }

.cotizador-alert{
    margin-top: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(253, 230, 138, .45);
    border: 1px solid rgba(245, 158, 11, .35);
}
.cotizador-alert-title{ font-weight: 900; font-size: 12.5px; color:#92400e; margin-bottom: 3px; }
.cotizador-alert-body{ font-size: 12.5px; color:#78350f; }

@media (max-width:900px){
    .cotizador-grid{ grid-template-columns:1fr; }
    .cotizador-result{ grid-column:auto; grid-row:auto; }
    .cotizador-total{ text-align:left; }
    .cotizador-header{ flex-direction:column; gap:8px; }
}
</style>

<script>
const ES_ADMIN = @json($esAdmin);

// refs modales
const modalError = document.getElementById('modalError');
const modalExito = document.getElementById('modalExito');

function showError(msg){
    document.getElementById('modalErrorTxt').innerText = msg || 'Ocurrió un error.';
    modalError.style.display = 'flex';
}
function cerrarError(){ modalError.style.display = 'none'; }

function cerrarExito(){
    modalExito.style.display = 'none';

    // limpiar storage
    const keys = [
        "pedidoEspecial","fechaSolicitud","fechaEntrega",
        "pdf_solicitud","pdf_solicitud_nombre",
        "pdf_cotizacion","pdf_cotizacion_nombre",
        "pdf_autorizacion","pdf_autorizacion_nombre",
        "unidad_operativa_id","unidad_operativa_nombre"
    ];
    keys.forEach(k => localStorage.removeItem(k));

    window.location.href = "{{ route('dashboard.pedidos.consultar') }}";
}

function num(v, def = 0){
    const n = Number(v);
    return Number.isFinite(n) ? n : def;
}
function money(n){
    const v = Number(n);
    if(!Number.isFinite(v)) return '$0.00';
    return '$' + v.toFixed(2);
}
function escapeHtml(str){
    return String(str ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
}

/* ============================
   LocalStorage
============================ */
let productosLS = [];
try{
    productosLS = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");
    if(!Array.isArray(productosLS)) productosLS = [];
}catch(e){ productosLS = []; }

productosLS = (productosLS || []).map(p => {
    const descPresenta = p.descripcion ?? p.descripcion_contenido ?? '—';
    const descContenido = p.descripcion_contenido ?? (p.contenido ? `${descPresenta} - ${p.contenido}` : descPresenta);
    return {
        ...p,
        presentacion_id: p.presentacion_id ?? p.id ?? p.producto_id ?? null,
        producto: p.producto ?? p.nombre ?? '',
        marca: p.marca ?? '',
        descripcion_contenido: descContenido,
        unidad_contenido: p.unidad_contenido ?? p.unidad ?? '',
    };
});

const fechaSolicitud = (localStorage.getItem("fechaSolicitud") || '').trim();
const fechaEntrega   = (localStorage.getItem("fechaEntrega") || '').trim();

const unidadOperativaId = (localStorage.getItem("unidad_operativa_id") || '').trim();
const unidadOperativaNombre = (localStorage.getItem("unidad_operativa_nombre") || '').trim();

document.getElementById("fechaSolicitudTxt").innerText = fechaSolicitud || '—';
document.getElementById("fechaEntregaTxt").innerText   = fechaEntrega   || '—';

// ✅ mostrar unidad: si hay nombre (admin) o si existe aunque sea — (admin)
(function renderUnidad(){
    const wrap = document.getElementById('wrapUnidad');
    const txt = document.getElementById('unidadTxt');

    if(unidadOperativaNombre){
        wrap.style.display = '';
        txt.innerText = unidadOperativaNombre;
        return;
    }

    if(ES_ADMIN){
        wrap.style.display = '';
        txt.innerText = '—';
    }
})();

/* PDFs nombres */
document.getElementById("pdfClienteNombre").innerText =
    localStorage.getItem("pdf_solicitud_nombre") || "Sin archivo";

document.getElementById("pdfCotizacionNombre").innerText =
    localStorage.getItem("pdf_cotizacion_nombre") || "Sin archivo";

document.getElementById("pdfAceptacionNombre").innerText =
    localStorage.getItem("pdf_autorizacion_nombre") || "Sin archivo";

/* ===========================
   PDF Preview (BLOB URL)
=========================== */
function abrirPDF(key){
    const raw = (localStorage.getItem(key) || '').trim();
    if(!raw) return showError("PDF no cargado. Regresa y adjunta el documento.");

    let base64 = raw;
    let mime = "application/pdf";

    if(raw.startsWith("data:")){
        const parts = raw.split(",");
        if(parts.length < 2 || !parts[1] || !parts[1].trim()){
            return showError("PDF inválido (vacío). Vuelve a adjuntarlo.");
        }
        const m = parts[0].match(/data:(.*?);base64/i);
        if(m && m[1]) mime = m[1];
        base64 = parts[1].trim();
    }else{
        if(base64.length < 50) return showError("PDF inválido (vacío). Vuelve a adjuntarlo.");
    }

    try{
        const bytes = atob(base64);
        const arr = new Uint8Array(bytes.length);
        for(let i=0;i<bytes.length;i++) arr[i] = bytes.charCodeAt(i);
        const blob = new Blob([arr], {type:mime});
        const url = URL.createObjectURL(blob);
        window.open(url, "_blank");
        setTimeout(()=>URL.revokeObjectURL(url), 60000);
    }catch(e){
        console.error(e);
        return showError("PDF inválido o corrupto. Vuelve a adjuntarlo.");
    }
}

/* Tabla productos */
const tbody = document.getElementById("tbodyPrevio");
tbody.innerHTML = "";

let total = 0;

(productosLS || []).forEach(p => {
    const precio = num(p.precio, 0);
    const cantidad = num(p.cantidad, 0);
    const subtotal = num(p.subtotal, cantidad * precio);

    tbody.innerHTML += `
        <tr>
            <td>${escapeHtml(p.producto || '')}</td>
            <td>${escapeHtml(p.marca || '')}</td>
            <td>${escapeHtml(p.descripcion_contenido || '')}</td>
            <td>${escapeHtml(p.unidad_contenido || '')}</td>
            <td>${cantidad}</td>
            <td>${money(precio)}</td>
            <td>${money(subtotal)}</td>
        </tr>
    `;
    total += subtotal;
});

document.getElementById("totalGeneral").innerText = total.toFixed(2);

/* ===========================
   Cotizador
=========================== */
(function initCotizador(){
    const cotTotal = document.getElementById('cotTotalDinero');
    const inpCom = document.getElementById('comensalesEstimados');
    const inpDes = document.getElementById('costoDeseado');

    const kpiActual = document.getElementById('kpiActual');
    const kpiDiffWrap = document.getElementById('kpiDiffWrap');
    const kpiDiffLabel = document.getElementById('kpiDiffLabel');
    const kpiDiff = document.getElementById('kpiDiff');

    const kpiExcesoWrap = document.getElementById('kpiExcesoWrap');
    const kpiExcesoLabel = document.getElementById('kpiExcesoLabel');
    const kpiExceso = document.getElementById('kpiExceso');

    const alertAjuste = document.getElementById('alertAjuste');
    const kpiReducir = document.getElementById('kpiReducir');

    const moneyMx = (n) => {
        if(!isFinite(n)) return '—';
        return '$ ' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    cotTotal.textContent = moneyMx(total);

    function reset(){
        kpiActual.textContent = '—';
        kpiDiffWrap.style.display = 'none';
        kpiExcesoWrap.style.display = 'none';
        alertAjuste.style.display = 'none';
    }

    function render(){
        reset();

        const com = parseInt(inpCom.value || '0', 10);
        const deseado = parseFloat(inpDes.value || '0');

        if(!com || com <= 0) return;

        const actual = total / com;
        kpiActual.textContent = moneyMx(actual);

        if(!isFinite(deseado) || deseado <= 0) return;

        const diff = actual - deseado;
        const deltaTotal = diff * com;

        kpiDiffWrap.style.display = '';
        kpiExcesoWrap.style.display = '';

        if(diff > 0){
            kpiDiffLabel.textContent = 'Te excedes por (por comensal)';
            kpiExcesoLabel.textContent = 'Exceso total';
        }else if(diff < 0){
            kpiDiffLabel.textContent = 'Vas por debajo de (por comensal)';
            kpiExcesoLabel.textContent = 'Ahorro vs objetivo';
        }else{
            kpiDiffLabel.textContent = 'Diferencia';
            kpiExcesoLabel.textContent = 'Diferencia total';
        }

        kpiDiff.textContent = moneyMx(Math.abs(diff));
        kpiExceso.textContent = moneyMx(Math.abs(deltaTotal));

        if(diff > 0){
            alertAjuste.style.display = '';
            kpiReducir.textContent = moneyMx(deltaTotal);
        }
    }

    inpCom.addEventListener('input', render);
    inpDes.addEventListener('input', render);
    render();
})();

/* ============================
   Base64 => File (acepta DataURL o crudo)
============================ */
function base64ToFile(data, filename){
    const raw = String(data || '').trim();
    if(!raw) return null;

    let base64 = raw;
    let mime = 'application/pdf';

    if(raw.startsWith('data:')){
        const arr = raw.split(',');
        if(arr.length < 2 || !arr[1] || !arr[1].trim()) return null;
        const m = arr[0].match(/data:(.*?);base64/i);
        if(m && m[1]) mime = m[1];
        base64 = arr[1].trim();
    }

    try{
        const bstr = atob(base64);
        const u8arr = new Uint8Array(bstr.length);
        for(let i=0;i<bstr.length;i++) u8arr[i] = bstr.charCodeAt(i);
        return new File([u8arr], filename, { type: mime });
    }catch(e){
        console.error(e);
        return null;
    }
}

/* ============================
   Confirmar => backend
============================ */
function confirmarPedido() {
    // Validaciones rápidas
    if (!fechaSolicitud || !fechaEntrega) {
        return showError('Faltan fechas. Regresa y captura fecha de solicitud y entrega.');
    }
    if (fechaEntrega < fechaSolicitud) {
        return showError('La fecha de entrega no puede ser menor a la fecha de solicitud.');
    }
    if (!Array.isArray(productosLS) || productosLS.length === 0) {
        return showError('No hay productos en el pedido especial.');
    }

    const pdf1 = localStorage.getItem("pdf_solicitud");
    const pdf2 = localStorage.getItem("pdf_cotizacion");
    const pdf3 = localStorage.getItem("pdf_autorizacion");

    if (!pdf1 || !pdf2 || !pdf3) {
        return showError('Faltan PDFs. Regresa y adjunta los 3 documentos.');
    }

    if (ES_ADMIN && !unidadOperativaId) {
        return showError('Falta seleccionar unidad operativa. Regresa y selecciona una unidad.');
    }

    // Productos payload (solo lo que ocupa backend)
    const productosPayload = (productosLS || []).map(p => ({
        presentacion_id: parseInt(p.presentacion_id, 10) || null,
        producto_proveedor_id: parseInt(p.producto_proveedor_id, 10) || null,
        cantidad: num(p.cantidad, 0),
        precio: num(p.precio, 0),
    }));

    // Validar ids mínimos (evita guardar incompleto)
    const sinPresentacion = productosPayload.filter(x => !x.presentacion_id);
    if(sinPresentacion.length){
        return showError('Hay productos sin presentacion_id. Regresa y vuelve a agregarlos.');
    }
    const sinPP = productosPayload.filter(x => !x.producto_proveedor_id);
    if(sinPP.length){
        return showError('Hay productos sin proveedor asignado. Regresa y vuelve a agregarlos.');
    }

    // FormData con PDFs
    const form = new FormData();
    form.append("fecha_solicitud", fechaSolicitud);
    form.append("fecha_entrega", fechaEntrega);
    form.append("productos", JSON.stringify(productosPayload));

    if (ES_ADMIN && unidadOperativaId) {
        form.append("unidad_operativa_id", unidadOperativaId);
    }

    const f1 = base64ToFile(pdf1, localStorage.getItem("pdf_solicitud_nombre") || "solicitud.pdf");
    const f2 = base64ToFile(pdf2, localStorage.getItem("pdf_cotizacion_nombre") || "cotizacion.pdf");
    const f3 = base64ToFile(pdf3, localStorage.getItem("pdf_autorizacion_nombre") || "aceptacion.pdf");

    if(!f1 || !f2 || !f3){
        return showError('Error leyendo los PDFs. Vuelve a adjuntarlos.');
    }

    form.append("pdf_solicitud", f1);
    form.append("pdf_cotizacion", f2);
    form.append("pdf_autorizacion", f3);

    fetch("{{ route('dashboard.pedidos.especial.guardar') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: form
    })
    .then(async res => {
        const json = await res.json().catch(() => null);
        if(!res.ok){
            throw new Error(json?.message || json?.error || 'Respuesta HTTP no válida.');
        }
        return json;
    })
    .then(json => {
        if (json?.success) {
            document.getElementById('codigoReal').textContent = json.codigo || '—';
            modalExito.style.display = 'flex';
            return;
        }
        showError(json?.message || json?.error || 'No se pudo guardar.');
    })
    .catch(e => {
        console.error(e);
        showError(e?.message || 'No se pudo conectar con el servidor.');
    });
}

/* ============================
   Regresar
============================ */
function regresar() {
    window.location.href = "{{ route('dashboard.pedidos.especial.crear') }}";
}
</script>

@endsection
