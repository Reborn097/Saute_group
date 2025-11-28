<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido {{ $pedido->codigo }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 40px;
            position: relative;
        }

        /* ===== MARCA DE AGUA ===== */
        body::before {
            content: "";
            position: fixed;
            top: 25%;
            left: 15%;
            width: 70%;
            height: 70%;
            background-image: url("{{ public_path('images/logo_watermark.png') }}");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 60%;
            opacity: 0.08; /* Transparencia para que no moleste */
            z-index: -1;
        }

        /* ===== CONTENEDOR DEL CUADRO PRINCIPAL ===== */
        .box {
            border: 1px solid #eaeaea;
            padding: 20px;
            border-radius: 10px;
            background-color: #ffffffcc;
        }

        h1 {
            color: #b22b27;
            font-size: 22px;
        }

        h3 {
            margin-top: 25px;
            color: #333;
        }

        /* Tabla PDF */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th {
            background: #b22b27;
            color: white;
            padding: 8px;
            text-align: left;
        }

        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 12px;
            color: white;
            font-size: 12px;
            display: inline-block;
        }

        .estado-pendiente   { background: #ff9800; }
        .estado-proceso     { background: #2196f3; }
        .estado-preaprobado { background: #8bc34a; }
        .estado-aprobado    { background: #4caf50; }
        .estado-finalizado  { background: #9c27b0; }
    </style>
</head>
<body>

    <h1>Detalle del Pedido - {{ $pedido->codigo }}</h1>

    <div class="box">
        <p><strong>Fecha solicitud:</strong> {{ $pedido->fecha_solicitud }}</p>
        <p><strong>Fecha entrega:</strong> {{ $pedido->fecha_entrega }}</p>
        <p><strong>Usuario:</strong> {{ $pedido->usuario->name ?? 'Administrador' }}</p>
        <p><strong>Total:</strong> ${{ number_format($pedido->total,2) }}</p>

        <p><strong>Estado:</strong>
            <span class="badge estado-{{ strtolower($pedido->estado) }}">
                {{ ucfirst($pedido->estado) }}
            </span>
        </p>
    </div>

    <h3>Productos</h3>

    <table>
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
            @foreach ($pedido->detalles as $d)
            <tr>
                <td>{{ $d->productoProveedor->producto->nombre }}</td>
                <td>{{ $d->productoProveedor->producto->categoria->nombre }}</td>
                <td>{{ number_format($d->cantidad_solicitada, 2) }}</td>
                <td>${{ number_format($d->precio_unitario, 2) }}</td>
                <td>${{ number_format($d->cantidad_solicitada * $d->precio_unitario, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
