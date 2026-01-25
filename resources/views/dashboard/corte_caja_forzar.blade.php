@extends('layouts.dashboard')

@section('titulo', 'Corte de caja — Semana pasada')

@section('contenido')
<div class="contenedor">

    <h2>Captura obligatoria: Corte de caja (semana pasada)</h2>

    <div class="info">
        <p><b>Periodo requerido:</b> {{ \Carbon\Carbon::parse($inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }} (Lun–Vie)</p>

        {{-- ✅ Quitado el texto "Faltan registros en: fechas..." --}}
        @if(empty($faltantes))
            <p class="ok">Ya existen registros, pero puedes corregirlos aquí si lo necesitas.</p>
        @endif

        <p class="nota">
            Mientras falte al menos un día, el sistema te mantendrá en esta pantalla.
        </p>
    </div>

    <form id="formCorteCaja" method="POST" action="{{ route('dashboard.corte-caja.forzar.guardar') }}">
        @csrf

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Efectivo</th>
                        <th>Crédito</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $r)
                        @php
                            $fechaFmt = \Carbon\Carbon::parse($r['fecha'])->format('d/m/Y');
                            $ef = old("rows.$i.cantidad_efectivo", $r['cantidad_efectivo']);
                            $cr = old("rows.$i.cantidad_credito", $r['cantidad_credito']);
                            $tot = (is_numeric($ef) ? (float)$ef : 0) + (is_numeric($cr) ? (float)$cr : 0);
                            $esFaltante = in_array($r['fecha'], $faltantes ?? [], true);
                        @endphp
                        <tr class="{{ $esFaltante ? 'fila-faltante' : '' }}">
                            <td>
                                <b>{{ $fechaFmt }}</b>
                                <input type="hidden" name="rows[{{ $i }}][fecha]" value="{{ $r['fecha'] }}">
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="99999"
                                    inputmode="decimal"
                                    name="rows[{{ $i }}][cantidad_efectivo]"
                                    value="{{ $ef }}"
                                    class="inp monto"
                                    oninput="recalcularFila(this)"
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="99999"
                                    inputmode="decimal"
                                    name="rows[{{ $i }}][cantidad_credito]"
                                    value="{{ $cr }}"
                                    class="inp monto"
                                    oninput="recalcularFila(this)"
                                >
                            </td>

                            <td>
                                <span class="total-fila">$<span class="total-num">{{ number_format($tot, 2) }}</span></span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($errors->any())
            <div class="errores">
                <b>Revisa:</b>
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="acciones">
            <button type="button" class="btn" onclick="validarAntesDeGuardar()">Guardar corte de caja</button>
        </div>
    </form>

    <div id="modalAdvertencia" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <h3>Advertencia de control contable</h3>

            <p>
                Se detectaron uno o más días con <b>importe cero</b> tanto en
                <b>efectivo</b> como en <b>crédito</b>.
            </p>

            <p>
                Antes de continuar, asegúrate de que esta información sea correcta,
                ya que podría generar <b>inconsistencias contables</b> o
                <b>observaciones en auditoría</b>.
            </p>

            <p class="modal-fechas">
                <b>Días detectados:</b><br>
                <span id="modalFechas"></span>
            </p>

            <div class="modal-acciones">
                <button type="button" class="btn-sec" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="btn" onclick="confirmarGuardado()">Confirmar y guardar</button>
            </div>
        </div>
    </div>

</div>

<style>
    /* ===== MODAL ADVERTENCIA ===== */
.modal-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.modal-box{
    background:#fff;
    border-radius:14px;
    max-width:480px;
    width:92%;
    padding:22px 26px;
    box-shadow:0 10px 30px rgba(0,0,0,.25);
    font-family:'Poppins',sans-serif;
}

.modal-box h3{
    margin:0 0 12px;
    color:#6b1818;
    font-size:1.2em;
}

.modal-box p{
    margin:8px 0;
    line-height:1.5;
}

.modal-fechas{
    background:#fff8f0;
    border:1px solid rgba(0,0,0,.08);
    padding:10px 12px;
    border-radius:10px;
    margin-top:10px;
    font-size:.95em;
}

.modal-acciones{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:18px;
}

