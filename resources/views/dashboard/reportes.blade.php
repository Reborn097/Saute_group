@extends('layouts.app') {{-- ajusta si tu layout se llama distinto --}}
@extends('layouts.dashboard')
@section('titulo', 'Reportes')
@section('content')
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
                                <td class="num">{{ number_format((float)$r->cantidad_total, 2) }}</td>
                                <td class="num">$ {{ number_format((float)$r->precio_promedio, 2) }}</td>
                                <td class="num">$ {{ number_format((float)$r->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">Sin datos en el rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager">
                {{ $productosPag->links() }}
            </div>
        </div>

        {{-- B) Gastos --}}
        <div class="rep-section">
            <div class="rep-section-title">
                <h3>Gastos (por día)</h3>
                <span class="muted">Total periodo: <b>$ {{ number_format((float)$gastoTotalPeriodo, 2) }}</b></span>
            </div>

            <div class="table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="num">Total gasto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gastosPag as $g)
                            <tr>
                                <td>{{ $g->fecha }}</td>
                                <td class="num">$ {{ number_format((float)$g->total_gasto, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager">
                {{ $gastosPag->links() }}
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
                {{ $comensalesPag->links() }}
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
                {{ $cortePag->links() }}
            </div>
        </div>

    </div>
</div>

<style>
    /* ====== BASE (para que NO salga el fondo azul/verde) ====== */
    body { background:#faebd7 !important; }
    main { padding: 18px 20px !important; } /* menos “espacio muerto” */

    /* ====== CONTENEDOR ====== */
    .rep-wrap{
        padding: 0 !important;
        background: transparent !important; /* que mande el body */
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

    /* Botón Menú principal gris (como en tus otras vistas) */
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

    /* ====== SECCIONES (TITULO EN ROJO + TEXTO BLANCO) ====== */
    .rep-section{
        margin:14px 0;
        background:#fff;
        border-radius:12px;
        border:1px solid rgba(0,0,0,.08);
        overflow:hidden;
    }

    /* Título centrado, “meta” a la derecha EN UNA SOLA LINEA */
    .rep-section-title{
        display:grid;
        grid-template-columns: 1fr auto 1fr; /* centro real */
        align-items:center;
        gap:10px;
        padding:10px 12px;
        background:#b12a2a;   /* ROJO */
        color:#fff;          /* BLANCO */
        margin:0 !important; /* sin “espacio muerto” */
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
        white-space:nowrap;       /* NO se parte en varias líneas */
        overflow:hidden;
        text-overflow:ellipsis;
        max-width: 420px;
        text-align:right;
    }

    /* ====== TABLAS (ENCABEZADO ROJO, TEXTO BLANCO, SIN “FRANJA” BLANCA) ====== */
    .table-wrap{ overflow:auto; }

    .rep-table{
        width:100%;
        border-collapse:collapse;
        table-layout:fixed; /* ayuda a que SIEMPRE cuadre */
        margin:0 !important;
    }

    .rep-table thead th{
        background:#b12a2a;  /* ROJO */
        color:#fff;          /* BLANCO */
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

    /* Alineación correcta de números (encabezado y datos) */
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

    /* ====== PAGINACIÓN ====== */
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
    }
</style>

@endsection
