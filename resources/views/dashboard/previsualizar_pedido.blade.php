@extends('layouts.dashboard')

@section('titulo', 'Previsualización del Pedido')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    $esAdmin = in_array($role, ['admin', 'encargado_pedidos'], true);
@endphp

<div class="contenedor">

    <button type="button" class="btn-menu" onclick="regresar()">Regresar</button>

    <h2>Previsualización del Pedido</h2>

    {{-- ✅ El backend asigna el código real (ENE26001...) --}}
    <p><strong>Número de pedido:</strong> <span id="codigoPedido">Se asignará al guardar</span></p>

    <p><strong>Fecha de solicitud:</strong> <span id="fechaSolicitudTxt"></span></p>
    <p><strong>Fecha de entrega:</strong> <span id="fechaEntregaTxt"></span></p>

    {{-- ✅ Mostrar unidad (solo para admin o si existe en localStorage) --}}
    <p id="unidadWrap" style="display:none;">
        <strong>Unidad operativa:</strong>
        <span id="unidadTxt"></span>
    </p>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Descripción - Contenido</th>
                    <th>Unidad contenido</th>
                    <th>Cantidad</th>
                    @if($esAdmin)
                        <th>Proveedor</th>
                    @endif
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
        (antes de Confirmar)
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
                    <div class="cotizador-alert-title" id="alertTitle">Para cumplir el objetivo</div>
                    <div class="cotizador-alert-body">
                        Debes reducir el pedido en:
                        <strong id="kpiReducir">—</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="acciones-final">
        <button type="button" class="btn-confirmar" onclick="enviarPedido()">Confirmar pedido</button>
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
        <p>El pedido se guardó correctamente.</p>
        <p><strong>Código:</strong> <span id="codigoReal"></span></p>

        <button type="button" class="btn" onclick="cerrarExito()">Aceptar</button>
    </div>
</div>


<style>
.contenedor{
    background:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

.total-titulo{
    margin-top: 18px;
    margin-bottom: 10px;
}

/* Tabla */
.tabla{
    width:100%;
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
}
.tabla td{
    padding:10px;
    text-align:center;
}

/* Botones */
.btn-menu, .btn-confirmar, .btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    cursor:pointer;
}
.acciones-final{
    margin-top: 12px;
}

/* Modal */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
    z-index:900;
}
.modal-contenido{
    background:white;
    padding:30px;
    border-radius:12px;
    text-align:center;
    width:350px;
}

