@extends('layouts.dashboard')

@section('titulo', 'Detalle del Pedido')

@section('contenido')
<div class="contenedor">
    <h2>Detalle del pedido #{{ $pedido->codigo }}</h2>

    <div class="info-pedido">
        <p><b>Fecha de solicitud:</b> {{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</p>
        <p><b>Fecha de entrega:</b> {{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('d/m/Y') }}</p>
        <p><b>Usuario:</b> {{ $pedido->usuario->name }}</p>
        <p><b>Total:</b> ${{ number_format($pedido->total, 2) }}</p>
    </div>

    <h3>Productos:</h3>

    <table class="tabla-pedidos">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido->detalles as $detalle)
                @php
                    $cantidad = $detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada ?? 0;
                    $precio = $detalle->precio_unitario ?? 0;
                    $subtotal = $cantidad * $precio;
                @endphp
                <tr>
                    <td>{{ $detalle->productoProveedor->producto->nombre ?? '-' }}</td>
                    <td>{{ $detalle->productoProveedor->producto->categoria->nombre ?? '-' }}</td>
                    <td>{{ $cantidad }}</td>
                    <td>${{ number_format($precio, 2) }}</td>
                    <td>${{ number_format($subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="acciones">
        <button class="btn" onclick="window.history.back()">Regresar</button>
    </div>
</div>

<style>
/* ======== CONTENEDOR GENERAL ======== */
.contenedor {
    background-color: #fae7d0;
    padding: 25px 35px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    max-width: 1100px;
    margin: 0 auto;
    font-family: 'Poppins', sans-serif;
}

/* Solo el título dentro del contenedor */
.contenedor h2 {
    font-size: 1.4em;
    font-weight: 700;
    margin-bottom: 10px;
    color: #6b1818;
}

/* El título principal del dashboard (barra roja arriba) se mantiene blanco */
h1, .titulo-principal, .navbar h1, .dashboard-header h1 {
    color: white !important;
}

/* ======== INFO DEL PEDIDO ======== */
.info-pedido {
    background-color: #fff8f0;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    line-height: 1.6;
}

h3 {
    margin-top: 20px;
    font-size: 1.1em;
    color: #6b1818;
}

/* ======== TABLA ======== */
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
    text-align: center;
    border-bottom: 1px solid #ddd;
}

.tabla-pedidos tr:hover {
    background-color: #f8dcdc;
}

/* ======== BOTÓN REGRESAR ======== */
.acciones {
    text-align: left;
    margin-top: 25px; /* Separación extra para no verse amontonado */
}

.btn {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.btn:hover {
    background-color: #941c1c;
}
</style>
@endsection
