<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Control de Kilometraje</title>
    <style>
        body{ font-family: DejaVu Sans, sans-serif; font-size:10.5px; }
        h2{ margin:0 0 6px; }
        .meta{ margin-bottom:10px; }
        table{ width:100%; border-collapse:collapse; margin:10px 0 14px; table-layout:fixed; }
        th,td{ border:1px solid #ccc; padding:5px; word-wrap:break-word; }
        th{ background:#eee; }
        .num{ text-align:right; }
        .col-fecha{ width:10%; }
        .col-unidad{ width:16%; }
        .col-km{ width:10%; }
        .col-diesel{ width:10%; }
        .col-lugares{ width:34%; }
    </style>
</head>
<body>
    <h2>Control de kilometraje</h2>
    <div class="meta">
        <div><b>Unidad:</b> {{ $unidadNombre ?? 'Todas' }}</div>
        <div><b>Rango:</b> {{ $desdeStr }} a {{ $hastaStr }}</div>
    </div>

    <div class="meta">
        <div><b>Días con registro:</b> {{ $totales['dias'] ?? 0 }}</div>
        <div><b>Total km recorridos:</b> {{ number_format((float)($totales['km_recorridos'] ?? 0), 0) }}</div>
        <div><b>Km inicio mínimo:</b> {{ $totales['km_inicio_min'] ?? '-' }}</div>
        <div><b>Km final máximo:</b> {{ $totales['km_final_max'] ?? '-' }}</div>
        <div><b>Promedio diésel inicio:</b> {{ isset($totales['diesel_inicio_prom']) ? number_format((float)$totales['diesel_inicio_prom'], 2) : '-' }}%</div>
        <div><b>Promedio diésel final:</b> {{ isset($totales['diesel_final_prom']) ? number_format((float)$totales['diesel_final_prom'], 2) : '-' }}%</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-fecha">Fecha</th>
                <th class="col-unidad">Unidad</th>
                <th class="num col-km">Km inicio</th>
                <th class="num col-km">Km final</th>
                <th class="num col-km">Km recorridos</th>
                <th class="num col-diesel">Diésel inicio</th>
                <th class="num col-diesel">Diésel final</th>
                <th class="col-lugares">Lugares visitados</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            <tr>
                <td>{{ $r->fecha }}</td>
                <td>{{ $r->unidad_nombre }}</td>
                <td class="num">{{ $r->km_inicio ?? '' }}</td>
                <td class="num">{{ $r->km_final ?? '' }}</td>
                <td class="num">{{ $r->km_recorridos ?? '' }}</td>
                <td class="num">{{ $r->diesel_inicio_pct ?? '' }}</td>
                <td class="num">{{ $r->diesel_final_pct ?? '' }}</td>
                <td>{{ $r->lugares_visitados ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Sin datos</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
