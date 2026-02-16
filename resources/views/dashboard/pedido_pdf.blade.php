@php
    $logoPath = public_path('images/icons/logoSaute2.png');
    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/icons/logoSaute.png');
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido {{ $pedido->codigo }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 14px 18px;
        }

        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
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
            margin: 0 0 6px;
        }

        .doc-header img {
            height: 44px;
            width: auto;
        }

        h1 {
            margin: 0 0 8px;
            color: #b22b27;
            font-size: 20px;
            font-weight: 700;
        }

        h3 {
            margin: 12px 0 6px;
            font-size: 15px;
            color: #1f2937;
        }

        .box {
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.92);
            padding: 8px 10px;
            margin-bottom: 10px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            width: 25%;
            padding: 4px 8px;
            vertical-align: top;
            border: none;
        }

        .meta .meta-wide {
            width: 50%;
        }

        .label {
            font-weight: 800;
        }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
        }

        .estado-pendiente { background: #f59e0b; }
        .estado-visto { background: #3b82f6; }
        .estado-preaprobado { background: #7c9b2a; }
        .estado-aprobado { background: #16a34a; }
        .estado-cancelado { background: #b91c1c; }
        .estado-default { background: #6b7280; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #b22b27;
            color: #fff;
            padding: 7px 8px;
            text-align: left;
            font-size: 10.5px;
        }

        td {
            padding: 7px 8px;
            border-bottom: 1px solid #ececec;
            font-size: 10.5px;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .prov-row td {
            background: #fff3e4;
            color: #6b1818;
            font-weight: 700;
            border-bottom: 1px solid #f2d9c8;
        }

        .tot-prov td {
            background: #fff9f2;
        }

        .tot-general td {
            background: #ffe9d2;
            font-weight: 800;
        }
    </style>
</head>
<body>
@php
    $estadoActual = strtolower((string) $pedido->estado);
    $estadoClass = 'estado-default';
    if ($estadoActual === 'pendiente') {
        $estadoClass = 'estado-pendiente';
    } elseif ($estadoActual === 'visto') {
        $estadoClass = 'estado-visto';
    } elseif ($estadoActual === 'preaprobado') {
        $estadoClass = 'estado-preaprobado';
    } elseif ($estadoActual === 'aprobado') {
        $estadoClass = 'estado-aprobado';
    } elseif ($estadoActual === 'cancelado') {
        $estadoClass = 'estado-cancelado';
    }

    $aprobadosPorProveedor = [];
    $totalGeneral = 0;

    foreach (($pedido->detalles ?? []) as $d) {
        $pres = $d->presentacion ?? null;
        $pp = $d->productoProveedor ?? null;
        $producto = $pres?->producto ?? $pp?->producto ?? null;

        $provRel = $pres?->proveedores ?? collect();
        $provSel = $provRel->firstWhere('id', (int)($d->producto_proveedor_id ?? 0));
        $primProv = $provSel ?: $provRel->first();
        $proveedor = $primProv?->proveedor ?? $pp?->proveedor ?? null;
        $provNombre = $proveedor->nombre ?? 'Sin proveedor';

        $sol = (float) ($d->cantidad_solicitada ?? 0);
        $apr = $d->cantidad_aprobada;
        $apr = ($apr === null ? $sol : (float) $apr);
        $activo = $d->activo;
        $activo = ($activo === null ? 1 : (int) $activo);

        if ($activo !== 1 || $apr <= 0) {
            continue;
        }

        $presentacion = trim((string) ($pres?->descripcion ?? ''));
        if ($presentacion === '') {
            $presentacion = '—';
        }

        $contenidoValor = $pres?->contenido ?? null;
        $unidadContenido = $pres?->unidad_contenido ?? ($pres?->unidad_base ?? '');
        $contenido = '—';

        if ($contenidoValor !== null && $contenidoValor !== '') {
            $contenido = trim($contenidoValor . ' ' . ($unidadContenido ?: ''));
        } elseif ($unidadContenido) {
            $contenido = $unidadContenido;
        } elseif ($producto) {
            $valorMedida = $producto->valor_medida ?? null;
            $unidadMedida = $producto->unidad_medida ?? ($producto->unidad ?? '');
            if ($valorMedida !== null && $valorMedida !== '') {
                $contenido = trim($valorMedida . ' ' . ($unidadMedida ?: ''));
            } elseif ($unidadMedida) {
                $contenido = $unidadMedida;
            }
        }

        $precio = (float) ($d->precio_unitario ?? 0);
        $subtotal = $d->subtotal;
        $subtotal = ($subtotal === null ? ($apr * $precio) : (float) $subtotal);

        $item = [
            'cantidad' => $apr,
            'contenido' => $contenido,
            'presentacion' => $presentacion,
            'producto' => $producto->nombre ?? '—',
            'marca' => $producto->marca ?? '—',
            'precio' => $precio,
            'subtotal' => $subtotal,
        ];

        $aprobadosPorProveedor[$provNombre][] = $item;
        $totalGeneral += $subtotal;
    }

    ksort($aprobadosPorProveedor);
@endphp

    <div class="doc-header">
        <img src="{{ $logoPath }}" alt="Saute Group">
    </div>

    <h1>Detalle del Pedido - {{ $pedido->codigo }}</h1>

    <div class="box">
        <table class="meta">
            <tr>
                <td><span class="label">Fecha solicitud:</span> {{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</td>
                <td><span class="label">Fecha entrega:</span> {{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('d/m/Y') }}</td>
                <td><span class="label">Usuario:</span> {{ $pedido->usuario->name ?? 'Administrador' }}</td>
                <td><span class="label">Estado:</span> <span class="badge {{ $estadoClass }}">{{ $pedido->estado }}</span></td>
            </tr>
            <tr>
                <td class="meta-wide" colspan="2"><span class="label">Total:</span> ${{ number_format((float) $pedido->total, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </table>
    </div>

    <h3>Productos</h3>

    <table>
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Contenido</th>
                <th>Presentacion</th>
                <th>Producto</th>
                <th>Marca</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if(empty($aprobadosPorProveedor))
                <tr>
                    <td colspan="7">No hay productos aprobados.</td>
                </tr>
            @else
                @foreach($aprobadosPorProveedor as $prov => $items)
                    @php
                        $totalProv = 0;
                        foreach ($items as $it) {
                            $totalProv += (float) $it['subtotal'];
                        }
                    @endphp
                    <tr class="prov-row">
                        <td colspan="7">Proveedor: {{ $prov }}</td>
                    </tr>
                    @foreach($items as $it)
                        <tr>
                            <td class="num">{{ rtrim(rtrim(number_format((float) $it['cantidad'], 2), '0'), '.') }}</td>
                            <td>{{ $it['contenido'] }}</td>
                            <td>{{ $it['presentacion'] }}</td>
                            <td>{{ $it['producto'] }}</td>
                            <td>{{ $it['marca'] }}</td>
                            <td class="num">${{ number_format((float) $it['precio'], 2) }}</td>
                            <td class="num">${{ number_format((float) $it['subtotal'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="tot-prov">
                        <td colspan="6" class="num"><strong>Total proveedor</strong></td>
                        <td class="num"><strong>${{ number_format($totalProv, 2) }}</strong></td>
                    </tr>
                @endforeach
                <tr class="tot-general">
                    <td colspan="6" class="num"><strong>TOTAL GENERAL</strong></td>
                    <td class="num"><strong>${{ number_format($totalGeneral, 2) }}</strong></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