.btn-sec{
    background:#666;
    color:#fff;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    font-weight:700;
    cursor:pointer;
}
.btn-sec:hover{ background:#555; }

.contenedor{
    background:#fae7d0;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:0 auto;
    font-family:'Poppins',sans-serif;
    box-shadow:0 4px 8px rgba(0,0,0,.15);
}
h2{
    color:#6b1818;
    margin-bottom:10px;
}
.info{
    background:#fff8f0;
    padding:12px 14px;
    border-radius:10px;
    border:1px solid rgba(0,0,0,.08);
    margin-bottom:14px;
}
.alerta{
    background:#fee2e2;
    border:1px solid #fecaca;
    padding:10px 12px;
    border-radius:10px;
    color:#991b1b;
    font-weight:700;
    margin:10px 0;
}
.ok{
    background:#dcfce7;
    border:1px solid #bbf7d0;
    padding:10px 12px;
    border-radius:10px;
    color:#166534;
    font-weight:700;
    margin:10px 0;
}
.nota{
    opacity:.85;
    margin-top:6px;
}

.tabla-contenedor{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    border-radius:10px;
}
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    min-width:720px;
    box-shadow:0 3px 6px rgba(0,0,0,.1);
    table-layout: fixed; /* ✅ evita que “brinque” */
}
.tabla th{
    background:#b22b27;
    color:#fff;
    padding:10px;
    text-align:center;
}
.tabla td{
    padding:10px;
    border-bottom:1px solid #eee;
    text-align:center;
    vertical-align: middle;
}
.fila-faltante td{
    background:#fff3e4;
}

/* ✅ Columnas estables (evita que se mueva la tabla al actualizar totales) */
.tabla th:nth-child(1), .tabla td:nth-child(1){ width: 160px; }
.tabla th:nth-child(2), .tabla td:nth-child(2){ width: 180px; }
.tabla th:nth-child(3), .tabla td:nth-child(3){ width: 180px; }
.tabla th:nth-child(4), .tabla td:nth-child(4){ width: 160px; }

.inp{
    width:100%;
    max-width:180px;
    padding:9px 10px;
    border-radius:10px;
    border:1px solid #ddd;
    text-align:right;
}
.total-fila{
    font-weight:900;
    color:#6b1818;
    display:inline-block;
    min-width: 120px; /* ✅ evita “brinco” por cambio de dígitos */
    text-align:right;
}
.acciones{
    margin-top:14px;
    text-align:right;
}
.btn{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:10px 16px;
    border-radius:10px;
    font-weight:800;
    cursor:pointer;
}
.btn:hover{ background:#941c1c; }

.errores{
    margin-top:12px;
    background:#fff3cd;
    border:1px solid #ffeeba;
    padding:10px 12px;
    border-radius:10px;
}
</style>

<script>
let permitirEnvio = false;

function recalcularFila(input){
    const tr = input.closest('tr');
    if(!tr) return;

    let ef = 0, cr = 0;

    tr.querySelectorAll('input.monto').forEach(el=>{
        const val = parseFloat(el.value || '0');
        if(el.name.includes('cantidad_efectivo')) ef = isNaN(val)?0:val;
        if(el.name.includes('cantidad_credito'))  cr = isNaN(val)?0:val;
    });

    const totalNum = tr.querySelector('.total-num');
    if(totalNum) totalNum.textContent = (ef + cr).toFixed(2);
}

function validarAntesDeGuardar(){
    const filas = document.querySelectorAll('table.tabla tbody tr');
    let fechasCero = [];

    filas.forEach(tr=>{
        const ef = parseFloat(tr.querySelector('input[name*="cantidad_efectivo"]')?.value || '0');
        const cr = parseFloat(tr.querySelector('input[name*="cantidad_credito"]')?.value || '0');

        if((isNaN(ef)?0:ef) === 0 && (isNaN(cr)?0:cr) === 0){
            const fecha = tr.querySelector('td b')?.textContent || '';
            fechasCero.push(fecha);
        }
    });

    if(fechasCero.length === 0){
        document.getElementById('formCorteCaja').submit();
        return;
    }

    document.getElementById('modalFechas').innerText = fechasCero.join(', ');
    document.getElementById('modalAdvertencia').style.display = 'flex';
}

function cerrarModal(){
    document.getElementById('modalAdvertencia').style.display = 'none';
}

function confirmarGuardado(){
    document.getElementById('modalAdvertencia').style.display = 'none';
    document.getElementById('formCorteCaja').submit();
}
</script>


@endsection
