@extends('layouts.dashboard')

@section('titulo', 'Control de Kilometraje')

@section('contenido')

<style>
    .page-wrap{ width:90%; margin:0 auto; max-width:1200px; }
    .panel{ background:#fde7d2; border-radius:14px; padding:18px; box-shadow:0 3px 8px rgba(0,0,0,0.12); }
    .panel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .btn-menu{
        background:#a13c2f; color:#fff; border:none; padding:8px 14px; border-radius:10px;
        font-weight:600; cursor:pointer;
    }
    .btn-menu:hover{ opacity:.9; }

    .filters{ display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin:10px 0 14px; }
    .field{ display:flex; flex-direction:column; gap:6px; min-width:180px; }
    .field label{ font-weight:600; color:#6b2a20; }
    .filters input[type="date"], .filters select{
        padding:8px 10px; border-radius:8px; border:1px solid #d9b699; background:#fff;
    }
    .btn-buscar, .btn{
        background:#b55446; color:#fff; border:none; padding:8px 14px; border-radius:10px;
        cursor:pointer; font-weight:600;
    }
    .btn:hover, .btn-buscar:hover{ opacity:.92; }

    .hint{ background:#fff3e6; border:1px dashed #d9b699; color:#6b2a20; padding:10px 12px; border-radius:10px; }
    .hint-mini{ color:#6b2a20; font-size:.92rem; }
    .toast{ background:#dff7d9; border:1px solid #9fd090; color:#2b6a2e; padding:8px 10px; border-radius:10px; margin-bottom:10px; }
    .toast.error{ background:#fde2e2; border:1px solid #e3a2a2; color:#8a1f1f; }
    .modal-overlay{
        position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:9999;
    }
    .modal{
        background:#fff; border-radius:14px; max-width:520px; width:90%; padding:18px 20px;
        border:2px solid #e3a2a2; box-shadow:0 8px 24px rgba(0,0,0,.25);
    }
    .modal h3{ margin:0 0 8px; color:#8a1f1f; }
    .modal p{ margin:0 0 14px; color:#4b1a1a; }
    .modal .actions{ justify-content:flex-end; }
    .btn-sec{ background:#e6d6c9; color:#5a2a1f; border:none; padding:8px 14px; border-radius:10px; cursor:pointer; }

    .table-wrap{ background:#fff8f1; border:1px solid #e7cdb5; border-radius:12px; padding:10px; }
    .scroll-x{ overflow-x:auto; }
    .tabla{ width:100%; border-collapse:collapse; min-width:980px; }
    .tabla th{ background:#f4d7b8; color:#6b2a20; text-align:left; padding:8px; font-weight:700; }
    .tabla td{ border-top:1px solid #f0d7c0; padding:8px; vertical-align:top; }
    .tabla tr:nth-child(even) td{ background:#fffdf9; }

    .in-num, .in-text{
        width:100%; border:1px solid #d9b699; border-radius:8px; padding:6px 8px; background:#fff;
    }
    .in-text{ resize:vertical; min-height:54px; }
    .actions{ display:flex; justify-content:flex-end; margin-top:10px; }
    .btn-confirmar{ background:#a13c2f; color:#fff; border:none; padding:9px 16px; border-radius:10px; font-weight:700; }
    .btn-confirmar:hover{ opacity:.9; }

    @media (max-width: 768px){
        .filters{ flex-direction:column; align-items:stretch; }
        .field{ min-width:auto; }
        .tabla{ min-width:880px; }
    }
</style>

@php
    $role = auth()->user()->role ?? '';
    $esAdmin = ($role === 'admin');

    $unidadUsuario = auth()->user()->unidad_operativa_id ?? null;
    if (!$esAdmin) {
        $unidadSel = $unidadUsuario;
    }
@endphp

<div class="page-wrap">
    <div class="panel">

        @if(session('ok'))
            <div class="toast">{{ session('ok') }}</div>
        @endif
        @if(session('error'))
            <div class="toast error">{{ session('error') }}</div>
        @endif

        <div class="panel-top">
            <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
                Menú principal
            </button>
            <button class="btn" onclick="window.location.href='{{ route('dashboard.kilometraje.reporte') }}'">
                Ver reporte
            </button>
        </div>

        <h2 style="margin:8px 0 14px;">Control de kilometraje de auto/camioneta</h2>
        <div class="hint-mini" style="margin-bottom:12px;">
            Registro semanal. El sistema calcula <b>Kilómetros recorridos</b> con base en inicio y final.
        </div>

        {{-- ======================
            FILTROS (SEMANA)
        ====================== --}}
        <form id="filtrosForm" class="filters" method="GET" action="{{ route('dashboard.kilometraje') }}">

            @if($esAdmin)
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
            @else
                <input type="hidden" name="unidad_id" value="{{ $unidadSel }}">
            @endif

            <div class="field">
                <label>Semana (inicio)</label>
                <input type="date" name="semana_inicio" value="{{ $inicio->toDateString() }}" required>
            </div>

            <button class="btn-buscar" type="submit">Cargar semana</button>
        </form>

        @if(empty($unidadSel))
            <div class="hint hint-top">
                Selecciona una unidad y presiona <b>“Cargar semana”</b> para habilitar el registro.
            </div>
        @endif

        {{-- ======================
            FORM GUARDAR SEMANA
        ====================== --}}
        <form method="POST" action="{{ route('dashboard.kilometraje.guardarTodo') }}">
            @csrf

            <input type="hidden" name="unidad_operativa_id" value="{{ $unidadSel }}">
            <input type="hidden" name="semana_inicio" value="{{ $inicio->toDateString() }}">

            <div class="table-wrap">
                <div class="scroll-x">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Kilometraje inicio</th>
                                <th>Kilometraje final</th>
                                <th>Kilómetros recorridos</th>
                                <th>Inicio diésel (%)</th>
                                <th>Final diésel (%)</th>
                                <th>Lugares visitados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $cursor = $inicio->copy();
                            @endphp

                            @for($i=0; $i<7; $i++)
                                    @php
                                        $d = $cursor->copy();
                                        $key = $d->toDateString();
                                        $row = $registros->get($key);
                                        $oldRow = old('rows.' . $key, []);
                                        $disabled = empty($unidadSel);
                                    @endphp
                                <tr>
                                    <td style="white-space:nowrap;">
                                        <b>{{ $d->format('d/m/Y') }}</b>
                                        <input type="hidden" name="rows[{{ $key }}][fecha]" value="{{ $key }}">
                                    </td>
                                    <td>
                                        <input
                                            class="in-num km-inicio"
                                            type="number"
                                            min="0"
                                            max="999999"
                                            name="rows[{{ $key }}][km_inicio]"
                                            value="{{ old('rows.' . $key . '.km_inicio', $row->km_inicio ?? '') }}"
                                            data-row="{{ $key }}"
                                            {{ $disabled ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td>
                                        <input
                                            class="in-num km-final"
                                            type="number"
                                            min="0"
                                            max="999999"
                                            name="rows[{{ $key }}][km_final]"
                                            value="{{ old('rows.' . $key . '.km_final', $row->km_final ?? '') }}"
                                            data-row="{{ $key }}"
                                            {{ $disabled ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td>
                                        <input
                                            class="in-num km-rec"
                                            type="number"
                                            min="0"
                                            max="999999"
                                            name="rows[{{ $key }}][km_recorridos]"
                                            value="{{ old('rows.' . $key . '.km_recorridos', $row->km_recorridos ?? '') }}"
                                            data-row="{{ $key }}"
                                            readonly
                                            {{ $disabled ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td>
                                        <input
                                            class="in-num"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            inputmode="decimal"
                                            name="rows[{{ $key }}][diesel_inicio_pct]"
                                            value="{{ old('rows.' . $key . '.diesel_inicio_pct', $row->diesel_inicio_pct ?? '') }}"
                                            {{ $disabled ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td>
                                        <input
                                            class="in-num"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            inputmode="decimal"
                                            name="rows[{{ $key }}][diesel_final_pct]"
                                            value="{{ old('rows.' . $key . '.diesel_final_pct', $row->diesel_final_pct ?? '') }}"
                                            {{ $disabled ? 'disabled' : '' }}
                                        >
                                    </td>
                                    <td>
                                        <textarea
                                            class="in-text"
                                            name="rows[{{ $key }}][lugares_visitados]"
                                            rows="2"
                                            maxlength="1000"
                                            {{ $disabled ? 'disabled' : '' }}
                                        >{{ old('rows.' . $key . '.lugares_visitados', $row->lugares_visitados ?? '') }}</textarea>
                                    </td>
                                </tr>
                                @php $cursor->addDay(); @endphp
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($unidadSel))
                <div class="actions">
                    <button type="submit" class="btn-confirmar">Guardar semana</button>
                </div>
            @endif
        </form>

    </div>
</div>

<div id="modalError" class="modal-overlay" style="display:none;">
    <div class="modal">
        <h3>Faltan datos en la captura</h3>
        <p id="modalErrorMsg">Hay días con información incompleta. Revisa y vuelve a intentar.</p>
        <div class="actions">
            <button type="button" class="btn-sec" onclick="cerrarModalError()">Entendido</button>
        </div>
    </div>
</div>

<script>
function actualizarKmRecorridos(rowKey){
    const ini = document.querySelector(`.km-inicio[data-row="${rowKey}"]`);
    const fin = document.querySelector(`.km-final[data-row="${rowKey}"]`);
    const rec = document.querySelector(`.km-rec[data-row="${rowKey}"]`);
    if(!ini || !fin || !rec) return;

    const vIni = parseFloat(ini.value);
    const vFin = parseFloat(fin.value);
    if (!isNaN(vIni) && !isNaN(vFin)) {
        rec.value = Math.max(0, vFin - vIni);
    } else {
        rec.value = '';
    }
}

document.addEventListener('input', function(e){
    if (e.target.classList.contains('km-inicio') || e.target.classList.contains('km-final')) {
        const key = e.target.getAttribute('data-row');
        actualizarKmRecorridos(key);
    }
});

function cerrarModalError(){
    document.getElementById('modalError').style.display = 'none';
}

@if(session('error'))
    (function(){
        const msg = @json(session('error'));
        const el = document.getElementById('modalError');
        const msgEl = document.getElementById('modalErrorMsg');
        if (el && msgEl) {
            msgEl.textContent = msg;
            el.style.display = 'flex';
        }
    })();
@endif
</script>

@endsection
