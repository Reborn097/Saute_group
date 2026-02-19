@extends('layouts.dashboard')

@section('titulo', 'Corte de Caja')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    $esAdmin = in_array($role, ['admin', 'responsable_de_unidades'], true); // admin o multi-unidad

    // ✅ Unidad del usuario (AJUSTA si tu columna real se llama distinto)
    $unidadUsuario = auth()->user()->unidad_operativa_id ?? null;

    // ✅ Si NO es admin, forzamos el local seleccionado
    if(!$esAdmin && $role !== 'responsable_de_unidades'){
        $localSel = $unidadUsuario;
    }
@endphp

<div class="page-wrap">
    <div class="panel">

        @if(session('ok'))
            <div class="toast">{{ session('ok') }}</div>
        @endif

        <div class="card-wrap">
            <div class="panel-top">
                <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
                    Menú principal
                </button>
                <div style="width:120px;"></div>
            </div>

            {{-- FILTROS (GET normal) --}}
            <form id="filtrosForm" class="filters" method="GET" action="{{ route('dashboard.corte-caja') }}">
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

                {{-- ✅ LOCAL: Admin lo ve, no-admin va oculto --}}
                @if($esAdmin)
                    <div class="field">
                        <label>Unidad</label>
                        <select name="unidad_id" id="local" required>
                            <option value="">-- Selecciona --</option>
                            @foreach($unidades as $u)
                                <option value="{{ $u->id }}" {{ (string)$localSel === (string)$u->id ? 'selected' : '' }}>
                                    {{ $u->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="unidad_id" value="{{ $localSel }}">
                @endif

                <button class="btn-buscar" type="submit">Buscar</button>
            </form>

            <div class="hint-mini">
                Ingresa montos por día. Límite sugerido por campo: <b>0 a 999999</b>
            </div>

            {{-- FORM guardar todo --}}
            <form method="POST" action="{{ route('dashboard.corte-caja.guardarTodo') }}">
                @csrf

                <input type="hidden" name="unidad_id" value="{{ $localSel }}">
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
                                    <th class="th-sum">Efectivo</th>
                                    <th class="th-sum">Tarjeta</th>
                                    <th class="th-sum">Total semanal</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php
                                    $cursor = $gridInicio->copy();
                                    $weekNum = 1;

                                    $totalMesEf = 0;
                                    $totalMesTa = 0;
                                @endphp

                                @while($cursor->lte($gridFin))
                                    @php
                                        $weekEf = 0;
                                        $weekTa = 0;
                                    @endphp

                                    <tr>
                                        <td class="week-cell">Semana {{ $weekNum }}</td>

                                        @for($i=0; $i<7; $i++)
                                            @php
                                                $d = $cursor->copy();
                                                $isInMonth = ($d->month === (int)$mes);
                                                $key = $d->toDateString();

                                                $ef = $registros->has($key) ? (float)($registros[$key]->cantidad_efectivo ?? 0) : 0;
                                                $ta = $registros->has($key) ? (float)($registros[$key]->cantidad_credito ?? 0)  : 0;

                                                if($isInMonth){
                                                    $weekEf += $ef;
                                                    $weekTa += $ta;

                                                    $totalMesEf += $ef;
                                                    $totalMesTa += $ta;
                                                }

                                                $disabled = !$isInMonth;
                                            @endphp

                                            <td class="{{ $isInMonth ? '' : 'off-month' }}">
                                                <div class="day-box">
                                                    <div class="day-date">{{ $d->format('d/m/Y') }}</div>

                                                    <div class="mini-label">Efectivo</div>
                                                    <input
                                                        class="in-num"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="999999"
                                                        inputmode="decimal"
                                                        name="efectivo[{{ $key }}]"
                                                        value="{{ number_format($ef, 2, '.', '') }}"
                                                        {{ $disabled ? 'disabled' : '' }}
                                                    >

                                                    <div class="mini-label">Tarjeta</div>
                                                    <input
                                                        class="in-num"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="999999"
                                                        inputmode="decimal"
                                                        name="tarjeta[{{ $key }}]"
                                                        value="{{ number_format($ta, 2, '.', '') }}"
                                                        {{ $disabled ? 'disabled' : '' }}
                                                    >
                                                </div>
                                            </td>

                                            @php $cursor->addDay(); @endphp
                                        @endfor

                                        @php $weekTotal = $weekEf + $weekTa; @endphp

                                        <td class="sum-cell">{{ number_format($weekEf, 2) }}</td>
                                        <td class="sum-cell">{{ number_format($weekTa, 2) }}</td>
                                        <td class="sum-cell">{{ number_format($weekTotal, 2) }}</td>
                                    </tr>

                                    @php $weekNum++; @endphp
                                @endwhile

                                @php $totalMes = $totalMesEf + $totalMesTa; @endphp

                                <tr class="totals-row">
                                    <td colspan="8" class="totals-label">Total efectivo del mes:</td>
                                    <td class="totals-val" colspan="3">{{ number_format($totalMesEf, 2) }}</td>
                                </tr>
                                <tr class="totals-row">
                                    <td colspan="8" class="totals-label">Total tarjeta del mes:</td>
                                    <td class="totals-val" colspan="3">{{ number_format($totalMesTa, 2) }}</td>
                                </tr>
                                <tr class="totals-row">
                                    <td colspan="8" class="totals-label"><b>Total general del mes:</b></td>
                                    <td class="totals-val" colspan="3"><b>{{ number_format($totalMes, 2) }}</b></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn-guardar-all" type="submit" {{ empty($localSel) ? 'disabled' : '' }}>
                        Guardar todo
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- ✅ Auto-submit para precargar (solo no-admin y si no viene unidad_id en URL) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const esAdmin = @json($esAdmin);

    if (!esAdmin) {
        const params = new URLSearchParams(window.location.search);
        const tieneUnidad = params.has('unidad_id');

        if (!tieneUnidad) {
            const form = document.getElementById('filtrosForm');
            if (form) form.submit();
        }
    }
});
</script>

