@extends('layouts.dashboard')

@section('titulo', 'Previsualización del Pedido')

@section('contenido')

<div class="contenedor">

    <button class="btn-menu" onclick="regresar()">Regresar</button>

    <h2>Previsualización del Pedido</h2>

    <p><strong>Número de pedido:</strong> <span id="codigoPedido"></span></p>

    <p><strong>Fecha de solicitud:</strong> <span id="fechaSolicitudTxt"></span></p>
    <p><strong>Fecha de entrega:</strong> <span id="fechaEntregaTxt"></span></p>


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
        <button class="btn-confirmar" onclick="enviarPedido()">Confirmar pedido</button>
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

{{-- MODAL ÉXITO --}}
<div id="modalExito" class="modal">
    <div class="modal-contenido">
        <h3 style="color:#2a7a2a;">✔ Pedido guardado</h3>
        <p>El pedido se guardó correctamente.</p>

        <button class="btn" onclick="cerrarExito()">Aceptar</button>
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
let productos = JSON.parse(localStorage.getItem('pedidoActual') || '[]');
let fechaSolicitud = localStorage.getItem('fechaSolicitud');
let fechaEntrega = localStorage.getItem('fechaEntrega');

// generar código de pedido visual
document.getElementById('codigoPedido').innerText = "#SPJ" + Math.floor(Math.random()*9000+1000);

document.getElementById('fechaSolicitudTxt').innerText = fechaSolicitud;
document.getElementById('fechaEntregaTxt').innerText = fechaEntrega;

const tbody = document.getElementById('tbodyPrevio');
tbody.innerHTML = "";

let total = 0;

productos.forEach(p => {
    const fila = `
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
    tbody.innerHTML += fila;
});

document.getElementById('totalGeneral').innerText = total.toFixed(2);



function enviarPedido(){

    const data = {
        fecha_solicitud: fechaSolicitud,
        fecha_entrega: fechaEntrega,
        productos: productos
    };

    console.log("Datos enviados al backend:", data);

    fetch("{{ route('dashboard.pedidos.guardar') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify(data)
    })
    .then(resp => {
        if(!resp.ok) throw new Error("Respuesta HTTP no válida");
        return resp.json();
    })
    .then(json => {
        if(json.success){
            localStorage.clear();
            alert("Pedido guardado correctamente");
            window.location.href = "{{ route('dashboard.pedidos.consultar') }}";
        }
    })
    .catch(err => {
        console.error("Error al enviar pedido:", err);
        modalError.style.display = "flex";
    });
}

function cerrarError(){
    modalError.style.display = "none";
}

function regresar(){
    window.location.href = "{{ route('dashboard.pedidos.solicitar') }}";
}
</script>

@endsection
