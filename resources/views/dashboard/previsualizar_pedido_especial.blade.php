@extends('layouts.dashboard')

@section('titulo', 'Previsualización del Pedido Especial')

@section('contenido')

<div class="contenedor">

    <button class="btn-menu" onclick="regresar()">Regresar</button>

    <p><strong>Número de pedido:</strong> <span id="codigoPedido"></span></p>

    <p><strong>Fecha de solicitud:</strong> <span id="fechaSolicitudTxt"></span></p>
    <p><strong>Fecha de entrega:</strong> <span id="fechaEntregaTxt"></span></p>

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
                    <td><button class="btn" onclick="abrirPDF('pdf_solicitud')">Ver PDF</button></td>
                </tr>
                <tr>
                    <td>PDF cotización</td>
                    <td id="pdfCotizacionNombre"></td>
                    <td><button class="btn" onclick="abrirPDF('pdf_cotizacion')">Ver PDF</button></td>
                </tr>
                <tr>
                    <td>PDF aceptación</td>
                    <td id="pdfAceptacionNombre"></td>
                    <td><button class="btn" onclick="abrirPDF('pdf_autorizacion')">Ver PDF</button></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="titulo-seccion">Productos en el pedido</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Unidad</th>
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
        <button class="btn-confirmar" onclick="confirmarPedido()">Confirmar pedido especial</button>
    </div>

</div>


{{-- MODAL ERROR --}}
<div id="modalError" class="modal">
    <div class="modal-contenido">
        <h3 style="color:#b22b27;">✖ Error de conexión</h3>
        <p>No se pudo conectar con el servidor.</p>
        <button class="btn" onclick="cerrarError()">Aceptar</button>
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

/* Botones */
.btn-menu, .btn-confirmar, .btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    cursor:pointer;
}

/* ===========================
   Cotizador (cuidado en estilos)
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

/* GRID: 2 columnas + panel lateral fijo (evita encimados) */
.cotizador-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items: stretch;
}

.cotizador-field{
    display:flex;
    flex-direction:column;
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
    height: 42px;
    box-sizing: border-box;
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

/* Panel derecho ocupa las 2 filas */
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

/* Responsive del cotizador (por si se abre en pantallas chicas) */
@media (max-width: 900px){
    .cotizador-grid{
        grid-template-columns: 1fr;
    }

    .cotizador-result{
        grid-column: auto;
        grid-row: auto;
    }

    .cotizador-total{
        text-align:left;
    }

    .cotizador-header{
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<script>
/* ============================
    CARGAR INFORMACIÓN
============================ */

let productos = JSON.parse(localStorage.getItem("pedidoEspecial") || "[]");

let fechaSolicitud = localStorage.getItem("fechaSolicitud");
let fechaEntrega = localStorage.getItem("fechaEntrega");

document.getElementById("fechaSolicitudTxt").innerText = fechaSolicitud;
document.getElementById("fechaEntregaTxt").innerText = fechaEntrega;

document.getElementById("codigoPedido").innerText = "#SPJ" + Math.floor(Math.random()*9000+1000);

document.getElementById("pdfClienteNombre").innerText =
    localStorage.getItem("pdf_solicitud_nombre") || "Sin archivo";

document.getElementById("pdfCotizacionNombre").innerText =
    localStorage.getItem("pdf_cotizacion_nombre") || "Sin archivo";

document.getElementById("pdfAceptacionNombre").innerText =
    localStorage.getItem("pdf_autorizacion_nombre") || "Sin archivo";

/* TABLA PRODUCTOS */
const tbody = document.getElementById("tbodyPrevio");
tbody.innerHTML = "";

let total = 0;

productos.forEach(p => {
    tbody.innerHTML += `
        <tr>
            <td>${p.nombre}</td>
            <td>${p.categoria}</td>
            <td>${p.unidad}</td>
            <td>${p.cantidad}</td>
            <td>$${p.precio.toFixed(2)}</td>
            <td>$${p.subtotal.toFixed(2)}</td>
        </tr>
    `;
    total += p.subtotal;
});

document.getElementById("totalGeneral").innerText = total.toFixed(2);


/* ===========================
   Cotizador por comensal (JS)
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

    const money = (n) => {
        if(!isFinite(n)) return '—';
        return '$ ' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    cotTotal.textContent = money(total);

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
        kpiActual.textContent = money(actual);

        if(!isFinite(deseado) || deseado <= 0) return;

        const diff = actual - deseado; // + => te pasas; - => vas abajo
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

        kpiDiff.textContent = money(Math.abs(diff));
        kpiExceso.textContent = money(Math.abs(deltaTotal));

        if(diff > 0){
            alertAjuste.style.display = '';
            kpiReducir.textContent = money(deltaTotal);
        }
    }

    inpCom.addEventListener('input', render);
    inpDes.addEventListener('input', render);

    render();
})();


/* ============================
    VER PDF DESDE BASE64
============================ */

function abrirPDF(key) {
    const base64 = localStorage.getItem(key);

    if (!base64) return alert("PDF no cargado");

    const win = window.open("");
    win.document.write(`
        <iframe width="100%" height="100%" src="${base64}"></iframe>
    `);
}


/* ============================
    ENVIAR A BACKEND
============================ */
function confirmarPedido() {

    const form = new FormData();

    form.append("fecha_solicitud", fechaSolicitud);
    form.append("fecha_entrega", fechaEntrega);
    form.append("productos", JSON.stringify(productos));

    // PDFS en base64
    form.append("pdf_solicitud", localStorage.getItem("pdf_solicitud"));
    form.append("pdf_cotizacion", localStorage.getItem("pdf_cotizacion"));
    form.append("pdf_autorizacion", localStorage.getItem("pdf_autorizacion"));

    fetch("{{ route('dashboard.pedidos.especial.guardar') }}", {
        method: "POST",
        headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        body: form
    })
    .then(res => res.json())
    .then(json => {
        if (json.success) {
            alert("Pedido especial guardado correctamente");

            localStorage.removeItem("pedidoEspecial");
            localStorage.removeItem("fechaSolicitud");
            localStorage.removeItem("fechaEntrega");
            localStorage.removeItem("pdf_solicitud");
            localStorage.removeItem("pdf_solicitud_nombre");
            localStorage.removeItem("pdf_cotizacion");
            localStorage.removeItem("pdf_cotizacion_nombre");
            localStorage.removeItem("pdf_autorizacion");
            localStorage.removeItem("pdf_autorizacion_nombre");

            window.location.href = "{{ route('dashboard.pedidos.consultar') }}";
        } else {
            alert("Error: " + json.error);
        }
    })
    .catch(e => {
        console.error(e);
        modalError.style.display = "flex";
    });
}


/* ============================
    REGRESAR
============================ */
function regresar() {
    window.location.href = "{{ route('dashboard.pedidos.especial.crear') }}";
}
</script>

@endsection
