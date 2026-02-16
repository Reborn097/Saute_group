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
    <title>Pedido Diario {{ strtoupper($pedido->tipo) }} - Semana</title>

    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 30px; }

        body::before{
            content:"";
            position: fixed;
            top: 25%;
            left: 15%;
            width: 70%;
            height: 70%;
            background-image: url("{{ $logoPath }}");
            background-repeat:no-repeat;
            background-position:center;
            background-size:60%;
            opacity:0.08;
            z-index:-1;
        }

        .doc-header{
            margin-bottom: 8px;
        }

        .doc-header img{
            height: 44px;
            width: auto;
        }

        h1{ color:#b22b27; font-size:20px; margin-bottom:5px; }
        .box{
            border: 1px solid #eaeaea;
            padding: 14px 16px;
            border-radius: 10px;
            background: #ffffffcc;
            margin-bottom: 16px;
        }

        .grid{
            width:100%;
            border-collapse: collapse;
        }
        .grid td{
            padding: 4px 0;
            vertical-align: top;
            font-size:12px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
        }
        th{
            background:#b22b27;
            color:#fff;
            padding:7px;
            font-size:12px;
            text-align:center;
        }
        td{
            padding:7px;
            border-bottom:1px solid #ddd;
            font-size:12px;
            text-align:center;
        }
        td.left{ text-align:left; }

        tfoot th{
            text-align:right;
            padding:10px;
        }

        .badge{
            padding:4px 10px;
            border-radius:12px;
            color:#fff;
            font-size:11px;
            display:inline-block;
        }
        .estado-solicitado{ background:#999; }
        .estado-pendiente{ background:#999; }
        .estado-visto{ background:#66b3ff; }
        .estado-en-revision{ background:#f0ad4e; }
        .estado-preaprobado{ background:#f0ad4e; }
        .estado-aprobado{ background:#4caf50; }
        .estado-rechazado{ background:#d9534f; }
    </style>
</head>

<body>

@php
    $estadoRaw = trim((string)($pedido->estado ?? 'Solicitado'));
    $estadoKey = strtolower(str_replace(' ', '-', $estadoRaw));
    $estadoKey = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $estadoKey);
    $estadoKey = str_replace('--','-',$estadoKey);
@endphp

<div class="doc-header">
    <img src="{{ $logoPath }}" alt="Saute Group">
</div>

<h1>Pedido Diario - {{ strtoupper((string)$pedido->tipo) }}</h1>

<div class="box">
    <table class="grid">
        <tr>
            <td><strong>Código:</strong> {{ $pedido->codigo ?? '—' }}</td>
            <td><strong>Unidad operativa:</strong> {{ $pedido->unidadOperativa->nombre ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td><strong>Creado por:</strong> {{ $pedido->usuario->name ?? 'N/A' }}</td>
            <td><strong>Estado:</strong>
                <span class="badge estado-{{ $estadoKey }}">{{ $estadoRaw }}</span>
            </td>
        </tr>

        <tr>
            <td><strong>Semana:</strong>
                {{ \Carbon\Carbon::parse($pedido->semana_inicio)->format('d/m/Y') }}
                -
                {{ \Carbon\Carbon::parse($pedido->semana_fin)->format('d/m/Y') }}
            </td>
            <td><strong>Fecha creación:</strong> {{ optional($pedido->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>

        @if(!empty($pedido->observaciones))
            <tr>
                <td colspan="2"><strong>Observaciones:</strong> {{ $pedido->observaciones }}</td>
            </tr>
        @endif

        @if(!empty($pedido->observaciones_ceo))
            <tr>
                <td colspan="2"><strong>Observaciones CEO:</strong> {{ $pedido->observaciones_ceo }}</td>
            </tr>
        @endif

        @if(!empty($pedido->motivo_rechazo))
            <tr>
                <td colspan="2"><strong>Motivo rechazo:</strong> {{ $pedido->motivo_rechazo }}</td>
            </tr>
        @endif
    </table>
</div>

<table>
    <thead>
        <tr>
            <th style="text-align:left;">Producto</th>
            @foreach($days as $d)
                <th>{{ \Carbon\Carbon::parse($d)->locale('es')->isoFormat('ddd DD') }}</th>
            @endforeach
            <th>Total</th>
            <th>Precio</th>
            <th>Subtotal</th>
        </tr>
    </thead>

    <tbody>
        @php $totalGeneral = 0; @endphp

        @foreach(($presentaciones ?? []) as $pres)
            @php
                $totalFila = 0;
                $precio = (float)($precios[$pres->id] ?? 0);

                $nombreProd = $pres->producto->nombre ?? 'Producto';
                $descPres   = $pres->descripcion ?? '';
                $unidadBase = $pres->unidad_base ?? null;
                $unidadCont = $pres->unidad_contenido ?? null;
            @endphp

            <tr>
                <td class="left">
                    <strong>{{ $nombreProd }}</strong>
                    @if($descPres)
                        <br><small>{{ $descPres }}</small>
                    @endif

                    @if($unidadBase || $unidadCont)
                        <br>
                        <small>
                            {{ $unidadBase ?? '—' }}
                            @if($unidadCont)
                                <span style="opacity:.85;">({{ $unidadCont }})</span>
                            @endif
                        </small>
                    @endif
                </td>

                @foreach($days as $d)
                    @php
                        $cant = (float)($cantidades[$pres->id][$d] ?? 0);
                        $totalFila += $cant;
                    @endphp
                    <td>
                        {{ $cant > 0 ? rtrim(rtrim(number_format($cant, 2), '0'), '.') : '-' }}
                    </td>
                @endforeach

                @php
                    $sub = $totalFila * $precio;
                    $totalGeneral += $sub;
                @endphp

                <td><strong>{{ rtrim(rtrim(number_format($totalFila, 2), '0'), '.') }}</strong></td>
                <td>${{ number_format($precio, 2) }}</td>
                <td><strong>${{ number_format($sub, 2) }}</strong></td>
            </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <th colspan="{{ 1 + count($days) + 2 }}">TOTAL GENERAL</th>
            <th>${{ number_format($totalGeneral, 2) }}</th>
        </tr>
    </tfoot>
</table>

</body>
</html>
