@extends('layouts.dashboard')

@section('titulo', 'Reporte por proveedor')

@section('contenido')
@php
    $fmt = function($n){
        $n = (float)$n;
        $s = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
        return $s === '' ? '0' : $s;
    };
@endphp

<div class="rep-wrap">

    <div class="rep-head">
        <div>
            <div class="rep-title">Reporte de pedidos por proveedor</div>
            <div class="rep-sub">
                Periodo: <b>{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}</b>
                — <b>{{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</b>
            </div>
        </div>

        <form method="GET" class="rep-filters no-print">
            <div class="f">
                <label>Desde</label>
                <input type="date" name="desde" value="{{ $desde }}">
            </div>
            <div class="f">
                <label>Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}">
            </div>
            <button type="submit" class="btn">Buscar</button>
            <button type="button" class="btn btn-sec" onclick="window.print()">Imprimir</button>
        </form>
    </div>

    @if(empty($agrupado))
        <div class="rep-empty">No hay registros en el periodo seleccionado.</div>
    @else

        @foreach($agrupado as $prov)
            <section class="prov-card">
                <div class="prov-bar">
                    <span class="prov-name">{{ $prov['proveedor'] }}</span>
                </div>

                @foreach(($prov['unidades'] ?? []) as $uo)
                    <div class="uo-block">
                        @php $totalItems = count($uo['items'] ?? []); @endphp

                            <div class="uo-chip">
                                {{ $uo['unidad_operativa'] }}
                                <span class="uo-count">{{ $totalItems }} ítems</span>
                            </div>


                        <div class="items">
                            @foreach(($uo['items'] ?? []) as $it)
                                <div class="item">
                                    <div class="qty">
                                        <span class="num">{{ $fmt($it['cantidad']) }}</span>
                                        <span class="um">{{ $it['unidad_medida'] }}</span>
                                    </div>
                                    <div class="prod">{{ $it['producto'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach

    @endif

</div>

<style>
/* ====== LOOK SAUTÉ (compacto + imprimible) ====== */
.rep-wrap{
    max-width: 1040px;
    margin: 0 auto;
    padding: 18px 18px 26px;
    background: #fae7d0;       /* crema sauté */
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,.10);
    font-family: 'Poppins', Arial, Helvetica, sans-serif;
    color: #231f20;
}

.rep-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(0,0,0,.12);
    margin-bottom: 12px;
}

.rep-title{
    font-size: 18px;
    font-weight: 900;
    color: #6b1818;
    letter-spacing: .2px;
}
.rep-sub{
    margin-top: 4px;
    font-size: 12px;
    color: rgba(0,0,0,.70);
}

/* Filtros (solo pantalla) */
.rep-filters{
    display:flex;
    gap: 10px;
    align-items:flex-end;
    flex-wrap:wrap;
}
.rep-filters .f label{
    display:block;
    font-size: 11px;
    color: rgba(0,0,0,.70);
    margin-bottom: 4px;
    font-weight: 700;
}
.rep-filters input{
    padding: 7px 9px;
    border: 1px solid rgba(0,0,0,.20);
    border-radius: 8px;
    font-size: 12px;
    background:#fff;
    outline:none;
}
.rep-filters input:focus{
    border-color:#b22b27;
    box-shadow:0 0 0 3px rgba(178,43,39,.12);
}

/* Botones sauté */
.btn{
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #b22b27;
    background: #b22b27;
    color:#fff;
    font-weight: 900;
    font-size: 12px;
    cursor:pointer;
}
.btn:hover{ background:#941c1c; border-color:#941c1c; }
.btn.btn-sec{
    background:#fff;
    color:#6b1818;
    border:1px solid rgba(0,0,0,.25);
}
.btn.btn-sec:hover{ background:#fff8f0; }

/* Estado vacío */
.rep-empty{
    background:#fff8f0;
    border:1px solid rgba(0,0,0,.10);
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 13px;
    color:#333;
}

/* ====== PROVEEDOR CARD ====== */
.prov-bar{
    background:#fff3e4;   /* MISMO crema que uo-chip */
    color:#6b1818;
    padding: 10px 14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-bottom:1px solid rgba(0,0,0,.08);
}

.prov-name{
    font-weight: 900;
    font-size: 14px;
    letter-spacing: .2px;
}

.prov-name{
    font-weight: 900;
    font-size: 13.5px;
    letter-spacing: .2px;
}
.uo-count{
    margin-left:8px;
    padding:2px 8px;
    font-size:11px;
    font-weight:800;
    border-radius:999px;
    background:#b22b27;
    color:#fff;
}


/* ====== UO ====== */
.uo-block{
    padding: 10px 14px 12px;
    border-top: 1px solid rgba(0,0,0,.06);
    background: #fffdf9;
}
.uo-chip{
    display:inline-block;
    background:#fff3e4;
    color:#6b1818;
    font-weight: 900;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid rgba(0,0,0,.06);
    margin-bottom: 8px;
}

/* ====== ITEMS (sin tabla, compacto) ====== */
.items{
    display:flex;
    flex-direction:column;
    gap: 3px;
}
.item{
    display:grid;
    grid-template-columns: 140px 1fr;
    gap: 10px;
    padding: 5px 0;
    border-bottom: 1px dotted rgba(0,0,0,.14);
}
.item:last-child{ border-bottom: none; }

.qty{ white-space:nowrap; font-size: 12px; }
.qty .num{
    font-weight: 900;
    display:inline-block;
    min-width: 52px;
    color:#111;
}
.qty .um{
    color: rgba(0,0,0,.78);
}
.prod{
    font-size: 12px;
    color:#111;
}

/* ====== PRINT ====== */
@media print{
    body{ background:#fff !important; }
    .no-print{ display:none !important; }
    .rep-wrap{
        background:#fff !important;
        box-shadow:none !important;
        border-radius:0 !important;
        padding: 0 !important;
        max-width:none !important;
    }
    .rep-head{
        border-bottom: 1px solid #bbb;
        margin-bottom: 8px;
        padding-bottom: 8px;
    }
    .prov-card{
        box-shadow:none !important;
        border:1px solid #bbb !important;
        page-break-inside: avoid;
    }
    .prov-bar{
        background:#f2f2f2 !important;
        color:#111 !important;
        border-bottom:1px solid #bbb;
    }
    .uo-chip{
        background:#f7f7f7 !important;
        color:#111 !important;
        border:1px solid #ccc !important;
    }
    .uo-count{
    background:#e5e5e5 !important;
    color:#111 !important;
    border:1px solid #bbb;
}

    .item{ border-bottom: 1px dotted #ccc; }
}
</style>
@endsection
