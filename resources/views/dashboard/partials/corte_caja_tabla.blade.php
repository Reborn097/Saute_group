@php
    use Carbon\Carbon;

    $cursor = $gridInicio->copy();
    $weekNum = 1;

    $totalMesEfectivo = 0;
    $totalMesTarjeta  = 0;
@endphp

<form method="POST" action="{{ route('dashboard.corte-caja.guardarTodo') }}">
    @csrf

    <input type="hidden" name="unidad_id" value="{{ $unidadSel }}">
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
                        <th class="total-col">Efectivo</th>
                        <th class="total-col">Tarjeta</th>
                        <th class="total-col">Total semanal</th>
                    </tr>
                </thead>

                <tbody>
                @while($cursor->lte($gridFin))
                    @php
                        $semEf = 0;
                        $semTa = 0;
                    @endphp

                    <tr>
                        <td class="week-cell">Semana {{ $weekNum }}</td>

                        @for($i=0; $i<7; $i++)
                            @php
                                $d = $cursor->copy();
                                $key = $d->toDateString();
                                $isInMonth = ((int)$d->month === (int)$mes);

                                $row = $registros->get($key);

                                $valEf = $row ? (float)$row->cantidad_efectivo : 0;
                                $valTa = $row ? (float)$row->cantidad_credito : 0;

                                if($isInMonth){
                                    $semEf += $valEf;
                                    $semTa += $valTa;
                                }

                                $disabled = !$isInMonth;
                            @endphp

                            <td class="{{ $isInMonth ? '' : 'off-month' }}">
                                <div class="day-box">
                                    <div class="day-date">{{ $d->format('d/m/Y') }}</div>

                                    <div class="inp-line">
                                        <span class="inp-tag">Efectivo</span>
                                        <input
                                            class="in-num"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="999999"
                                            name="efectivo[{{ $key }}]"
                                            value="{{ number_format($valEf, 2, '.', '') }}"
                                            {{ $disabled ? 'disabled' : '' }}
                                        >
                                    </div>

                                    <div class="inp-line">
                                        <span class="inp-tag">Tarjeta</span>
                                        <input
                                            class="in-num"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="999999"
                                            name="tarjeta[{{ $key }}]"
                                            value="{{ number_format($valTa, 2, '.', '') }}"
                                            {{ $disabled ? 'disabled' : '' }}
                                        >
                                    </div>
                                </div>
                            </td>

                            @php $cursor->addDay(); @endphp
                        @endfor

                        @php
                            $semTotal = $semEf + $semTa;

                            $totalMesEfectivo += $semEf;
                            $totalMesTarjeta  += $semTa;
                        @endphp

                        <td class="sum-col">{{ number_format($semEf, 2) }}</td>
                        <td class="sum-col">{{ number_format($semTa, 2) }}</td>
                        <td class="sum-col">{{ number_format($semTotal, 2) }}</td>
                    </tr>

                    @php $weekNum++; @endphp
                @endwhile

                {{-- Totales del mes --}}
                @php $totalMes = $totalMesEfectivo + $totalMesTarjeta; @endphp

                <tr class="total-row">
                    <td colspan="8" class="total-label">Total efectivo del mes:</td>
                    <td colspan="3" class="total-val">{{ number_format($totalMesEfectivo, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="8" class="total-label">Total tarjeta del mes:</td>
                    <td colspan="3" class="total-val">{{ number_format($totalMesTarjeta, 2) }}</td>
                </tr>
                <tr class="total-row total-row-strong">
                    <td colspan="8" class="total-label">Total general del mes:</td>
                    <td colspan="3" class="total-val">{{ number_format($totalMes, 2) }}</td>
                </tr>

                </tbody>
            </table>
        </div>
    </div>

    <div class="actions">
        <button class="btn-guardar-all" type="submit">Guardar todo</button>
    </div>
</form>

<style>
.table-wrap{
    background:#f9e9d8;
    border-radius:14px;
    border:1px solid #edd7c6;
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
    min-width:1200px;
    border-collapse:separate;
    border-spacing:0;
    background:#fff;
}
table.cal thead th{
    background:#b22b27;
    color:#fff;
    font-weight:900;
    text-align:center;
    padding:9px 6px;
    font-size:.84rem;
    position:sticky;
    top:0;
    z-index:1;
    border-bottom:2px solid #941c1c;
}
.th-week{
    text-align:left;
    padding-left:10px !important;
    width:110px;
}
table.cal td{
    border-bottom:1px solid #f0d3c9;
    padding:6px 6px;
    vertical-align:top;
}
.week-cell{
    font-weight:900;
    color:#7b2b25;
    white-space:nowrap;
    text-align:left;
    padding-left:10px !important;
    background:#fff7f1;
    font-size:.85rem;
}
.day-box{
    display:flex;
    flex-direction:column;
    gap:4px;
    align-items:center;
    justify-content:flex-start;
    min-height:54px;
}
.day-date{
    font-size:.70rem;
    font-weight:900;
    color:#7b2b25;
    opacity:.75;
}
.inp-line{
    display:flex;
    align-items:center;
    gap:6px;
}
.inp-tag{
    font-size:.68rem;
    font-weight:900;
    color:#7b2b25;
    background:#fff3ea;
    border:1px solid #edd7c6;
    padding:3px 6px;
    border-radius:7px;
    min-width:60px;
    text-align:center;
}
.in-num{
    width:86px;
    text-align:center;
    padding:6px 8px;
    border-radius:7px;
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
.total-col{
    width:120px;
}
.sum-col{
    background:#fde3c8;
    font-weight:900;
}
.total-row td{
    background:#fde3c8;
    font-weight:900;
    border-bottom:none;
}
.total-row-strong td{
    background:#f6d3b0;
}
.total-label{
    text-align:right;
    padding-right:12px !important;
}
.total-val{
    text-align:center;
}
.actions{
    display:flex;
    justify-content:flex-end;
    margin-top:10px;
}
.btn-guardar-all{
    background:#b22b27;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:8px 14px;
    font-weight:900;
    cursor:pointer;
}
.btn-guardar-all:hover{
    background:#941c1c;
}
</style>
