@extends('layouts.dashboard')

@section('titulo', 'Previsualización del Pedido Especial')

@section('contenido')

<div class="contenedor">

    <button class="btn-menu" onclick="regresar()">Regresar</button>

    <h2>Previsualización del Pedido Especial</h2>

    <p><strong>Número de pedido:</strong> <span id="codigoPedido"></span></p>

    <p><strong>Fecha de solicitud:</strong> <span id="fechaSolicitudTxt"></span></p>
    <p><strong>Fecha de entrega:</strong> <span id="fechaEntregaTxt"></span></p>


    {{-- ============================
            PDFS SUBIDOS
    ============================= --}}
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
                    <td><button class="btn" onclick="verPDF('cliente')">Ver PDF</button></td>
                </tr>
                <tr>
                    <td>PDF cotización</td>
                    <td id="pdfCotizacionNombre"></td>
                    <td><button class="btn" onclick="verPDF('cotizacion')">Ver PDF</button></td>
                </tr>
                <tr>
                    <td>PDF aceptación</td>
                    <td id="pdfAceptacionNombre"></td>
                    <td><button class="btn" onclick="verPDF('aceptacion')">Ver PDF</button></td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- ============================
            TABLA DEL PEDIDO
    ============================= --}}
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
        <button class="btn-confirmar" onclick="enviarPedidoEspecial()">Confirmar pedido especial</button>
    </div>

</div>


{{-- MODALES --}}
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

.btn-menu, .btn-confirmar, .btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    cursor:pointer;
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
</style>


<script>
let productos = JSON.parse(localStorage.getItem('pedidoActual') || '[]');
let fechaSolicitud = localStorage.getItem('fechaSolicitud');
let fechaEntrega = localStorage.getItem('fechaEntrega');

let pdfCliente = localStorage.getItem('pdf_cliente_nombre');
let pdfCotizacion = localStorage.getItem('pdf_cotizacion_nombre');
let pdfAceptacion = localStorage.getItem('pdf_aceptacion_nombre');

document.getElementById('codigoPedido').innerText = "#SPJ" + Math.floor(Math.random()*9000+1000);

document.getElementById('fechaSolicitudTxt').innerText = fechaSolicitud;
document.getElementById('fechaEntregaTxt').innerText = fechaEntrega;

document.getElementById('pdfClienteNombre').innerText = pdfCliente;
document.getElementById('pdfCotizacionNombre').innerText = pdfCotizacion;
document.getElementById('pdfAceptacionNombre').innerText = pdfAceptacion;

// Mostrar tabla de productos
const tbody = document.getElementById('tbodyPrevio');
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

document.getElementById('totalGeneral').innerText = total.toFixed(2);


// -------------------------
//  VISUALIZAR PDF
// -------------------------
function verPDF(tipo){
    let archivo = localStorage.getItem("pdf_" + tipo);

    if(!archivo){
        alert("No se cargó este PDF.");
        return;
    }

    window.open(archivo, "_blank");
}


// -------------------------
//  ENVIAR PEDIDO ESPECIAL
// -------------------------
function enviarPedidoEspecial(){

    let formData = new FormData();

    formData.append("fecha_solicitud", fechaSolicitud);
    formData.append("fecha_entrega", fechaEntrega);
    formData.append("productos", JSON.stringify(productos));

    // Adjuntar PDFs reales (paths guardados en localStorage)
    formData.append("pdf_cliente", localStorage.getItem("pdf_cliente_file"));
    formData.append("pdf_cotizacion", localStorage.getItem("pdf_cotizacion_file"));
    formData.append("pdf_aceptacion", localStorage.getItem("pdf_aceptacion_file"));

    fetch("{{ route('dashboard.pedidos.especial.guardar') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
        },
        body: formData
    })
    .then(r => r.json())
    .then(json => {
        if(json.success){
            alert("Pedido especial guardado correctamente");
            localStorage.clear();
            window.location.href = "{{ route('dashboard.pedidos.consultar') }}";
        }
    })
    .catch(err => {
        console.error(err);
        modalError.style.display = "flex";
    });
}

function regresar(){
    window.location.href = "{{ route('dashboard.pedidos.especial.crear') }}";
}
</script>

@endsection
