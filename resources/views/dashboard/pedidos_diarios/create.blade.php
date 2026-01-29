@extends('layouts.dashboard')

@section('titulo', 'Pedido Diario ' . (($tipo ?? '') === 'PAN' ? 'Pan' : 'Tortilla'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pedidos-diarios.css') }}">
@endpush

@section('contenido')

@php
    $esPan = (($tipo ?? '') === 'PAN');

    // Rutas
    $routeCreate = $esPan
        ? route('dashboard.pedidos_diarios.pan.create')
        : route('dashboard.pedidos_diarios.tortilla.create');

    $routeStore = $esPan
        ? route('dashboard.pedidos_diarios.pan.store')
        : route('dashboard.pedidos_diarios.tortilla.store');

    $unidadSeleccionada = old('unidad_operativa_id')
        ?? request('unidad_operativa_id')
        ?? ($pedido->unidad_operativa_id ?? null);

    $fechaReferencia = old('fecha_referencia')
        ?? request('fecha')
        ?? ($semana_inicio ?? now()->toDateString());

    $semanaInicio = $semana_inicio ?? ($pedido->semana_inicio ?? null);
    $semanaFin    = $semana_fin    ?? ($pedido->semana_fin ?? null);

    $diasLabel = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

    // ✅ Bloqueo si ya no es editable
    $estadoRaw = trim((string)($pedido->estado ?? ''));
    $bloqueado = in_array($estadoRaw, ['Preaprobado', 'Aprobado']);
@endphp

<div class="contenedor pd-contenedor">

    {{-- ================= ACCIONES SUPERIORES ================= --}}
    <div class="pd-acciones-superior">
        <div class="pd-acciones-left">
            <button class="btn-menu" type="button" onclick="irMenuPrincipal()">Menú principal</button>
        </div>

        <div class="pd-acciones-right">
            <button class="btn" type="button"
                onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pan.create') }}'">
                Pedido PAN
            </button>

            <button class="btn" type="button"
                onclick="window.location.href='{{ route('dashboard.pedidos_diarios.tortilla.create') }}'">
                Pedido TORTILLA
            </button>
        </div>
    </div>

    {{-- ============================ FILTROS ============================= --}}
    <div class="pd-filtros-wrap">
        <div class="pd-filtros-grid pd-filtros-grid-create">

            {{-- ✅ Mostrar código cuando ya existe --}}
            @if(($modo ?? 'create') === 'edit' && !empty($pedido?->codigo))
                <div class="pd-filtro">
                    <label class="pd-label">Código:</label>
                    <input type="text" value="{{ $pedido->codigo }}" readonly>
                </div>
            @endif

            <div class="pd-filtro">
                <label class="pd-label">Tipo de pedido:</label>
                <input type="text" value="{{ $esPan ? 'PAN' : 'TORTILLA' }}" readonly>
            </div>

            <div class="pd-filtro pd-col-span-2">
                <label class="pd-label">Unidad operativa:</label>
                <select id="unidadOperativa" {{ $bloqueado ? 'disabled' : '' }}>
                    <option value="">Selecciona...</option>
                    @foreach($unidades as $u)
                        <option value="{{ $u->id }}" {{ (string)$unidadSeleccionada === (string)$u->id ? 'selected' : '' }}>
                            {{ $u->nombre }}
                        </option>
                    @endforeach
                </select>



                @if($bloqueado)
                    <small class="pd-helper pd-helper-lock">🔒 Pedido bloqueado ({{ $estadoRaw }})</small>
                @endif
            </div>

            <div class="pd-filtro">
                <label class="pd-label">Fecha de referencia:</label>
                <input type="date" id="fechaReferencia" value="{{ $fechaReferencia }}" {{ $bloqueado ? 'disabled' : '' }}>
            </div>

            <div class="pd-filtro">
                <label class="pd-label">Semana inicio (lunes):</label>
                <input type="date" id="semanaInicio" value="{{ $semanaInicio }}" readonly>
            </div>

            <div class="pd-filtro">
                <label class="pd-label">Semana fin (domingo):</label>
                <input type="date" id="semanaFin" value="{{ $semanaFin }}" readonly>
            </div>
            <small class="pd-helper">Se calcula la semana (lunes–domingo) automáticamente.</small>
            <div class="pd-filtro pd-col-span-3">
                <label class="pd-label">Observaciones:</label>
                <input type="text"
                       id="observaciones"
                       form="formPedidoDiario"
                       name="observaciones"
                       value="{{ old('observaciones', $pedido->observaciones ?? '') }}"
                       placeholder="Opcional"
                       {{ $bloqueado ? 'readonly' : '' }}>
            </div>

        </div>
    </div>

    {{-- ============================ TABLA CALENDARIO ============================= --}}
    <h3 class="pd-titulo">Captura por día</h3>

    @if ($errors->any())
        <div class="pd-alerta">
            <strong>Revisa esto:</strong>
            <ul style="margin:8px 0 0 18px;">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="formPedidoDiario" method="POST" action="{{ $routeStore }}">
        @csrf

        <input type="hidden" name="fecha_referencia" id="fechaReferenciaHidden" value="{{ $fechaReferencia }}">
        <input type="hidden" name="unidad_operativa_id" id="unidadOperativaHidden" value="{{ $unidadSeleccionada }}">

        <div class="pd-tabla-contenedor ">
            <table class="pd-tabla pd-tabla-calendario" id="tablaCalendario">
                <thead>
                <tr>
                    <th class="col-producto">Producto</th>
                    <th class="col-unidad">Unidad</th>
                    <th class="col-precio">Precio unitario</th>

                    @foreach($days as $i => $d)
                        <th class="col-dia">
                            <div class="pd-dia-head">
                                <div class="pd-dia-nombre">{{ $diasLabel[$i] }}</div>
                                <div class="pd-dia-fecha">{{ \Carbon\Carbon::parse($d)->format('d/m') }}</div>
                            </div>
                        </th>
                    @endforeach

                    <th class="col-total">Total (cant)</th>
                    <th class="col-total-mxn">Total $</th>
                </tr>
                </thead>

                <tbody>
                    @forelse($presentaciones as $pres)
                        @php
                            // ✅ ahora el "ID" de la fila es la PRESENTACIÓN
                            $presId = $pres->id;

                            // ✅ producto real (para mostrar nombre y sacar proveedor/precio legacy)
                            $prod = $pres->producto;

                            // ✅ unidad: prioridad unidad_base, luego unidad_contenido
                            $unidad = $pres->unidad_base ?? $pres->unidad_contenido ?? 'N/A';

                            // ✅ Precio (fase 1): lo seguimos tomando del producto -> proveedor vigente (legacy)
                            // (En fase 2 podríamos traer precio por presentacion/proveedor si lo necesitas)
                            $prov = $prod?->proveedores?->first();
                            $precio = $prov ? (float)($prov->pivot->precio ?? 0) : 0;

                            $rowTotalCant = 0;
                        @endphp

                        <tr data-precio="{{ $precio }}">
                            <td class="col-producto" title="{{ $prod->nombre ?? '' }}">
                                <strong>{{ $prod->nombre ?? 'Producto' }}</strong>
                                <div style="font-size:12px; opacity:.80;">
                                    {{ $pres->descripcion ?? '' }}
                                </div>
                            </td>

                            <td class="col-unidad">{{ $unidad }}</td>

                            <td class="col-precio">
                                ${{ number_format($precio, 2) }}
                                <div style="font-size:12px; opacity:.75;">
                                    {{ $prov->nombre ?? '' }}
                                </div>
                            </td>

                            @foreach($days as $d)
                                @php
                                    // ✅ ahora cantidades se indexa por presentacion_id
                                    $val = old("cantidades.$presId.$d") ?? ($cantidades[$presId][$d] ?? '');
                                    $num = is_numeric($val) ? (float)$val : 0;
                                    $rowTotalCant += $num;
                                @endphp

                                <td class="col-dia">
                                    @if($esPan)
                                        <input
                                            class="pd-inp-cant inp-pan"
                                            type="number"
                                            name="cantidades[{{ $presId }}][{{ $d }}]"
                                            value="{{ $val }}"
                                            min="0"
                                            max="999"
                                            step="1"
                                            inputmode="numeric"
                                            oninput="limitarEnteros(this); recalcularTotales();"
                                            placeholder="0"
                                            {{ $bloqueado ? 'disabled' : '' }}
                                        >
                                    @else
                                        <input
                                            class="pd-inp-cant inp-tortilla"
                                            type="number"
                                            name="cantidades[{{ $presId }}][{{ $d }}]"
                                            value="{{ $val }}"
                                            min="0"
                                            step="0.01"
                                            inputmode="decimal"
                                            oninput="limitarDecimales(this, 2); recalcularTotales();"
                                            placeholder="0.00"
                                            {{ $bloqueado ? 'disabled' : '' }}
                                        >
                                    @endif
                                </td>
                            @endforeach

                            <td class="col-total">
                                <span class="pd-badge-total" data-row-total-cant>
                                    {{ number_format($rowTotalCant, $esPan ? 0 : 2) }}
                                </span>
                            </td>

                            <td class="col-total-mxn">
                                <span class="pd-badge-total pd-badge-total--grand" data-row-total-mxn>$0.00</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + 7 + 2 }}" style="padding:14px; text-align:center;">
                                No hay presentaciones para {{ $esPan ? 'PAN' : 'TORTILLA' }}.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>


                <tfoot>
                <tr>
                    <th colspan="3" style="text-align:right;">Total por día (cant)</th>
                    @foreach($days as $d)
                        <th class="tfoot-dia">
                            <span class="pd-badge-total" data-col-total-cant="{{ $d }}">0</span>
                        </th>
                    @endforeach
                    <th>
                        <span class="pd-badge-total" id="grandTotalCant">0</span>
                    </th>
                    <th></th>
                </tr>

                <tr>
                    <th colspan="3" style="text-align:right;">Total por día ($)</th>
                    @foreach($days as $d)
                        <th class="tfoot-dia">
                            <span class="pd-badge-total" data-col-total-mxn="{{ $d }}">$0.00</span>
                        </th>
                    @endforeach
                    <th></th>
                    <th>
                        <span class="pd-badge-total pd-badge-total--grand" id="grandTotalMxn">$0.00</span>
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>

        <div class="pd-acciones-final">
            @if(!$bloqueado)
                <button type="button" class="btn-cancelar" onclick="limpiarCeldas()">Limpiar cantidades</button>
                <button type="submit" class="btn-confirmar">
                    {{ ($modo ?? 'create') === 'edit' ? 'Actualizar pedido' : 'Guardar pedido' }}
                </button>
            @else
                <span class="pd-badge-total pd-badge-total--grand">🔒 Pedido bloqueado ({{ $estadoRaw }})</span>
                <button type="button" class="btn btn-pdf"
                    onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pdf', $pedido->id) }}'">
                    Descargar PDF
                </button>
            @endif
        </div>
    </form>

