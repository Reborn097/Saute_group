@extends('layouts.dashboard')

@section('titulo', 'Registro obligatorio de comensales')

@section('contenido')
<div class="contenedor">
    <h2>Registro obligatorio de comensales (semana pasada)</h2>

    <div class="info">
        <div><b>Periodo:</b> {{ \Carbon\Carbon::parse($inicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }}</div>
        <div><b>Regla:</b> Debes tener al menos un registro por día (Lun–Vie) para tu unidad.</div>
    </div>

    @if(session('success'))
        <div class="alert ok">{{ session('success') }}</div>
    @endif

    @if(empty($faltantes))
        <div class="alert ok">
            ✅ Ya tienes todos los días registrados. Ya puedes continuar.
        </div>

        <div style="margin-top:14px;">
            <a class="btn" href="{{ route('dashboard.pedidos.consultar') }}">Ir al sistema</a>
        </div>
    @else
        <div class="alert warn">
            ⚠️ Faltan registros para: <b>{{ count($faltantes) }}</b> día(s). Debes completar los faltantes para poder continuar.
        </div>

        <form id="formComensales" method="POST" action="{{ route('dashboard.comensales.forzar.guardar') }}">
            @csrf

            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Fecha</th>
                            <th>Cantidad</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dias as $fecha)
                            @php
                                $existe = $existentes->has($fecha);
                                $cantidadExistente = $existe ? (int)($existentes[$fecha]->cantidad ?? 0) : null;

                                $nombreDia = \Carbon\Carbon::parse($fecha)->locale('es')->dayName;
                                $nombreDia = mb_strtoupper(mb_substr($nombreDia,0,1)).mb_substr($nombreDia,1);

                                $val = old("cantidades.$fecha", $cantidadExistente);
                            @endphp

                            <tr class="{{ $existe ? 'row-ok' : 'row-miss' }}">
                                <td>{{ $nombreDia }}</td>
                                <td>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</td>

                                {{-- ✅ name="cantidades[YYYY-MM-DD]" --}}
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        max="99999"
                                        inputmode="numeric"
                                        name="cantidades[{{ $fecha }}]"
                                        value="{{ $val }}"
                                        class="inp"
                                        required
                                    >
                                </td>

                                <td>
                                    @if($existe)
                                        <span class="pill ok">Registrado</span>
                                    @else
                                        <span class="pill warn">Faltante</span>
                                    @endif
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
                <button type="button" class="btn" onclick="validarComensalesAntesDeGuardar()">
                    Guardar registros
                </button>
            </div>
        </form>
    @endif

</div>

{{-- =========================
        MODAL ADVERTENCIA
========================= --}}
<div id="modalAdvertenciaComensales" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Confirmación de captura</h3>

        <p>
            Se detectaron uno o más días con <b>cantidad 0</b> en el registro de comensales.
        </p>

        <p>
            Si el valor es correcto (por ejemplo, no hubo servicio o no se registraron consumos),
            puedes continuar. En caso contrario, ajusta la captura antes de guardar.
        </p>

        <p class="modal-fechas">
            <b>Días detectados:</b><br>
            <span id="modalFechasComensales"></span>
        </p>

        <div class="modal-acciones">
            <button type="button" class="btn-sec" onclick="cerrarModalComensales()">Cancelar</button>
            <button type="button" class="btn" onclick="confirmarGuardadoComensales()">Confirmar y guardar</button>
        </div>
    </div>
</div>

<style>
.contenedor{
    background:#fae7d0;
    padding:22px 28px;
    border-radius:12px;
    max-width:980px;
    margin:0 auto;
    font-family:'Poppins', sans-serif;
    box-shadow:0 4px 8px rgba(0,0,0,0.12);
}
h2{ color:#6b1818; margin:0 0 10px; }

.info{
    background:#fff8f0;
    border:1px solid rgba(0,0,0,.06);
    border-radius:10px;
    padding:12px 14px;
    display:grid;
    gap:6px;
    margin-bottom:14px;
}

.alert{
    padding:10px 12px;
    border-radius:10px;
    margin: 10px 0 14px;
    font-weight:700;
}
.alert.ok{ background:#dcfce7; color:#166534; border:1px solid rgba(0,0,0,.08); }
.alert.warn{ background:#fef3c7; color:#92400e; border:1px solid rgba(0,0,0,.08); }

.tabla-contenedor{ overflow-x:auto; border-radius:10px; }
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    min-width:720px;
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
}

.row-ok{ background:#fbfffb; }
.row-miss{ background:#fff7ed; }

.inp{
    width:110px;
    padding:8px 10px;
    border:1px solid #ddd;
    border-radius:10px;
    text-align:center;
}

/* Pills */
.pill{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-weight:900;
    font-size:.85em;
    border:1px solid rgba(0,0,0,.08);
}
.pill.ok{ background:#dcfce7; color:#166534; }
.pill.warn{ background:#fef3c7; color:#92400e; }

.acciones{
    margin-top:14px;
    display:flex;
    justify-content:flex-end;
}
.btn{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:10px 16px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    text-decoration:none;
    display:inline-block;
}
.btn:hover{ background:#941c1c; }

.btn-sec{
    background:#666;
    color:#fff;
    border:none;
    padding:10px 16px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
}
.btn-sec:hover{ background:#555; }

.errores{
    margin-top:12px;
    background:#fff3cd;
    border:1px solid #ffeeba;
    padding:10px 12px;
    border-radius:10px;
}

/* ===== MODAL ADVERTENCIA (PRO) ===== */
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
</style>

<script>
function validarComensalesAntesDeGuardar(){
    const form = document.getElementById('formComensales');
    if(!form) return;

    const inputs = form.querySelectorAll('input[name^="cantidades["]');
    let fechasCero = [];

    inputs.forEach(inp => {
        const val = parseInt(inp.value || '0', 10);
        if(!isNaN(val) && val === 0){
            // buscar la fecha en la misma fila
            const tr = inp.closest('tr');
            const fechaTxt = tr?.children?.[1]?.textContent?.trim() || '';
            fechasCero.push(fechaTxt);
        }
    });

    if(fechasCero.length === 0){
        form.submit();
        return;
    }

    document.getElementById('modalFechasComensales').innerText = fechasCero.join(', ');
    document.getElementById('modalAdvertenciaComensales').style.display = 'flex';
}

function cerrarModalComensales(){
    document.getElementById('modalAdvertenciaComensales').style.display = 'none';
}

function confirmarGuardadoComensales(){
    document.getElementById('modalAdvertenciaComensales').style.display = 'none';
    document.getElementById('formComensales').submit();
}
</script>
@endsection
