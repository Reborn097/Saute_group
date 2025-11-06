@extends('layouts.dashboard')

@section('titulo', 'Previsualización del Pedido')

@section('contenido')
<div class="contenedor">

    <div class="cabecera">
        <button class="btn-volver" onclick="window.location.href='{{ route('dashboard.pedidos.solicitar') }}'">Regresar</button>

    
        <div class="numero-pedido">Número de pedido: <b id="numeroPedido">#SPJ{{ rand(1000,9999) }}</b></div>
    </div>

    <div class="info-fechas">
        <p><b>Fecha de solicitud:</b> <span id="fechaSolicitudTexto">-</span></p>
        <p><b>Fecha de entrega:</b> <span id="fechaEntregaTexto">-</span></p>
    </div>

    {{-- Tabla de productos --}}
    <div class="tabla-contenedor">
        <table class="tabla-productos" id="tablaPrevisualizacion">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Unidad de medida</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="productosPrevisualizacion">
                <tr><td colspan="6" style="text-align:center;">Cargando productos...</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Totales --}}
    <div class="resumen">
        <p><b>Total:</b> $<span id="totalPedido">0.00</span></p>
    </div>

    {{-- Cálculo por comensales --}}
    <div class="calculo">
        <label>Cantidad de comensales:</label>
        <input type="number" id="comensales" min="1" value="1">
        <button class="btn-calcular" onclick="calcularCosto()">Calcular costo</button>

        <div class="resultado">
            <p><b>Costo total:</b> $<span id="costoTotal">0.00</span></p>
            <p><b>Costo por comensal:</b> $<span id="costoComensal">0.00</span></p>
        </div>
    </div>

    {{-- Confirmar --}}
    <div class="acciones">
        <button class="btn-confirmar">Confirmar pedido</button>
    </div>
</div>

<style>
.contenedor {
    background-color: #fae7d0;
    padding: 25px 35px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    max-width: 1100px;
    margin: 0 auto;
}
.cabecera {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.numero-pedido {
    font-size: 1.1em;
}
.info-fechas {
    background-color: #fff8f0;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.tabla-contenedor {
    overflow-x: auto;
    margin-bottom: 25px;
}
table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
}
th, td {
    border: 1px solid #aaa;
    padding: 10px;
    text-align: left;
}
th {
    background-color: #f0f0f0;
}
.resumen {
    text-align: right;
    font-size: 1.1em;
    margin-top: 10px;
    margin-bottom: 20px;
}
.calculo {
    background-color: #fff8f0;
    padding: 15px;
    border-radius: 8px;
    width: 350px;
}
.calculo input {
    margin-top: 8px;
    margin-bottom: 10px;
    width: 100%;
}
.btn-calcular, .btn-confirmar, .btn-volver {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 10px;
}
.btn-calcular:hover, .btn-confirmar:hover, .btn-volver:hover {
    background-color: #911f1d;
}
.acciones {
    text-align: right;
    margin-top: 30px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const productos = JSON.parse(localStorage.getItem('pedidoActual')) || [];
    const fechaSolicitud = localStorage.getItem('fechaSolicitud');
    const fechaEntrega = localStorage.getItem('fechaEntrega');
    const cuerpo = document.getElementById('productosPrevisualizacion');
    let total = 0;

    // Mostrar fechas
    document.getElementById('fechaSolicitudTexto').textContent = fechaSolicitud || '-';
    document.getElementById('fechaEntregaTexto').textContent = fechaEntrega || '-';

    cuerpo.innerHTML = "";

    if (productos.length === 0) {
        const fila = document.createElement('tr');
        fila.innerHTML = `<td colspan="6" style="text-align:center;">No hay productos en el pedido.</td>`;
        cuerpo.appendChild(fila);
        return;
    }

    productos.forEach(p => {
        const precio = parseFloat(p.precio || p.precio_unitario || 0);
        const cantidad = parseFloat(p.cantidad || 0);
        const subtotal = precio * cantidad;
        total += subtotal;

        const fila = `
            <tr>
                <td>${p.nombre}</td>
                <td>${p.categoria}</td>
                <td>${p.unidad}</td>
                <td>${p.cantidad}</td>
                <td>$${precio.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
            </tr>
        `;
        cuerpo.innerHTML += fila;
    });

    document.getElementById('totalPedido').innerText = total.toFixed(2);
    document.getElementById('costoTotal').innerText = total.toFixed(2);

    // Cálculo por comensal
    window.calcularCosto = function() {
        const comensales = parseInt(document.getElementById('comensales').value);
        if (comensales > 0) {
            const costoComensal = total / comensales;
            document.getElementById('costoComensal').innerText = costoComensal.toFixed(2);
        }
    };

    // Confirmar pedido
    document.querySelector('.btn-confirmar').addEventListener('click', () => {
        const productos = JSON.parse(localStorage.getItem('pedidoActual')) || [];
        const fechaSolicitud = localStorage.getItem('fechaSolicitud');
        const fechaEntrega = localStorage.getItem('fechaEntrega');

        if (productos.length === 0) {
            alert("⚠️ No hay productos en el pedido.");
            return;
        }

        fetch("{{ route('dashboard.pedidos.guardar') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                fecha_solicitud: fechaSolicitud,
                fecha_entrega: fechaEntrega,
                productos: productos
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Respuesta HTTP no válida: " + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert("✅ Pedido registrado correctamente.");
                // Limpiar datos
                localStorage.removeItem('pedidoActual');
                localStorage.removeItem('fechaSolicitud');
                localStorage.removeItem('fechaEntrega');
                // Redirigir a consulta de pedidos
                window.location.href = "{{ route('dashboard.pedidos.consultar') }}";
            } else {
                alert("❌ Error al guardar el pedido. Verifica los datos.");
                console.error(data);
            }
        })
        .catch(err => {
            console.error("Error al enviar pedido:", err);
            alert("❌ Error en la conexión con el servidor. Revisa el backend.");
        });
    });
});
</script>

@endsection