</div>

<script>
function irMenuPrincipal(){
    window.location.href = "{{ route('dashboard.admin') }}";
}

function pad2(n){ return (n < 10 ? '0' : '') + n; }

function getMonday(d){
    const day = d.getDay(); // 0 dom - 6 sab
    const diff = (day === 0 ? -6 : 1) - day; // lunes
    const monday = new Date(d);
    monday.setDate(d.getDate() + diff);
    monday.setHours(0,0,0,0);
    return monday;
}

function formatDateYYYYMMDD(d){
    return d.getFullYear() + '-' + pad2(d.getMonth()+1) + '-' + pad2(d.getDate());
}

function setSemanaFromReferencia(){
    const ref = document.getElementById('fechaReferencia');
    const ini = document.getElementById('semanaInicio');
    const fin = document.getElementById('semanaFin');

    if(!ref || !ref.value) return;

    const d = new Date(ref.value + 'T00:00:00');
    const monday = getMonday(d);
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    if(ini) ini.value = formatDateYYYYMMDD(monday);
    if(fin) fin.value = formatDateYYYYMMDD(sunday);

    const hidden = document.getElementById('fechaReferenciaHidden');
    if(hidden) hidden.value = ref.value;
}

function recargarConFiltros(){
    const unidadEl = document.getElementById('unidadOperativa');
    const fechaEl  = document.getElementById('fechaReferencia');

    const unidad = unidadEl ? unidadEl.value : '';
    const fecha  = fechaEl ? fechaEl.value : '';

    const hidU = document.getElementById('unidadOperativaHidden');
    const hidF = document.getElementById('fechaReferenciaHidden');

    if(hidU) hidU.value = unidad;
    if(hidF) hidF.value = fecha;

    const url = new URL("{{ $routeCreate }}", window.location.origin);
    if(unidad) url.searchParams.set('unidad_operativa_id', unidad);
    if(fecha)  url.searchParams.set('fecha', fecha);

    window.location.href = url.toString();
}

