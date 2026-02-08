@extends('layouts.dashboard')

@section('titulo', 'Reportes')

@section('contenido')
<div class="rep-wrap">
    <div class="rep-card">

        <div class="rep-header">
            <div class="acciones-superior">
                <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>
            </div>

            <div class="rep-actions">
                <a class="btn btn-outline"
                   href="{{ route('dashboard.reportes.pdf', request()->query()) }}">
                    Exportar PDF
                </a>
                <a class="btn btn-outline"
                   href="{{ route('dashboard.reportes.excel', request()->query()) }}">
                    Exportar Excel
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('dashboard.reportes') }}" class="rep-filters">
            <div class="field">
                <label>Unidad operativa</label>
                <select name="unidad_operativa_id">
                    <option value="">-- Selecciona --</option>
                    @foreach($unidades as $u)
                        <option value="{{ $u->id }}" {{ (string)$unidadOperativaId === (string)$u->id ? 'selected' : '' }}>
                            {{ $u->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label>Desde</label>
                <input type="date" name="desde" value="{{ $desdeStr }}">
            </div>

            <div class="field">
                <label>Hasta</label>
                <input type="date" name="hasta" value="{{ $hastaStr }}">
            </div>

            <div class="field btnbox" style="gap:8px;">
                <label>&nbsp;</label>

                <button class="btn btn-primary" type="submit">
                    Buscar
                </button>

                <a href="{{ route('dashboard.reportes') }}"
                    class="btn btn-outline">
                    Limpiar
                </a>
            </div>
        </form>

        @if($mensajeFiltroUnidad)
            <div class="rep-warn">
                {{ $mensajeFiltroUnidad }}
            </div>
        @endif

        {{-- ===========================
            PANEL DE GRÁFICOS (sin gasto diario)
        ============================ --}}
        <div class="rep-charts">

            <div class="rep-chart-card">
                <div class="rep-chart-head">
                    <h4>Comensales por día</h4>
                    <span class="muted">Rango: {{ $desdeStr }} a {{ $hastaStr }}</span>
                </div>
                <div class="rep-chart-canvas">
                    <canvas id="chartComensales"></canvas>
                </div>
            </div>

            <div class="rep-chart-card">
                <div class="rep-chart-head">
                    <h4>Top 10 productos por total ($)</h4>
                    <span class="muted">Periodo</span>
                </div>
                <div class="rep-chart-canvas">
                    <canvas id="chartTopProductos"></canvas>
                </div>
            </div>

            <div class="rep-chart-card rep-chart-small">
                <div class="rep-chart-head">
                    <h4>Corte de caja</h4>
                    <span class="muted">Efectivo vs Crédito</span>
                </div>
                <div class="rep-chart-canvas">
                    <canvas id="chartCorte"></canvas>
                </div>
            </div>

        </div>

        {{-- A) Productos pedidos --}}
        <div class="rep-section">
            <div class="rep-section-title">
                <h3>Productos pedidos</h3>
                <span class="muted">Rango: {{ $desdeStr }} a {{ $hastaStr }}</span>
            </div>

            <div class="table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Presentacion</th>
                            <th class="num">Cantidad</th>
                            <th class="num">Precio prom.</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productosPag as $r)
                            <tr>
                                <td>{{ $r->fecha }}</td>
                                <td>{{ $r->producto }}</td>
                                <td>{{ $r->presentacion }}</td>
                                <td class="num">{{ number_format((float)$r->cantidad_total, 2) }}</td>
                                <td class="num">$ {{ number_format((float)$r->precio_promedio, 2) }}</td>
                                <td class="num">$ {{ number_format((float)$r->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">Sin datos en el rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager">
                {{ $productosPag->links('vendor.pagination.dashboard') }}
            </div>
        </div>

        {{-- B) Gastos --}}
        <div class="rep-section">
            <div class="rep-section-title">
                <h3>Gastos (por semana)</h3>
                <span class="muted">Total periodo: <b>$ {{ number_format((float)$gastoTotalPeriodo, 2) }}</b></span>
            </div>

            <div class="table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Semana</th>
                            <th class="num">Total gasto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gastosPag as $g)
                            <tr>
                                <td>{{ $g->semana_inicio }} - {{ $g->semana_fin }}</td>
                                <td class="num">$ {{ number_format((float)$g->total_gasto, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager">
                {{ $gastosPag->links('vendor.pagination.dashboard') }}
            </div>
        </div>

        {{-- C) Comensales --}}
        <div class="rep-section">
            <div class="rep-section-title">
                <h3>Registros de comensales</h3>
                <span class="muted">Total periodo: <b>{{ number_format((int)$comensalesTotal) }}</b></span>
            </div>

            <div class="table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="num">Total comensales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comensalesPag as $c)
                            <tr>
                                <td>{{ $c->fecha }}</td>
                                <td class="num">{{ number_format((float)$c->total_comensales, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager">
                {{ $comensalesPag->links('vendor.pagination.dashboard') }}
            </div>
        </div>

        {{-- D) Corte de caja --}}
        <div class="rep-section">
            <div class="rep-section-title">
                <h3>Corte de caja</h3>
                <span class="muted">
                    Totales: Efectivo <b>$ {{ number_format((float)($corteTotales->total_efectivo ?? 0), 2) }}</b>
                    | Crédito <b>$ {{ number_format((float)($corteTotales->total_credito ?? 0), 2) }}</b>
                    | General <b>$ {{ number_format((float)($corteTotales->total_general ?? 0), 2) }}</b>
                </span>
            </div>

            <div class="table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="num">Efectivo</th>
                            <th class="num">Crédito</th>
                            <th class="num">Total</th>
                            <th class="num">Semana</th>
                            <th class="num">Mes</th>
                            <th class="num">Año</th>
                            <th class="num">Unidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cortePag as $cc)
                            <tr>
                                <td>{{ $cc->fecha }}</td>
                                <td class="num">$ {{ number_format((float)$cc->cantidad_efectivo, 2) }}</td>
                                <td class="num">$ {{ number_format((float)$cc->cantidad_credito, 2) }}</td>
                                <td class="num">$ {{ number_format((float)$cc->total, 2) }}</td>
                                <td class="num">{{ $cc->semana }}</td>
                                <td class="num">{{ $cc->mes }}</td>
                                <td class="num">{{ $cc->anio }}</td>
                                <td class="num">{{ $cc->unidad_id }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager">
                {{ $cortePag->links('vendor.pagination.dashboard') }}
            </div>
        </div>

        <div class="rep-section">
            <div class="rep-section-title">
                <h3>Transferencias de almacén</h3>
                <span class="muted">
                    Total transferido (costo): <b>$ {{ number_format((float)$transferenciasTotalCosto, 2) }}</b>
                </span>
            </div>

            <div class="table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Folio</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Producto</th>
                            <th>Presentación</th>
                            <th class="num">Cantidad</th>
                            <th class="num">Costo unit.</th>
                            <th class="num">Costo total</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transferenciasPag as $t)
                            <tr>
                                <td>{{ $t->fecha }}</td>
                                <td>{{ $t->referencia ?? '—' }}</td>
                                <td>{{ $t->almacen_origen ?? '—' }}</td>
                                <td>{{ $t->almacen_destino ?? '—' }}</td>
                                <td>{{ $t->producto ?? '—' }}</td>
                                <td>{{ $t->presentacion ?? '—' }}</td>
                                <td class="num">{{ number_format((float)$t->cantidad, 2) }}</td>
                                <td class="num">$ {{ number_format((float)($t->costo_unitario ?? 0), 2) }}</td>
                                <td class="num">$ {{ number_format((float)($t->costo_total ?? 0), 2) }}</td>
                                <td>{{ $t->usuario ?? '—' }}</td>
                                <td>{{ $t->motivo ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="empty">Sin transferencias en el rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager">
                {{ $transferenciasPag->links('vendor.pagination.dashboard') }}
            </div>
        </div>

    </div>
</div>

<style>
    /* ====== BASE ====== */
    body { background:#faebd7 !important; }
    main { padding: 18px 20px !important; }

    /* ====== CONTENEDOR ====== */
    .rep-wrap{
        padding: 0 !important;
        background: transparent !important;
    }

    .rep-card{
        background:#f9e3cc;
        border-radius:14px;
        padding:18px;
        box-shadow:0 6px 18px rgba(0,0,0,.08);
        border:1px solid rgba(0,0,0,.08);
        max-width: 1100px;
        margin: 0 auto;
    }

    .rep-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        margin: 0 0 10px;
    }

    .rep-actions{ display:flex; gap:10px; flex-wrap:wrap; }

    /* ====== FILTROS ====== */
    .rep-filters{
        display:grid;
        grid-template-columns: 1.4fr .8fr .8fr auto;
        gap:10px;
        align-items:end;
        margin:10px 0 14px;
    }

    .field label{
        display:block;
        font-size:12px;
        font-weight:700;
        margin:0 0 6px;
        color:#3b1a1a;
    }

    .field select,.field input{
        width:100%;
        padding:10px 12px;
        border-radius:10px;
        border:1px solid rgba(0,0,0,.18);
        background:#fff;
        box-sizing:border-box;
    }

    /* ====== BOTONES ====== */
    .btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:9px 12px;
        border-radius:10px;
        text-decoration:none;
        font-weight:800;
        font-size:13px;
        line-height:1;
        box-sizing:border-box;
        white-space:nowrap;
    }

    .btn-menu{
        background:#9c9c9c !important;
        color:#fff !important;
        border:none !important;
        padding:9px 12px !important;
        border-radius:10px !important;
        cursor:pointer;
        font-weight:800;
        white-space:nowrap;
    }
    .btn-menu:hover{ filter:brightness(.95); }

    .btn-primary{
        background:#b12a2a;
        color:#fff;
        border:1px solid #9f2424;
        cursor:pointer;
    }
    .btn-primary:hover{ filter:brightness(.95); }

    .btn-outline{
        background:#fff;
        color:#b12a2a;
        border:1px solid rgba(177,42,42,.35);
    }
    .btn-outline:hover{ background:#fff7ef; }

    /* ====== ADVERTENCIA ====== */
    .rep-warn{
        background:#fff;
        border:1px solid rgba(178,43,39,.25);
        color:#6b1e1e;
        padding:10px 12px;
        border-radius:12px;
        font-weight:800;
        margin: 10px 0 14px;
    }

    /* ====== GRÁFICOS ====== */
    .rep-charts{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap:12px;
        margin: 10px 0 14px;
    }

    .rep-chart-card{
        background:#fff;
        border-radius:12px;
        border:1px solid rgba(0,0,0,.08);
        overflow:hidden;
    }

    .rep-chart-small{
        grid-column: 1 / -1;
    }

    .rep-chart-head{
        background:#b12a2a;
        color:#fff;
        padding:10px 12px;
        display:flex;
        align-items:baseline;
        justify-content:space-between;
        gap:10px;
    }

    .rep-chart-head h4{
        margin:0;
        font-size:13px;
        font-weight:900;
        letter-spacing:.2px;
    }

    .rep-chart-head .muted{
        color:#fff;
        opacity:.95;
        font-size:12px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        max-width: 60%;
        text-align:right;
    }

    .rep-chart-canvas{
        height: 260px;
        padding: 10px 12px;
    }

    /* ====== SECCIONES ====== */
    .rep-section{
        margin:14px 0;
        background:#fff;
        border-radius:12px;
        border:1px solid rgba(0,0,0,.08);
        overflow:hidden;
    }

    .rep-section-title{
        display:grid;
        grid-template-columns: 1fr auto 1fr;
        align-items:center;
        gap:10px;
        padding:10px 12px;
        background:#b12a2a;
        color:#fff;
        margin:0 !important;
        border-bottom: 0;
    }

    .rep-section-title h3{
        grid-column:2;
        margin:0;
        font-size:14px;
        font-weight:800;
        text-align:center;
        color:#fff;
        line-height:1.2;
    }

    .rep-section-title .muted{
        grid-column:3;
        justify-self:end;
        font-size:12px;
        color:#fff;
        opacity:.95;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        max-width: 420px;
        text-align:right;
    }

    .table-wrap{ overflow:auto; }

    .rep-table{
        width:100%;
        border-collapse:collapse;
        table-layout:fixed;
        margin:0 !important;
    }

    .rep-table thead th{
        background:#b12a2a;
        color:#fff;
        padding:10px 12px;
        font-size:12px;
        font-weight:800;
        text-align:left;
        border:0;
        white-space:nowrap;
        vertical-align:middle;
        box-sizing:border-box;
    }

    .rep-table td{
        padding:10px 12px;
        font-size:12px;
        border-bottom:1px solid rgba(0,0,0,.07);
        vertical-align:middle;
        box-sizing:border-box;
        background:#fff;
    }

    .rep-table tr:hover td{ background:#fff7ef; }

    .rep-table th.num, .rep-table td.num{
        text-align:right !important;
        font-variant-numeric: tabular-nums;
        white-space:nowrap;
    }

    .empty{
        text-align:center;
        padding:14px;
        color:#6b4b4b;
        font-weight:700;
    }

    .pager{
        padding:10px 12px;
        background:#fff;
        border-top:1px solid rgba(0,0,0,.06);
    }

    /* ====== RESPONSIVE ====== */
    @media (max-width: 980px){
        .rep-card{ padding:14px; }
        .rep-filters{ grid-template-columns: 1fr 1fr; }
        .rep-section-title{ grid-template-columns: 1fr; }
        .rep-section-title h3{ grid-column:1; }
        .rep-section-title .muted{
            grid-column:1;
            justify-self:center;
            text-align:center;
            max-width: 100%;
        }

        .rep-charts{
            grid-template-columns: 1fr;
        }
        .rep-chart-small{
            grid-column: auto;
        }
        .rep-chart-canvas{
            height: 240px;
        }
    }
</style>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function(){
    const comLabels = @json($chartComensalesLabels ?? []);
    const comValues = @json($chartComensalesValues ?? []);

    const topLabels = @json($chartTopProductosLabels ?? []);
    const topValues = @json($chartTopProductosValues ?? []);

    const corte = @json($chartCorte ?? ['efectivo'=>0,'credito'=>0]);

    const money = (n) => {
        const v = Number(n || 0);
        return '$ ' + v.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    // 1) Comensales por día (línea)
    const elCom = document.getElementById('chartComensales');
    if (elCom && comLabels.length) {
        new Chart(elCom, {
            type: 'line',
            data: {
                labels: comLabels,
                datasets: [{
                    label: 'Comensales',
                    data: comValues,
                    tension: 0.25,
                    pointRadius: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.parsed.y.toLocaleString('es-MX')} comensales`
                        }
                    }
                },
                scales: {
                    x: { ticks: { maxRotation: 0 } },
                    y: { beginAtZero: true }
                }
            }
        });
    } else if (elCom) {
        elCom.parentElement.innerHTML = `<div class="empty">Sin datos para graficar.</div>`;
    }

    // 2) Top productos por total ($) (barras horizontales)
    const elTop = document.getElementById('chartTopProductos');
    if (elTop && topLabels.length) {
        new Chart(elTop, {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{
                    label: 'Total',
                    data: topValues,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${money(ctx.parsed.x)}`
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { callback: (v) => money(v) }
                    },
                    y: {
                        ticks: {
                            callback: function(value) {
                                const label = this.getLabelForValue(value);
                                return (label && label.length > 26) ? (label.slice(0, 26) + '…') : label;
                            }
                        }
                    }
                }
            }
        });
    } else if (elTop) {
        elTop.parentElement.innerHTML = `<div class="empty">Sin datos para graficar.</div>`;
    }

    // 3) Corte de caja (donut)
    const elCorte = document.getElementById('chartCorte');
    if (elCorte) {
        const ef = Number(corte.efectivo || 0);
        const cr = Number(corte.credito || 0);

        if (ef === 0 && cr === 0) {
            elCorte.parentElement.innerHTML = `<div class="empty">Sin datos para graficar.</div>`;
            return;
        }

        new Chart(elCorte, {
            type: 'doughnut',
            data: {
                labels: ['Efectivo', 'Crédito'],
                datasets: [{
                    data: [ef, cr]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${money(ctx.parsed)}`
                        }
                    }
                }
            }
        });
    }
})();
</script>
@endsection
