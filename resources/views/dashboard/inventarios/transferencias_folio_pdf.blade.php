@php
    $logoPath = public_path('images/icons/logoSaute2.png');
    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/icons/logoSaute.png');
    }
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Transferencias Por Folio</title>
    <style>
        @page {
            margin: 16px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 18%;
            left: 14%;
            width: 72%;
            height: 72%;
            background-image: url("{{ $logoPath }}");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 55%;
            opacity: 0.07;
            z-index: -1;
        }

        .doc-header {
            margin-bottom: 8px;
        }

        .doc-header img {
            height: 44px;
            width: auto;
        }

        h2 {
            margin: 0 0 6px;
        }

        .meta {
            margin-bottom: 10px;
        }

        .meta div {
            margin-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.95);
        }

        th, td {
            border: 1px solid #cfcfcf;
            padding: 5px;
        }

        th {
            background: #f0f0f0;
            text-align: left;
        }

        .num {
            text-align: right;
        }

        .total {
            margin-top: 8px;
            font-weight: 700;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <img src="{{ $logoPath }}" alt="Saute Group">
    </div>

    <h2>Transferencias de inventario</h2>

    <div class="meta">
        <div><b>Folio:</b> {{ $folio }}</div>
        <div><b>Fecha:</b> {{ $fecha }}</div>
        <div><b>Movimientos:</b> {{ number_format($transferencias->count(), 0) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Unidad destino</th>
                <th>Almacen destino</th>
                <th>Producto</th>
                <th>Presentacion</th>
                <th class="num">Cantidad</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transferencias as $t)
            @php
                $presentacion = $t->presentacion ?? '-';
                if (!empty($t->presentacion_contenido)) {
                    $presentacion = trim(($t->presentacion ?? '') . ' - ' . $t->presentacion_contenido);
                }
            @endphp
            <tr>
                <td>{{ $t->fecha }}</td>
                <td>{{ $t->unidad_destino ?? '-' }}</td>
                <td>{{ $t->almacen_destino ?? '-' }}</td>
                <td>{{ $t->producto ?? '-' }}</td>
                <td>{{ $presentacion }}</td>
                <td class="num">{{ number_format((float)$t->cantidad, 2) }}</td>
                <td>{{ $t->motivo ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="total">
        Cantidad total transferida: {{ number_format((float)$totalCantidad, 2) }}
    </div>
</body>
</html>
