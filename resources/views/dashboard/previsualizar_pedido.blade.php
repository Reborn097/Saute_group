@extends('layouts.dashboard')

@section('titulo', 'Previsualización del Pedido')

@section('contenido')
<div class="contenedor">

    <div class="cabecera">
        <button class="btn-volver" onclick="window.location.href='{{ route('dashboard.pedidos.solicitar') }}'">Regresar</button>

        <h2>Previsualización</h2>
        <div class="numero-pedido">Número de pedido: <b id="numeroPedido">#SPJ{{ rand(1000,9999) }}</b></div>
    </div>

    {{-- Tabla de productos en el pedido --}}
    <div class="tabla-contenedor">
        <table class="tabla-productos" id="tablaPrevisualizacion">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Unidad de medida</th>
                    <th>Cantidad</th>
                    <th>Proveedor</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="productosPrevisualizacion">
                {{-- Aquí se llenará dinámicamente desde localStorage --}}
            </tbody>
        </table>
    </div>

    {{-- Totales --}}
    <div class="resumen">
        <p><b>Total:</b> $<span id="totalPedido">0.00</span></p>
    </div>

    {{-- Cálculo de costo por comensal --}}
    <div class="calculo">
        <label>Cantidad de comensales:</label>
        <input type="number" id="comensales" min="1" value="1">
        <button class="btn-calcular" onclick="calcularCosto()">Calcular costo</button>

        <div class="resultado">
            <p><b>Costo total:</b> $<span id="costoTotal">0.00</span></p>
            <p><b>Costo por comensal:</b> $<span id="costoComensal">0.00</span></p>
        </div>
    </div>

    {{-- Botones finales --}}
    <div class="acciones">
        <button class="btn-confirmar">Confirmar pedido</button>
    </div>
</div>

{{-- Estilos --}}
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
        margin-bottom: 20px;
    }

    .numero-pedido {
        font-size: 1.1em;
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

{{-- Script --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const productos = JSON.parse(localStorage.getItem('pedidoActual')) || [];
    const cuerpo = document.getElementById('productosPrevisualizacion');
    let total = 0;

    if (productos.length === 0) {
        const fila = document.createElement('tr');
        fila.innerHTML = `<td colspan="7" style="text-align:center;">No hay productos en el pedido.</td>`;
        cuerpo.appendChild(fila);
        return;
    }

    productos.forEach(p => {
        // Aseguramos los nombres correctos (compatibilidad)
        const nombre = p.nombre || p.descripcion || 'Sin nombre';
        const categoria = p.categoria || p.categoria_nombre || 'N/A';
        const unidad = p.unidad_medida || p.unidad || 'N/A';
        const proveedor = p.proveedor || p.proveedor_nombre || 'N/A';
        const precio = parseFloat(p.precio || p.precio_unitario || 0);
        const cantidad = parseFloat(p.cantidad || 0);
        const subtotal = precio * cantidad;

        total += subtotal;

        const fila = `
            <tr>
                <td>${nombre}</td>
                <td>${categoria}</td>
                <td>${unidad}</td>
                <td>${cantidad}</td>
                <td>${proveedor}</td>
                <td>$${precio.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
            </tr>
        `;
        cuerpo.innerHTML += fila;
    });

    // Mostrar totales
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
});
</script>

@endsection
