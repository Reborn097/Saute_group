<table class="tabla-corte">
    <thead>
        <tr>
            <th class="col-semana">Semana</th>
            <th>Lunes</th>
            <th>Martes</th>
            <th>Miércoles</th>
            <th>Jueves</th>
            <th>Viernes</th>
            <th>Sábado</th>
            <th>Domingo</th>
            <th class="col-total">Efectivo</th>
            <th class="col-total">Tarjeta</th>
            <th class="col-total">Total semanal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($semanas as $i => $semana)
            <tr class="fila-semana">
                <td class="celda-semana">Semana {{ $i + 1 }}</td>

                @foreach($semana['dias'] as $dia)
                    @php
                        $fecha    = $dia['fecha'];
                        $idBase   = $fecha->format('Ymd');
                        $esMes    = $dia['dentroMes'];
                    @endphp

                    <td class="celda-dia {{ $esMes ? '' : 'dia-inactivo' }}">
                        @if($esMes)
                            <div class="dia-contenido">
                                <div class="dia-fecha">
                                    {{ $fecha->format('d/m') }}
                                </div>
                                <div class="dia-inputs">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="efectivo_{{ $idBase }}"
                                        placeholder="Efectivo"
                                        value="{{ $dia['efectivo'] > 0 ? number_format($dia['efectivo'], 2, '.', '') : '' }}"
                                    >
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="credito_{{ $idBase }}"
                                        placeholder="Tarjeta"
                                        value="{{ $dia['credito'] > 0 ? number_format($dia['credito'], 2, '.', '') : '' }}"
                                    >
                                </div>
                                <button type="button"
                                        class="btn-registrar"
                                        onclick="guardarDia('{{ $fecha->toDateString() }}')">
                                    Registrar
                                </button>
                            </div>
                        @endif
                    </td>
                @endforeach

                <td class="celda-total">
                    {{ number_format($semana['total_efectivo'], 2) }}
                </td>
                <td class="celda-total">
                    {{ number_format($semana['total_credito'], 2) }}
                </td>
                <td class="celda-total">
                    {{ number_format($semana['total_semana'], 2) }}
                </td>
            </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <th colspan="8" class="tfoot-label">Total general:</th>
            <th class="celda-total">{{ number_format($totalesMes['efectivo'], 2) }}</th>
            <th class="celda-total">{{ number_format($totalesMes['credito'], 2) }}</th>
            <th class="celda-total">{{ number_format($totalesMes['total'], 2) }}</th>
        </tr>
    </tfoot>
</table>

<style>
    .tabla-corte {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 3px 6px rgba(0,0,0,0.08);
        font-size: 0.85rem;
    }

    .tabla-corte thead th {
        background-color: #b22b27;
        color: #fff;
        padding: 10px;
        text-align: center;
        font-weight: 600;
    }

    .col-semana {
        width: 90px;
    }

    .col-total {
        width: 110px;
    }

    .tabla-corte tbody td,
    .tabla-corte tfoot th,
    .tabla-corte tfoot td {
        border-bottom: 1px solid #f0d9cc;
        text-align: center;
        padding: 6px 4px;
    }

    .fila-semana:nth-child(odd) {
        background-color: #fff7f0;
    }

    .fila-semana:nth-child(even) {
        background-color: #ffe8d6;
    }

    .celda-semana {
        font-weight: 600;
        color: #7a4b3a;
    }

    .dia-inactivo {
        background-color: #f3e0d4;
        opacity: 0.6;
    }

    .dia-contenido {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .dia-fecha {
        font-size: 0.75rem;
        font-weight: 600;
        color: #7a4b3a;
    }

    .dia-inputs {
        display: flex;
        flex-direction: column;
        gap: 3px;
        width: 100%;
        max-width: 90px;
    }

    .dia-inputs input {
        border-radius: 8px;
        border: 1px solid #d6b7a4;
        padding: 3px 6px;
        font-size: 0.75rem;
        text-align: right;
    }

    .dia-inputs input:focus {
        outline: none;
        border-color: #b22b27;
        box-shadow: 0 0 0 1px rgba(178,43,39,0.25);
    }

    .btn-registrar {
        margin-top: 2px;
        background-color: #333333;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 3px 10px;
        font-size: 0.7rem;
        cursor: pointer;
    }

    .btn-registrar:hover {
        background-color: #000000;
    }

    .celda-total {
        font-weight: 600;
        color: #333;
        background-color: #ffe4c7;
    }

    .tfoot-label {
        text-align: right;
        padding-right: 10px;
        background-color: #f9d6b8;
        font-weight: 700;
        color: #7a4b3a;
    }
</style>
