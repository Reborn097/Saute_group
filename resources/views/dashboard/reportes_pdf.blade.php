<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reportes</title>
    <style>
        body{ font-family: DejaVu Sans, sans-serif; font-size:11px; }
        h2{ margin:0 0 6px; }
        .meta{ margin-bottom:10px; }
        table{ width:100%; border-collapse:collapse; margin:10px 0 14px; }
        th,td{ border:1px solid #ccc; padding:6px; }
        th{ background:#eee; }
        .num{ text-align:right; }
    </style>
</head>
<body>
    <h2>Reportes</h2>
    <div class="meta">
        <div><b>Unidad:</b> {{ $unidadNombre ?? ($unidadOperativaId ? $unidadOperativaId : 'Todas') }}</div>
        <div><b>Rango:</b> {{ $desdeStr }} a {{ $hastaStr }}</div>
    </div>

    <h3>Productos pedidos</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th><th>Producto</th><th class="num">Cantidad</th><th class="num">Precio prom.</th><th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
        @forelse($productos as $r)
            <tr>
                <td>{{ $r->fecha }}</td>
                <td>{{ $r->producto }}</td>
                <td class="num">{{ number_format((float)$r->cantidad_total,2) }}</td>
                <td class="num">{{ number_format((float)$r->precio_promedio,2) }}</td>
                <td class="num">{{ number_format((float)$r->total,2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Sin datos</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>Gastos (por día) — Total periodo: {{ number_format((float)$gastoTotalPeriodo,2) }}</h3>
    <table>
        <thead><tr><th>Fecha</th><th class="num">Total gasto</th></tr></thead>
        <tbody>
        @forelse($gastos as $g)
            <tr><td>{{ $g->fecha }}</td><td class="num">{{ number_format((float)$g->total_gasto,2) }}</td></tr>
        @empty
            <tr><td colspan="2">Sin datos</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>Comensales — Total periodo: {{ number_format((int)$comensalesTotal) }}</h3>
    <table>
        <thead><tr><th>Fecha</th><th class="num">Total</th></tr></thead>
        <tbody>
        @forelse($comensales as $c)
            <tr><td>{{ $c->fecha }}</td><td class="num">{{ number_format((float)$c->total_comensales,0) }}</td></tr>
        @empty
            <tr><td colspan="2">Sin datos</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>Corte de caja</h3>
    <div>
        <b>Totales:</b>
        Efectivo {{ number_format((float)($corteTotales->total_efectivo ?? 0),2) }},
        Crédito {{ number_format((float)($corteTotales->total_credito ?? 0),2) }},
        General {{ number_format((float)($corteTotales->total_general ?? 0),2) }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th><th class="num">Efectivo</th><th class="num">Crédito</th><th class="num">Total</th><th class="num">Semana</th><th class="num">Mes</th><th class="num">Año</th><th class="num">Unidad</th>
            </tr>
        </thead>
        <tbody>
        @forelse($cortes as $cc)
            <tr>
                <td>{{ $cc->fecha }}</td>
                <td class="num">{{ number_format((float)$cc->cantidad_efectivo,2) }}</td>
                <td class="num">{{ number_format((float)$cc->cantidad_credito,2) }}</td>
                <td class="num">{{ number_format((float)$cc->total,2) }}</td>
                <td class="num">{{ $cc->semana }}</td>
                <td class="num">{{ $cc->mes }}</td>
                <td class="num">{{ $cc->anio }}</td>
                <td class="num">{{ $cc->unidad_id }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Sin datos</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
