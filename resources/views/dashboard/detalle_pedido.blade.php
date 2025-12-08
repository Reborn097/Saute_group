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

    {{-- ========================================================= --}}
    {{--        DOCUMENTOS DEL PEDIDO ESPECIAL (SI EXISTEN)        --}}
    {{-- ========================================================= --}}
    @if(isset($pedidoEspecial) && $pedidoEspecial)
        <h3 style="margin-top: 30px;">Documentos adjuntos del pedido especial:</h3>

        <table class="tabla-pedidos">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Archivo</th>
                    <th>Ver</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>PDF solicitud</td>
                    <td>{{ basename($pedidoEspecial->solicitud) }}</td>
                    <td><a class="btn" href="{{ asset($pedidoEspecial->solicitud) }}" target="_blank">Ver PDF</a></td>
                </tr>

                <tr>
                    <td>PDF cotización</td>
                    <td>{{ basename($pedidoEspecial->cotizacion) }}</td>
                    <td><a class="btn" href="{{ asset($pedidoEspecial->cotizacion) }}" target="_blank">Ver PDF</a></td>
                </tr>

                <tr>
                    <td>PDF autorización</td>
                    <td>{{ basename($pedidoEspecial->autorizacion) }}</td>
                    <td><a class="btn" href="{{ asset($pedidoEspecial->autorizacion) }}" target="_blank">Ver PDF</a></td>
                </tr>
            </tbody>
        </table>
    @endif

    <h3>Productos:</h3>

    <table class="tabla-pedidos">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Cantidad</th>
                <th>Proveedor</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido->detalles as $detalle)

                @php
                    $pp = $detalle->productoProveedor;

                    $producto = $pp->producto ?? null;
                    $categoria = $producto->categoria ?? null;
                    $proveedor = $pp->proveedor ?? null;

                    $cantidad = $detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada;
                    $precio = $detalle->precio_unitario;
                    $subtotal = $detalle->subtotal;
                @endphp

                <tr>
                    <td>{{ $producto->nombre ?? '-' }}</td>
                    <td>{{ $categoria->nombre ?? '-' }}</td>
                    <td>{{ $cantidad }}</td>
                    <td>{{ $proveedor->nombre ?? '-' }}</td>
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

.contenedor h2 {
    font-size: 1.4em;
    font-weight: 700;
    margin-bottom: 10px;
    color: #6b1818;
}

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

/* ======== BOTÓN ======== */
.acciones {
    text-align: left;
    margin-top: 25px;
}

.btn {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}
.btn:hover {
    background-color: #941c1c;
}
</style>

@endsection
