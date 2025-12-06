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

    <h3>Total: $<span id="totalGeneral">0.00</span></h3>

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

.btn-menu, .btn-confirmar, .btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    cursor:pointer;
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


/* ============================
    VER PDF DESDE BASE64
============================ */

function abrirPDF(key) {
    const base64 = localStorage.getItem(key);

    if (!base64) return alert("PDF no cargado");

    // Abrir en nueva pestaña
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
        body: form    // ← CORREGIDO
    })
    .then(res => res.json())
    .then(json => {
        if (json.success) {
            alert("Pedido especial guardado correctamente");

            // Limpiar solo lo del pedido especial
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
