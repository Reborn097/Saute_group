@extends('layouts.dashboard')

@section('titulo', 'Detalle del Pedido')

@section('contenido')
<div class="contenedor">
    <h2>Detalle del pedido #{{ $pedido->codigo }}</h2>

    <p><b>Fecha de solicitud:</b> {{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</p>
    <p><b>Fecha de entrega:</b> {{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('d/m/Y') }}</p>
    <p><b>Usuario:</b> {{ $pedido->usuario->name }}</p>
    <p><b>Total:</b> ${{ number_format($pedido->total, 2) }}</p>

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


    <button class="btn" onclick="window.history.back()">Regresar</button>
</div>
@endsection