<style>
/* (tu mismo CSS intacto) */
    :root{
        --rojo:#b62a24;
        --rojo-osc:#8e1f1a;
        --sombra: 0 10px 22px rgba(0,0,0,.08);
        --fila:#fff3ea;
    }

    .page-wrap{
        width:100%;
        display:flex;
        justify-content:center;
        padding:16px 12px 40px;
        background: var(--beige);
    }

    .panel{
        width:min(1400px, 100%);
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
        margin-bottom:8px;
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
        padding:14px 14px 16px;
        box-shadow: var(--sombra);
    }

    .filters{
        display:grid;
        grid-template-columns: .7fr .6fr 1fr auto;
        gap:10px;
        align-items:end;
        margin: 0 0 8px;
    }

    .field label{
        display:block;
        font-weight:900;
        color:#2b1b19;
        margin-bottom:6px;
        font-size:.88rem;
    }

    .field select{
        width:100%;
        padding:9px 12px;
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
        height:40px;
        box-shadow: 0 4px 10px rgba(0,0,0,.10);
    }
    .btn-buscar:hover{ background: var(--rojo-osc); }

    .hint-mini{
        display:inline-block;
        margin: 0 0 10px;
        background:#fff;
        border:1px solid #edd7c6;
        border-left:6px solid var(--rojo);
        padding:6px 10px;
        border-radius:10px;
        font-weight:800;
        font-size:.82rem;
        color:#6b3a34;
        max-width: fit-content;
    }

    .table-wrap{
        background:#f9e9d8;
        border-radius:14px;
        border:1px solid var(--borde);
        padding:10px;
    }

    .scroll-x{
        overflow-x:auto;
        border-radius:12px;
        background:#fff;
        border:1px solid #edd7c6;
    }

    table.cal{
        width:100%;
        min-width:1180px;
        border-collapse:separate;
        border-spacing:0;
        background:#fff;
    }

    table.cal thead th{
        background: var(--rojo);
        color:#fff;
        font-weight:900;
        text-align:center;
        padding:10px 6px;
        font-size:.86rem;
        position:sticky;
        top:0;
        z-index:1;
        border-bottom:2px solid var(--rojo-osc);
    }

    table.cal thead th:first-child{ border-top-left-radius:10px; }
    table.cal thead th:last-child{ border-top-right-radius:10px; }

    .th-week{
        text-align:left;
        padding-left:12px !important;
        width:120px;
    }

    .th-sum{ width:110px; }

    table.cal tbody tr:nth-child(even) td{ background: var(--fila); }

    table.cal td{
        border-bottom:1px solid #e6c0b8;
        padding:8px 6px;
        vertical-align:top;
    }

    .week-cell{
        font-weight:900;
        color:#7b2b25;
        white-space:nowrap;
        text-align:left;
        padding-left:12px !important;
        background:#fff7f1;
    }

    .day-box{
        display:flex;
        flex-direction:column;
        gap:4px;
        align-items:center;
        justify-content:flex-start;
        min-height:108px;
    }

    .day-date{
        font-size:.76rem;
        font-weight:900;
        color:#7b2b25;
        opacity:.85;
        margin-bottom:1px;
    }

    .mini-label{
        font-size:.70rem;
        font-weight:900;
        color:#7b2b25;
        opacity:.85;
        line-height:1;
    }

    .in-num{
        width:96px;
        text-align:center;
        padding:6px 8px;
        border-radius:8px;
        border:1px solid #d7c2ae;
        background:#fff;
        font-weight:900;
        outline:none;
        font-size:.82rem;
    }

    .off-month{
        opacity:.35;
        pointer-events:none;
        filter: grayscale(.2);
    }

    .sum-cell{
        background:#fde6d9 !important;
        font-weight:900;
        text-align:center;
        vertical-align:middle;
        white-space:nowrap;
    }

    .totals-row td{
        background:#ffe0c2 !important;
        border-bottom:1px solid #f0caa8;
        padding:10px 10px;
    }

    .totals-label{
        text-align:right;
        font-weight:900;
        color:#2b1b19;
    }

    .totals-val{
        text-align:center;
        font-weight:900;
    }

    .actions{
        display:flex;
        justify-content:flex-end;
        margin-top:10px;
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
        margin: 0 0 10px;
    }

    @media (max-width: 980px){
        .filters{ grid-template-columns: 1fr; }
        .btn-buscar{ width:140px; justify-self:end; }
        .actions{ justify-content:flex-end; }
    }
</style>

@endsection