function limitarEnteros(input){
    let v = (input.value || '').replace(/[^\d]/g, '');
    if(v.length > 3) v = v.slice(0,3);
    input.value = v;
}

function limitarDecimales(input, dec){
    let v = (input.value || '').toString();
    v = v.replace(/[^0-9.]/g, '');

    const parts = v.split('.');
    if(parts.length > 2){
        v = parts[0] + '.' + parts.slice(1).join('');
    }

    const p2 = v.split('.');
    if(p2.length === 2 && p2[1].length > dec){
        v = p2[0] + '.' + p2[1].slice(0, dec);
    }

    if(v.length > 8) v = v.slice(0,8);
    input.value = v;
}

function limpiarCeldas(){
    document.querySelectorAll('.pd-inp-cant').forEach(inp => {
        if(!inp.disabled) inp.value = '';
    });
    recalcularTotales();
}

function money(n){
    const v = isNaN(n) ? 0 : n;
    return '$' + v.toFixed(2);
}

function recalcularTotales(){
    const tabla = document.getElementById('tablaCalendario');
    if(!tabla) return;

    const esPan = @json($esPan);
    const days = @json($days ?? []);

    let grandCant = 0;
    let grandMxn  = 0;

    const colCant = {};
    const colMxn  = {};
    days.forEach(d => { colCant[d]=0; colMxn[d]=0; });

    tabla.querySelectorAll('tbody tr').forEach(tr => {
        const precio = parseFloat(tr.getAttribute('data-precio') || '0') || 0;

        let rowCant = 0;
        let rowMxn  = 0;

        tr.querySelectorAll('input.pd-inp-cant').forEach(inp => {
            const name = inp.getAttribute('name') || '';
            const match = name.match(/\[(\d{4}-\d{2}-\d{2})\]$/);
            const fecha = match ? match[1] : null;

            const val = parseFloat(inp.value);
            const cant = isNaN(val) ? 0 : val;

            rowCant += cant;
            rowMxn  += cant * precio;

            if(fecha && Object.prototype.hasOwnProperty.call(colCant, fecha)){
                colCant[fecha] += cant;
                colMxn[fecha]  += cant * precio;
            }
        });

        grandCant += rowCant;
        grandMxn  += rowMxn;

        const badgeCant = tr.querySelector('[data-row-total-cant]');
        if(badgeCant){
            badgeCant.textContent = esPan ? rowCant.toFixed(0) : rowCant.toFixed(2);
        }

        const badgeMxn = tr.querySelector('[data-row-total-mxn]');
        if(badgeMxn){
            badgeMxn.textContent = money(rowMxn);
        }
    });

    days.forEach(d => {
        const bc = document.querySelector(`[data-col-total-cant="${d}"]`);
        if(bc) bc.textContent = esPan ? colCant[d].toFixed(0) : colCant[d].toFixed(2);

        const bm = document.querySelector(`[data-col-total-mxn="${d}"]`);
        if(bm) bm.textContent = money(colMxn[d]);
    });

    const gCant = document.getElementById('grandTotalCant');
    if(gCant) gCant.textContent = esPan ? grandCant.toFixed(0) : grandCant.toFixed(2);

    const gMxn = document.getElementById('grandTotalMxn');
    if(gMxn) gMxn.textContent = money(grandMxn);
}

document.addEventListener('DOMContentLoaded', () => {
    setSemanaFromReferencia();
    recalcularTotales();

    const bloqueado = @json($bloqueado);

    if(!bloqueado){
        const fechaEl = document.getElementById('fechaReferencia');
        const unidadEl = document.getElementById('unidadOperativa');

        if(fechaEl){
            fechaEl.addEventListener('change', () => {
                setSemanaFromReferencia();
                recargarConFiltros();
            });
        }

        if(unidadEl){
            unidadEl.addEventListener('change', () => {
                recargarConFiltros();
            });
        }
    }

    const uH = document.getElementById('unidadOperativaHidden');
    const fH = document.getElementById('fechaReferenciaHidden');
    const u  = document.getElementById('unidadOperativa');
    const f  = document.getElementById('fechaReferencia');

    if(uH && u) uH.value = u.value;
    if(fH && f) fH.value = f.value;
});
</script>

@endsection