/* ===========================
   Cotizador (acorde a la vista)
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

.cotizador-title{
    display:flex;
    gap:10px;
    align-items:flex-start;
}

.cotizador-title h3{
    margin:0;
    font-size: 16px;
    font-weight: 900;
    color:#1f2937;
    letter-spacing: .2px;
}

.cotizador-title p{
    margin:3px 0 0;
    font-size: 12.5px;
    color:#6b7280;
    line-height: 1.35;
}

.cotizador-icon{
    width:34px;height:34px;
    display:grid;place-items:center;
    border-radius: 10px;
    background: rgba(178, 43, 39, .10);
    font-size: 18px;
}

.cotizador-total{
    text-align:right;
    min-width: 170px;
}
.cotizador-total span{
    display:block;
    font-size: 12px;
    color:#6b7280;
    margin-bottom: 2px;
}
.cotizador-total strong{
    display:block;
    font-size: 16px;
    font-weight: 900;
    color:#111827;
}

.cotizador-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items: stretch;
}

/* Panel derecho ocupa las dos filas */
.cotizador-result{
    grid-column: 2 / 3;
    grid-row: 1 / span 2;
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
.cotizador-help{
    display:block;
    margin-top: 6px;
    font-size: 11.5px;
    color:#6b7280;
}

.cotizador-result{
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

.cotizador-kpi span{
    font-size: 12px;
    color:#4b5563;
    font-weight: 800;
}
.cotizador-kpi strong{
    font-size: 15px;
    color:#111827;
    font-weight: 900;
    white-space: nowrap;
}

.cotizador-kpi-compact strong{ font-size: 14px; }

.cotizador-alert{
    margin-top: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(253, 230, 138, .45);
    border: 1px solid rgba(245, 158, 11, .35);
}
.cotizador-alert-title{
    font-weight: 900;
    font-size: 12.5px;
    color:#92400e;
    margin-bottom: 3px;
}
.cotizador-alert-body{
    font-size: 12.5px;
    color:#78350f;
}

@media (max-width: 980px){
    .cotizador-grid{ grid-template-columns: 1fr; }
    .cotizador-total{ text-align:left; }
    .cotizador-header{ flex-direction: column; }
}
</style>


<script>
const ES_ADMIN = @json($esAdmin);

// ✅ refs modales (evita "modalError is not defined")
const modalError = document.getElementById('modalError');
const modalExito = document.getElementById('modalExito');

// ===== leer LS
let productos = [];
try {
  productos = JSON.parse(localStorage.getItem('pedidoActual') || '[]');
  if(!Array.isArray(productos)) productos = [];
} catch(e){ productos = []; }

let fechaSolicitud = localStorage.getItem('fechaSolicitud') || '';
let fechaEntrega = localStorage.getItem('fechaEntrega') || '';

// ✅ unidad operativa (solo importante si ES_ADMIN)
let unidadOperativaId = localStorage.getItem('unidad_operativa_id') || '';
let unidadOperativaNombre = localStorage.getItem('unidad_operativa_nombre') || '';

function money(n){
  n = Number(n);
  if(!isFinite(n)) n = 0;
  return n.toFixed(2);
}
function num(n, def=0){
  n = Number(n);
  return isFinite(n) ? n : def;
}

// Render fechas
document.getElementById('fechaSolicitudTxt').innerText = fechaSolicitud || '—';
document.getElementById('fechaEntregaTxt').innerText = fechaEntrega || '—';

// Render unidad si existe / admin
(function renderUnidad(){
    const wrap = document.getElementById('unidadWrap');
    const txt = document.getElementById('unidadTxt');

    if (unidadOperativaNombre) {
        wrap.style.display = '';
        txt.textContent = unidadOperativaNombre;
    } else if (ES_ADMIN) {
        wrap.style.display = '';
        txt.textContent = '—';
    }
})();

// Render tabla
const tbody = document.getElementById('tbodyPrevio');
tbody.innerHTML = "";

let total = 0;

(productos || []).forEach(p => {
    const precio = num(p.precio, 0);
    const cantidad = num(p.cantidad, 0);

    // ✅ subtotal normalizado (por si no viene)
    const subtotal = num(p.subtotal, (precio * cantidad));

    const fila = `
        <tr>
            <td>${p.producto ?? ''}</td>
            <td>${p.marca ?? ''}</td>
            <td>${p.descripcion_contenido ?? ''}</td>
            <td>${p.unidad_contenido ?? ''}</td>
            <td>${cantidad}</td>
            ${ES_ADMIN ? `<td>${p.proveedor ?? ''}</td>` : ``}
            <td>$${money(precio)}</td>
            <td>$${money(subtotal)}</td>
        </tr>
    `;
    total += subtotal;
    tbody.innerHTML += fila;
});

document.getElementById('totalGeneral').innerText = money(total);

// ===========================
// Cotizador por comensal (JS)
// ===========================
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

function showError(msg){
    document.getElementById('modalErrorTxt').textContent = msg || 'Ocurrió un error.';
    modalError.style.display = "flex";
}

function enviarPedido(){

    if(!fechaSolicitud || !fechaEntrega){
        showError('Faltan fechas. Regresa y selecciona fecha de solicitud y entrega.');
        return;
    }
    if(fechaEntrega < fechaSolicitud){
        showError('La fecha de entrega no puede ser menor a la fecha de solicitud.');
        return;
    }

    if(!productos || productos.length === 0){
        showError('No hay productos en el pedido.');
        return;
    }

    // ✅ Si admin: exigir unidad seleccionada
    if(ES_ADMIN && !unidadOperativaId){
        showError('Debes seleccionar una unidad operativa antes de confirmar.');
        return;
    }

    // ✅ Validar proveedor + cantidad > 0
    const invalidos = (productos || []).filter(p => !p.producto_proveedor_id);
    if (invalidos.length > 0) {
        showError('Hay productos sin proveedor asignado. Regresa y vuelve a agregarlos.');
        return;
    }

    const cantCero = (productos || []).filter(p => num(p.cantidad, 0) <= 0);
    if (cantCero.length > 0) {
        showError('Hay productos con cantidad 0. Ajusta cantidades antes de confirmar.');
        return;
    }

    const productosPayload = (productos || []).map(p => ({
        presentacion_id: parseInt(p.presentacion_id, 10),
        producto_proveedor_id: parseInt(p.producto_proveedor_id, 10),
        cantidad: num(p.cantidad, 0),
        precio: num(p.precio, 0),
    }));

    const payload = {
        fecha_solicitud: fechaSolicitud,
        fecha_entrega: fechaEntrega,
        productos: productosPayload
    };

    if(ES_ADMIN && unidadOperativaId){
        payload.unidad_operativa_id = parseInt(unidadOperativaId, 10);
    }

    fetch("{{ route('dashboard.pedidos.guardar') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify(payload)
    })
    .then(async resp => {
        const json = await resp.json().catch(() => null);
        if(!resp.ok){
            throw new Error(json?.message || 'Respuesta HTTP no válida.');
        }
        return json;
    })
    .then(json => {
        if(json && json.success){
            document.getElementById('codigoReal').textContent = json.codigo || '—';
            modalExito.style.display = "flex";
            return;
        }
        showError(json?.message || 'No se pudo guardar el pedido.');
    })
    .catch(err => {
        console.error("Error al enviar pedido:", err);
        showError(err?.message || 'No se pudo conectar con el servidor.');
    });
}

function cerrarError(){
    modalError.style.display = "none";
}

function cerrarExito(){
    modalExito.style.display = "none";

    localStorage.removeItem('pedidoActual');
    localStorage.removeItem('fechaSolicitud');
    localStorage.removeItem('fechaEntrega');
    localStorage.removeItem('unidad_operativa_id');
    localStorage.removeItem('unidad_operativa_nombre');

    window.location.href = "{{ route('dashboard.pedidos.consultar') }}";
}

function regresar(){
    window.location.href = "{{ route('dashboard.pedidos.solicitar') }}";
}
</script>

@endsection
