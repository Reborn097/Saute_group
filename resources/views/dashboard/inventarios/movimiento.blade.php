@extends('layouts.dashboard')
@section('titulo','Movimiento de Inventario')

@section('contenido')
@php
    $role = auth()->user()->role ?? '';
    $esAdmin = $role === 'admin';
@endphp

<div class="contenedor">
    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Men&uacute; principal
        </button>

        {{-- OK Evita loop del previous --}}
        <button class="btn-regresar"
            onclick="window.location.href='{{ route('inventarios.index') }}'">
            Regresar
        </button>
    </div>

    <h2>Registrar movimientos</h2>

    @if($errors->any())
        @php
            $primerError = (string) $errors->first();
            $errorStock = str_contains(mb_strtolower($primerError), 'no hay suficiente inventario');
        @endphp
        <div class="alerta-error" role="alert">
            <strong>
                {{ $errorStock
                    ? 'No se pudo registrar la transferencia por inventario insuficiente.'
                    : 'No se pudieron guardar los movimientos.' }}
            </strong>
            <div class="alerta-error-msg">{{ $primerError }}</div>

            @if($errors->count() > 1)
                <ul class="alerta-error-lista">
                    @foreach(array_slice($errors->all(), 1) as $errorExtra)
                        <li>{{ $errorExtra }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- OK SOLO ADMIN: filtro de Unidad Operativa (GET) en UNA FILA con scroll --}}
    @if($esAdmin)
        <form method="GET" action="{{ route('inventarios.movimiento.form') }}" class="filtros-uno">
            <div class="campo-filtro">
                <label>Unidad operativa:</label>
                <select name="unidad_operativa_id" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach(($unidadesOperativas ?? []) as $uo)
                        <option value="{{ $uo->id }}" {{ (string)($uoId ?? '') === (string)$uo->id ? 'selected' : '' }}>
                            {{ $uo->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <small class="hint" style="margin:0;">
                Filtra los almacenes disponibles.
            </small>
        </form>
    @endif

    {{-- FORM PRINCIPAL (se manda hasta el final) --}}
    <form id="formMovimientos" method="POST" action="{{ route('inventarios.movimiento.store') }}">
        @csrf

        <div class="grid">

            <div>
                <label>Almac&eacute;n</label>
                <select id="almacen_id" name="almacen_id" required>
                    <option value="">- Selecciona -</option>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                    @endforeach
                </select>
                <small class="hint">El almac&eacute;n aplica a todos los renglones.</small>
            </div>

            <div class="presentacion-buscador" style="grid-column:1/-1;">
                <label>Presentaci&oacute;n</label>
                <input id="presentacionSearch" type="text" placeholder="Buscar presentaci&oacute;n...">
                <input type="hidden" id="presentacion_id">
                <small class="hint" id="presentacionSeleccionada"></small>
                <div class="tabla-wrap tabla-resultados">
                    <table class="tabla" id="tablaResultados">
                        <thead>
                            <tr>
                                <th>Coincidencias</th>
                                <th style="width:90px;">Acci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyResultados">
                            <tr>
                                <td colspan="2" class="vacio">Escribe para buscar.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <label>Tipo</label>
                <select id="tipo_movimiento">
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="ajuste">Ajuste (cantidad final)</option>
                    @if($esAdmin)
                        <option value="transferencia">Transferencia</option>
                    @endif
                </select>
            </div>

            @if($esAdmin)
                <div id="destinoWrap" style="display:none;">
                    <label>Almac&eacute;n destino (transferencia)</label>
                    <select id="destino_almacen_id">
                        <option value="">- Selecciona -</option>
                        @foreach(($almacenesDestino ?? []) as $ad)
                            <option value="{{ $ad->id }}">{{ $ad->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label>Cantidad</label>
                <input id="cantidad" type="number" min="0.01" max="99999" step="0.01" maxlength="5" placeholder="Ej. 2.5">
            </div>

            <div>
                <label>Lote (opcional)</label>
                <input id="lote" type="text" maxlength="15" placeholder="Ej. L-2026-01">
            </div>

            <div>
                <label>Caducidad (opcional)</label>
                <input id="caducidad" type="date">
            </div>

            <div style="grid-column:1/-1;">
                <label>Motivo (opcional)</label>
                <input id="motivo" type="text" maxlength="30" placeholder="Ej. Compra / merma / ajuste por conteo">
            </div>

        </div>

        <div class="acciones">
            <button class="btn-secundario" type="button" id="btnAgregar">Agregar a lista</button>
            <button class="btn-secundario" type="button" id="btnLimpiar">Limpiar captura</button>
        </div>

        <hr class="separador">

        <h3 class="subtitulo">Lista de movimientos a guardar</h3>

        {{-- OK tabla con scroll horizontal si no caben columnas --}}
        <div class="tabla-wrap">
            <table class="tabla" id="tablaItems">
                <thead>
                    <tr>
                        <th style="width:28%;">Presentaci&oacute;n</th>
                        <th style="width:12%;">Tipo</th>
                        <th style="width:10%;">Cantidad</th>
                        <th style="width:15%;">Lote</th>
                        <th style="width:15%;">Caducidad</th>
                        <th style="width:18%;">Destino</th>
                        <th>Motivo</th>
                        <th style="width:8%;">Acci&oacute;n</th>
                    </tr>
                </thead>
                <tbody id="tbodyItems">
                    <tr id="filaVacia">
                        <td colspan="8" class="vacio">A&uacute;n no agregas movimientos.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Aqui se inyectan inputs hidden items[0][...] --}}
        <div id="hiddenItems"></div>

        <div class="footer-acciones">
            <button class="btn" type="submit" id="btnGuardarTodos" disabled>Guardar todos</button>
            
        </div>
    </form>
</div>

<style>
/* ===== CONTENEDOR ===== */
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* acciones superiores (si no lo define el layout) */
.acciones-superior{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    margin-bottom:10px;
}

.alerta-error{
    margin:12px 0 18px;
    padding:12px 14px;
    border-radius:10px;
    border:1px solid #d93025;
    background:#fff3f2;
    color:#7f1d1d;
}

.alerta-error strong{
    display:block;
    font-weight:800;
    margin-bottom:4px;
}

.alerta-error-msg{
    font-size:14px;
}

.alerta-error-lista{
    margin:8px 0 0 18px;
    padding:0;
}
/* ===== GRID FORM ===== */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}
@media (max-width:720px){
    .grid{ grid-template-columns:1fr; }
}

.grid > div{
    display:flex;
    flex-direction:column;
    gap:6px;
}

label{
    font-weight:700;
    display:block;
    margin-bottom:6px;
}

input, select{
    width:100%;
    height:42px;                 /* OK altura consistente */
    padding:0 12px;              /* OK sin padding vertical */
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-size:14px;
    line-height:42px;
    box-sizing:border-box;
}

.hint{
    display:block;
    margin-top:6px;
    color:#6b6b6b;
    font-size:13px;
}

/* ===== FILTRO ADMIN (1 fila con scroll si no cabe) ===== */
.filtros-uno{
    display:flex;
    gap:10px;
    align-items:flex-end;
    flex-wrap:nowrap;
    overflow-x:auto;
    padding-bottom:6px;
    margin:10px 0 18px;
}
.campo-filtro{
    min-width:260px;
    flex:0 0 auto;
}

/* ===== BUSCADOR PRESENTACION ===== */
.presentacion-buscador{
    display:flex;
    flex-direction:column;
    gap:6px;
}

/* ===== ACCIONES ===== */
.acciones{
    margin-top:14px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn,
.btn-secundario{
    height:42px;
    padding:0 16px;
    border-radius:8px;
    font-weight:700;
    font-size:14px;
    line-height:42px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    text-decoration:none;
    border:none;
    box-sizing:border-box;
    white-space:nowrap;
}

.btn{ background:#b22b27; color:#fff; }
.btn:hover{ background:#941c1c; }

.btn-secundario{ background:#b22b27; color:#fff; }
.btn-secundario:hover{ background:#941c1c; }

.btn-menu,
.btn-regresar{
    height:42px;
    padding:0 16px;
    border-radius:8px;
    border:none;
    cursor:pointer;
    background:#777;
    color:#fff;
    font-weight:700;
    line-height:42px;
}
.btn-menu:hover,
.btn-regresar:hover{ filter:brightness(.95); }

/* separador */
.separador{
    margin:18px 0;
    border:none;
    height:1px;
    background:rgba(0,0,0,.12);
}

.subtitulo{ margin:0 0 10px; }

/* ===== TABLAS (scroll solo aqui) ===== */
.tabla-wrap{
    overflow-x:auto;
    border-radius:10px;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
}

/* tabla base (items) */
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;

    /* OK menos agresivo que 980px */
    min-width:820px;
}

.tabla thead th{
    background:#b22b27;
    color:#fff;
    text-align:left;
    padding:10px;
    font-weight:700;
    position:sticky;
    top:0;
    white-space:nowrap;
}

.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    vertical-align:top;
    white-space:nowrap;
}

/* OK Motivo (col 7) con ellipsis */
#tablaItems td:nth-child(7){
    max-width:320px;
    overflow:hidden;
    text-overflow:ellipsis;
}

/* vacios */
.vacio{
    text-align:center;
    padding:16px;
    color:#666;
    background:#fff7f0;
}

/* ===== RESULTADOS BUSCADOR ===== */
.tabla-resultados{
    margin-top:8px;
    max-height:180px;
    overflow:auto;
    border-radius:10px;
}

/* resultados NO necesitan min-width */
#tablaResultados{
    min-width:0;
}

/* botones de accion en tablas */
.btn-quitar{
    background:#777;
    border:none;
    color:#fff;
    padding:7px 10px;
    border-radius:8px;
    cursor:pointer;
    font-weight:700;
    white-space:nowrap;
}
.btn-quitar:hover{ filter:brightness(0.9); }

/* footer acciones */
.footer-acciones{
    margin-top:15px;
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
}

/* ===========================
   RESPONSIVE
   =========================== */

/* Tablet */
@media (max-width:1024px){
    .contenedor{
        padding:20px 22px;
        max-width:100%;
    }
    .tabla{ min-width:780px; }
}

/* Celular */
@media (max-width:600px){
    .contenedor{
        padding:16px 14px;
    }

    /* botones superiores full width */
    .acciones-superior .btn-menu,
    .acciones-superior .btn-regresar{
        width:100%;
    }

    /* acciones (agregar/limpiar) full width */
    .acciones{
        width:100%;
    }
    .acciones .btn-secundario{
        width:100%;
    }

    /* guardar todos full width */
    .footer-acciones .btn{
        width:100%;
    }

    /* tabla items scroll mas manejable */
    .tabla{ min-width:720px; }

    /* motivo mas corto en movil */
    #tablaItems td:nth-child(7){ max-width:200px; }
}

/* muy pequeno */
@media (max-width:380px){
    .tabla{ min-width:680px; }
}
</style>


<script>
(function () {
    const items = [];
    const presentacionesData = @json($presentaciones);

    const almacenSelect   = document.getElementById('almacen_id');
    const presentacionInput = document.getElementById('presentacionSearch');
    const presentacionHidden = document.getElementById('presentacion_id');
    const presentacionSeleccionada = document.getElementById('presentacionSeleccionada');
    const tbodyResultados = document.getElementById('tbodyResultados');
    const tipoSelect      = document.getElementById('tipo_movimiento');
    const destinoInput    = document.getElementById('destino_almacen_id');
    const destinoWrap     = document.getElementById('destinoWrap');
    const cantidadInput   = document.getElementById('cantidad');
    const loteInput       = document.getElementById('lote');
    const caducidadInput  = document.getElementById('caducidad');
    const motivoInput     = document.getElementById('motivo');

    const btnAgregar      = document.getElementById('btnAgregar');
    const btnLimpiar      = document.getElementById('btnLimpiar');
    const btnGuardarTodos = document.getElementById('btnGuardarTodos');

    const tbodyItems      = document.getElementById('tbodyItems');
    const filaVacia       = document.getElementById('filaVacia');
    const hiddenItemsDiv  = document.getElementById('hiddenItems');

    function limpiarCaptura() {
        presentacionInput.value = '';
        presentacionHidden.value = '';
        presentacionSeleccionada.textContent = '';
        renderResultados();
        tipoSelect.value = 'entrada';
        if (destinoInput) destinoInput.value = '';
        if (destinoWrap) destinoWrap.style.display = 'none';
        cantidadInput.value = '';
        loteInput.value = '';
        caducidadInput.value = '';
        motivoInput.value = '';
        presentacionInput.focus();
    }

    function validarCaptura() {
        if (!almacenSelect.value) {
            alert('Selecciona un almac\u00E9n primero.');
            almacenSelect.focus();
            return false;
        }
        if (!presentacionHidden.value) {
            alert('Selecciona una presentaci\u00F3n.');
            presentacionInput.focus();
            return false;
        }
        const cantidadRaw = cantidadInput.value || "";
        if (cantidadRaw.length > 5) {
            alert('La cantidad debe tener m\u00E1ximo 5 caracteres.');
            cantidadInput.focus();
            return false;
        }
        const cantidad = parseFloat(cantidadRaw);
        if (!cantidadRaw || isNaN(cantidad) || cantidad <= 0) {
            alert('Captura una cantidad v\u00E1lida (mayor a 0).');
            cantidadInput.focus();
            return false;
        }
        if (cantidad > 99999) {
            alert('La cantidad maxima permitida es 99999.');
            cantidadInput.focus();
            return false;
        }
        if (tipoSelect.value === 'transferencia') {
            if (!destinoInput || !destinoInput.value) {
                alert('Selecciona el almac\u00E9n destino para la transferencia.');
                destinoInput?.focus();
                return false;
            }
            if (String(destinoInput.value) === String(almacenSelect.value)) {
                alert('El almac\u00E9n destino debe ser distinto al almac\u00E9n origen.');
                destinoInput.focus();
                return false;
            }
        }
        return true;
    }
    function buildPresentacionTexto(p){
        const desc = p?.descripcion ? (p.contenido ? `${p.descripcion} - ${p.contenido}` : p.descripcion) : '-';
        const unidad = p?.unidad_contenido ?? '-';
        const prod = p?.producto?.nombre ?? '-';
        return `${prod} - ${desc} (${unidad})`;
    }

    function renderResultados(){
        const q = (presentacionInput.value || '').trim().toLowerCase();
        tbodyResultados.innerHTML = '';

        if (!q){
            tbodyResultados.innerHTML = '<tr><td colspan="2" class="vacio">Escribe para buscar.</td></tr>';
            return;
        }

        const matches = (presentacionesData || []).map(p => ({
            id: p.id,
            texto: buildPresentacionTexto(p)
        })).filter(p => p.texto.toLowerCase().includes(q)).slice(0, 3);

        if (!matches.length){
            tbodyResultados.innerHTML = '<tr><td colspan="2" class="vacio">Sin resultados.</td></tr>';
            return;
        }

        matches.forEach(m => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(m.texto)}</td>
                <td><button type="button" class="btn-quitar" data-id="${m.id}" data-texto="${escapeAttr(m.texto)}">Elegir</button></td>
            `;
            tbodyResultados.appendChild(tr);
        });

        tbodyResultados.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                presentacionHidden.value = btn.getAttribute('data-id');
                const texto = btn.getAttribute('data-texto');
                presentacionInput.value = texto;
                presentacionSeleccionada.textContent = texto;
                tbodyResultados.innerHTML = '<tr><td colspan="2" class="vacio">Seleccionado.</td></tr>';
            });
        });
    }

    function renderTabla() {
        tbodyItems.innerHTML = '';

        if (items.length === 0) {
            tbodyItems.appendChild(filaVacia);
            btnGuardarTodos.disabled = true;
            return;
        }

        btnGuardarTodos.disabled = false;

        items.forEach((it, idx) => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${escapeHtml(it.presentacion_texto)}</td>
                <td>${escapeHtml(it.tipo_movimiento)}</td>
                <td>${escapeHtml(it.cantidad)}</td>
                <td>${escapeHtml(it.lote || '-')}</td>
                <td>${escapeHtml(it.caducidad || '-')}</td>
                <td>${escapeHtml(it.destino_almacen_nombre || '-')}</td>
                <td>${escapeHtml(it.motivo || '-')}</td>
                <td><button type="button" class="btn-quitar" data-idx="${idx}">Quitar</button></td>
            `;
            tbodyItems.appendChild(tr);
        });

        tbodyItems.querySelectorAll('.btn-quitar').forEach(btn => {
            btn.addEventListener('click', () => {
                const i = parseInt(btn.getAttribute('data-idx'));
                items.splice(i, 1);
                renderTabla();
                renderHiddenInputs();
            });
        });
    }

    function renderHiddenInputs() {
        hiddenItemsDiv.innerHTML = '';
        items.forEach((it, idx) => {
            hiddenItemsDiv.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="items[${idx}][presentacion_id]" value="${escapeAttr(it.presentacion_id)}">
                <input type="hidden" name="items[${idx}][tipo_movimiento]" value="${escapeAttr(it.tipo_movimiento)}">
                <input type="hidden" name="items[${idx}][cantidad]" value="${escapeAttr(it.cantidad)}">
                <input type="hidden" name="items[${idx}][lote]" value="${escapeAttr(it.lote || '')}">
                <input type="hidden" name="items[${idx}][caducidad]" value="${escapeAttr(it.caducidad || '')}">
                <input type="hidden" name="items[${idx}][motivo]" value="${escapeAttr(it.motivo || '')}">
                <input type="hidden" name="items[${idx}][destino_almacen_id]" value="${escapeAttr(it.destino_almacen_id || '')}">
            `);
        });
    }

    btnAgregar.addEventListener('click', () => {
        if (!validarCaptura()) return;

        const presentacionId = presentacionHidden.value;
        const presentacionTexto = presentacionSeleccionada.textContent || presentacionInput.value;
        const destinoTexto = destinoInput?.selectedOptions?.[0]?.text || '';

        items.push({
            presentacion_id: presentacionId,
            presentacion_texto: presentacionTexto,
            tipo_movimiento: tipoSelect.value,
            cantidad: cantidadInput.value,
            lote: loteInput.value.trim(),
            caducidad: caducidadInput.value,
            motivo: motivoInput.value.trim(),
            destino_almacen_id: tipoSelect.value === 'transferencia' ? destinoInput?.value : '',
            destino_almacen_nombre: tipoSelect.value === 'transferencia' ? destinoTexto : '',
        });

        renderTabla();
        renderHiddenInputs();
        limpiarCaptura();
    });

    btnLimpiar.addEventListener('click', () => limpiarCaptura());

    cantidadInput.addEventListener('input', () => {
        if (cantidadInput.value.length > 5) {
            cantidadInput.value = cantidadInput.value.slice(0, 5);
        }
    });

    tipoSelect.addEventListener('change', () => {
        const esTransferencia = tipoSelect.value === 'transferencia';
        if (destinoWrap) destinoWrap.style.display = esTransferencia ? 'flex' : 'none';
        if (!esTransferencia && destinoInput) {
            destinoInput.value = '';
        }
    });

    presentacionInput.addEventListener('input', () => {
        presentacionHidden.value = '';
        presentacionSeleccionada.textContent = '';
        renderResultados();
    });

    document.getElementById('formMovimientos').addEventListener('submit', (e) => {
        if (items.length === 0) {
            e.preventDefault();
            alert('Agrega al menos un movimiento a la lista antes de guardar.');
        }
        if (!almacenSelect.value) {
            e.preventDefault();
            alert('Selecciona un almac\u00E9n.');
            almacenSelect.focus();
        }
    });

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[s]));
    }
    function escapeAttr(str) {
        return String(str ?? '').replace(/"/g, '&quot;');
    }

    renderResultados();
    renderTabla();
})();
</script>
@endsection






