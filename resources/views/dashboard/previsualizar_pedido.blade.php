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
        <table class="tabla-pedidos">
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
        <label><b>Cantidad de comensales:</b></label>
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

{{-- Modal de mensaje estilizado --}}
<div id="modalMensaje" class="modal">
    <div class="modal-contenido">
        <h3 id="tituloMensaje" style="margin-bottom:6px;"></h3>
        <p id="textoMensaje"></p>
        <div class="modal-acciones">
            <button class="btn" onclick="cerrarModalMensaje()">Aceptar</button>
        </div>
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
.info-fechas {
    background-color: #fff8f0;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.numero-pedido { font-size: 1.1em; font-weight: 600; }

/* Diseño igual al de “Crear Pedido” */
.tabla-pedidos {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}
.tabla-pedidos th {
    background-color: #b22b27;
    color: white;
    padding: 10px;
    text-align: center;
}
.tabla-pedidos td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}
.tabla-pedidos tr:hover {
    background-color: #f8dcdc;
}

/* Botones */
.btn-volver, .btn-calcular, .btn-confirmar {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}
.btn-volver:hover, .btn-calcular:hover, .btn-confirmar:hover {
    background-color: #941c1c;
}
.acciones { text-align: right; margin-top: 25px; }

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.45);
    justify-content: center;
    align-items: center;
}
.modal-contenido {
    background: #fff;
    padding: 25px 35px;
    border-radius: 12px;
    width: 380px;
    text-align: center;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
}
.modal-contenido h3 {
    font-weight: 700;
    color: #b22b27;
}
.modal-acciones {
    display: flex;
    justify-content: center;
    margin-top: 15px;
}
.btn {
    background-color: #b22b27;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 600;
    cursor: pointer;
}
.btn:hover {
    background-color: #941c1c;
}

/* Sección de cálculo */
.calculo {
    background-color: #fff8f0;
    padding: 10px 15px;
    border-radius: 8px;
    width: 280px;
}
.calculo label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}
.calculo input {
    margin-bottom: 8px;
    width: 100%;
    padding: 5px 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
.resultado p {
    margin: 6px 0;
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
        cuerpo.innerHTML = `<tr><td colspan="6" style="text-align:center;">No hay productos en el pedido.</td></tr>`;
        return;
    }

    productos.forEach(p => {
        const precio = parseFloat(p.precio || 0);
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
            </tr>`;
        cuerpo.innerHTML += fila;
    });

    document.getElementById('totalPedido').innerText = total.toFixed(2);
    document.getElementById('costoTotal').innerText = total.toFixed(2);

    // Calcular costo por comensal
    window.calcularCosto = function() {
        const comensales = parseInt(document.getElementById('comensales').value);
        if (comensales > 0) {
            const costoComensal = total / comensales;
            document.getElementById('costoComensal').innerText = costoComensal.toFixed(2);
        }
    };

    // Confirmar pedido (envío corregido)
    document.querySelector('.btn-confirmar').addEventListener('click', () => {
        const fechaSolicitud = localStorage.getItem('fechaSolicitud');
        const fechaEntrega = localStorage.getItem('fechaEntrega');
        const productos = JSON.parse(localStorage.getItem('pedidoActual')) || [];

        if (productos.length === 0) {
            mostrarModalMensaje("⚠️ No se puede confirmar", "No hay productos en el pedido.");
            return;
        }

        // ✅ Prepara los datos correctamente para el backend
        const productosFormateados = productos.map(p => ({
            id: p.id,
            cantidad: p.cantidad,
            precio: p.precio,
            producto_proveedor_id: p.producto_proveedor_id ?? p.proveedor_id ?? null // seguridad extra
        }));

        // Verifica antes de enviar
        console.log("Productos enviados:", productosFormateados);

        fetch("{{ route('dashboard.pedidos.guardar') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                fecha_solicitud: fechaSolicitud,
                fecha_entrega: fechaEntrega,
                productos: productosFormateados
            })
        })
        .then(response => {
            if (!response.ok) throw new Error("Respuesta HTTP no válida: " + response.status);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarModalMensaje("✅ Pedido registrado correctamente", "Tu pedido se ha guardado exitosamente.", true);
                localStorage.clear();
            } else {
                mostrarModalMensaje("❌ Error al guardar", data.message || "Hubo un problema al registrar el pedido.");
            }
        })
        .catch(err => {
            console.error("Error al enviar pedido:", err);
            mostrarModalMensaje("❌ Error de conexión", "No se pudo conectar con el servidor.");
        });
    });
});

// Modal elegante
function mostrarModalMensaje(titulo, mensaje, redirigir = false) {
    document.getElementById('tituloMensaje').innerHTML = titulo;
    document.getElementById('textoMensaje').innerHTML = mensaje;
    document.getElementById('modalMensaje').style.display = 'flex';

    if (redirigir) {
        document.querySelector('#modalMensaje .btn').onclick = () => {
            cerrarModalMensaje();
            window.location.href = "{{ route('dashboard.pedidos.consultar') }}";
        };
    }
}
function cerrarModalMensaje() {
    document.getElementById('modalMensaje').style.display = 'none';
}
</script>
@endsection
