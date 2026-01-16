@extends('layouts.dashboard')

@section('titulo', 'Registrar comensales')

@section('contenido')

<div class="page-wrap">
    <div class="panel">

        @if(session('ok'))
            <div class="toast">{{ session('ok') }}</div>
        @endif

        <div class="panel-top">
            <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
                Menú principal
            </button>
            <div style="width:120px;"></div>
        </div>

        <div class="card-wrap">

            {{-- Filtros (GET) --}}
            <form class="filters" method="GET" action="{{ route('dashboard.comensales') }}">
                <div class="field">
                    <label>Unidad operativa</label>
                    <select name="unidad_id" required>
                        <option value="">-- Selecciona --</option>
                        @foreach($unidades as $u)
                            <option value="{{ $u->id }}" {{ (string)$unidadSel === (string)$u->id ? 'selected' : '' }}>
                                {{ $u->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Mes</label>
                    <select name="mes" required>
                        @php
                            $meses = [
                                1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                                7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
                            ];
                        @endphp
                        @foreach($meses as $num=>$nom)
                            <option value="{{ $num }}" {{ (int)$mes === (int)$num ? 'selected' : '' }}>
                                {{ $nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Año</label>
                    <select name="anio" required>
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ (int)$anio === (int)$y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <button class="btn-buscar" type="submit">Buscar</button>
            </form>

            {{-- Instrucción arriba --}}
            @if(empty($unidadSel))
                <div class="hint hint-top">
                    Selecciona una unidad y presiona <b>“Buscar”</b> para habilitar el registro.
                </div>
            @else
                <div class="hint hint-top">
                    Límite por día: <b>0 a 999</b>
                </div>
            @endif

            {{-- FORM ÚNICO --}}
            <form method="POST" action="{{ route('dashboard.comensales.guardarTodo') }}">
                @csrf

                <input type="hidden" name="unidad_operativa_id" value="{{ $unidadSel }}">
                <input type="hidden" name="anio" value="{{ $anio }}">
                <input type="hidden" name="mes" value="{{ $mes }}">

                <div class="table-wrap">
                    <div class="scroll-x">
                        <table class="cal">
                            <thead>
                                <tr>
                                    <th class="th-week">Semana</th>
                                    <th>Lunes</th>
                                    <th>Martes</th>
                                    <th>Miércoles</th>
                                    <th>Jueves</th>
                                    <th>Viernes</th>
                                    <th>Sábado</th>
                                    <th>Domingo</th>
                                    {{-- ✅ NUEVA COLUMNA --}}
                                    <th class="th-total">Total semanal</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php
                                    $cursor = $gridInicio->copy();
                                    $weekNum = 1;
                                    $totalMes = 0;
                                @endphp

                                @while($cursor->lte($gridFin))
                                    @php $totalSemana = 0; @endphp

                                    <tr>
                                        <td class="week-cell">
                                            Semana {{ $weekNum }}
                                        </td>

                                        @for($i=0; $i<7; $i++)
                                            @php
                                                $d = $cursor->copy();
                                                $isInMonth = ($d->month === (int)$mes);
                                                $key = $d->toDateString();

                                                $valor = $registros->has($key) ? (int)$registros[$key]->cantidad : 0;

                                                // si está dentro del mes, suma a totales
                                                if ($isInMonth) {
                                                    $totalSemana += $valor;
                                                    $totalMes += $valor;
                                                }

                                                $disabled = empty($unidadSel) || !$isInMonth;
                                            @endphp

                                            <td class="{{ $isInMonth ? '' : 'off-month' }}">
                                                <div class="day-box">
                                                    <div class="day-date">{{ $d->format('d/m/Y') }}</div>

                                                    <input
                                                        class="in-num"
                                                        type="number"
                                                        name="cantidades[{{ $key }}]"
                                                        value="{{ $valor }}"
                                                        min="0"
                                                        max="999"
                                                        maxlength="3"
                                                        oninput="this.value = this.value.slice(0, 3)"
                                                        {{ $disabled ? 'disabled' : '' }}
                                                    >
                                                </div>
                                            </td>

                                            @php $cursor->addDay(); @endphp
                                        @endfor

                                        {{-- ✅ TOTAL SEMANAL (solo suma de días del mes) --}}
                                        <td class="week-total">
                                            {{ $totalSemana }}
                                        </td>
                                    </tr>

                                    @php $weekNum++; @endphp
                                @endwhile

                                {{-- ✅ FILA FINAL: TOTAL DEL MES --}}
                                <tr class="row-total-mes">
                                    <td class="label-total-mes" colspan="8">
                                        Total de comensales del mes
                                    </td>
                                    <td class="value-total-mes">
                                        {{ $totalMes }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Botón único --}}
                <div class="actions">
                    <button
                        class="btn-guardar-all"
                        type="submit"
                        {{ empty($unidadSel) ? 'disabled' : '' }}
                    >
                        Guardar todo
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
    :root{
        --rojo:#b62a24;
        --rojo-osc:#8e1f1a;

        --sombra: 0 10px 22px rgba(0,0,0,.08);

        --cab: var(--rojo);
        --fila:#fff3ea;
    }

    .page-wrap{
        width:100%;
        display:flex;
        justify-content:center;
        padding:28px 12px 60px;
        background: var(--beige);
    }

    .panel{
        width:min(1350px, 100%);
        padding:0;
        background: transparent;
        border:none;
        box-shadow:none;
    }

    .panel-top{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        margin-bottom:12px;
    }

    .btn-menu{
        background: var(--rojo);
        border:none;
        color:#fff;
        padding:8px 14px;
        border-radius:10px;
        font-weight:800;
        cursor:pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,.12);
        white-space:nowrap;
    }
    .btn-menu:hover{ background: var(--rojo-osc); }

    .card-wrap{
        background: #fceede;
        border:1px solid #fceede;
        border-radius:18px;
        padding:18px 18px 20px;
        box-shadow: var(--sombra);
    }

    .filters{
        display:grid;
        grid-template-columns: 1.25fr .7fr .6fr auto;
        gap:12px;
        align-items:end;
        margin: 4px 0 14px;
    }

    .field label{
        display:block;
        font-weight:900;
        color:#2b1b19;
        margin-bottom:6px;
        font-size:.9rem;
    }

    .field select{
        width:100%;
        padding:10px 12px;
        border-radius:10px;
        border:1px solid #d7c2ae;
        background:#fff;
        outline:none;
    }

    .btn-buscar{
        background: var(--rojo);
        color:#fff;
        border:none;
        border-radius:10px;
        padding:10px 16px;
        font-weight:900;
        cursor:pointer;
        height:42px;
        align-self:end;
        box-shadow: 0 4px 10px rgba(0,0,0,.10);
    }
    .btn-buscar:hover{ background: var(--rojo-osc); }

    .hint{
        font-weight:800;
        font-size:.92rem;
        color:#6b3a34;
    }
    .hint-top{
        margin: 0 0 12px;
        background:#fff;
        border:1px solid #edd7c6;
        border-left:6px solid var(--rojo);
        padding:10px 12px;
        border-radius:12px;
    }

    .table-wrap{
        background:#f9e9d8;
        border-radius:14px;
        border:1px solid var(--borde);
        padding:12px;
    }

    .scroll-x{
        overflow-x:auto;
        border-radius:12px;
        background:#fff;
        border:1px solid #edd7c6;
    }

    table.cal{
        width:100%;
        min-width:1100px; /* + una columna más */
        border-collapse:separate;
        border-spacing:0;
        background:#fff;
    }

    table.cal thead th{
        background: var(--cab);
        color:#fff;
        font-weight:900;
        text-align:center;
        padding:11px 8px;
        font-size:.9rem;
        position:sticky;
        top:0;
        z-index:1;
        border-bottom:2px solid var(--rojo-osc);
        white-space:nowrap;
    }

    table.cal thead th:first-child{ border-top-left-radius:10px; }
    table.cal thead th:last-child{ border-top-right-radius:10px; }

    .th-week{
        text-align:left;
        padding-left:14px !important;
        width:130px;
    }

    .th-total{
        width:140px;
    }

    table.cal tbody tr:nth-child(even) td{
        background: var(--fila);
    }

    table.cal td{
        border-bottom:1px solid #e6c0b8;
        padding:10px 8px;
        vertical-align:top;
        text-align:center;
    }

    .week-cell{
        font-weight:900;
        color:#7b2b25;
        white-space:nowrap;
        text-align:left;
        padding-left:14px !important;
        background:#fff7f1;
    }

    .day-box{
        display:flex;
        flex-direction:column;
        gap:6px;
        align-items:center;
        justify-content:flex-start;
        min-height:76px;
    }

    .day-date{
        font-size:.78rem;
        font-weight:900;
        color:#7b2b25;
        opacity:.85;
    }

    .in-num{
        width:110px;
        text-align:center;
        padding:8px 10px;
        border-radius:8px;
        border:1px solid #d7c2ae;
        background:#fff;
        font-weight:900;
        outline:none;
    }

    .off-month{
        opacity:.35;
        pointer-events:none;
        filter: grayscale(.2);
    }

    /* ✅ Total semanal */
    .week-total{
        font-weight:900;
        color:#2b1b19;
        background:#ffe6cf !important;
        vertical-align:middle;
        border-left:1px solid #f2c8b6;
    }

    /* ✅ Total mes (fila final) */
    .row-total-mes td{
        background:#ffe0c0 !important;
        border-bottom:none;
    }
    .label-total-mes{
        text-align:right;
        font-weight:900;
        color:#2b1b19;
        padding-right:14px !important;
    }
    .value-total-mes{
        font-weight:900;
        color:#2b1b19;
        border-left:1px solid #f2c8b6;
    }

    .actions{
        display:flex;
        justify-content:flex-end;
        margin-top:12px;
    }

    .btn-guardar-all{
        background: var(--rojo);
        color:#fff;
        border:none;
        border-radius:10px;
        padding:10px 16px;
        font-weight:900;
        cursor:pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,.10);
    }
    .btn-guardar-all:hover{ background: var(--rojo-osc); }

    .btn-guardar-all:disabled{
        opacity:.5;
        cursor:not-allowed;
    }

    .toast{
        background:#e7ffe6;
        border:1px solid #a6e7a5;
        color:#1f6b1e;
        padding:10px 12px;
        border-radius:12px;
        font-weight:900;
        margin: 0 0 12px;
    }

    @media (max-width: 980px){
        .filters{ grid-template-columns: 1fr; }
        .btn-buscar{ width:140px; justify-self:end; }
        .actions{ justify-content:flex-end; }
    }
</style>

@endsection
