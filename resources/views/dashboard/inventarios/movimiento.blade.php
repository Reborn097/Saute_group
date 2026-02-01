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
            Menú principal
        </button>

        {{-- ✅ Evita loop del previous --}}
        <button class="btn-regresar"
            onclick="window.location.href='{{ route('inventarios.index') }}'">
            Regresar
        </button>
    </div>

    <h2>Registrar movimientos</h2>

    {{-- ✅ SOLO ADMIN: filtro de Unidad Operativa (GET) en UNA FILA con scroll --}}
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
                <label>Almacén</label>
                <select id="almacen_id" name="almacen_id" required>
                    <option value="">— Selecciona —</option>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                    @endforeach
                </select>
                <small class="hint">El almacén aplica a todos los renglones.</small>
            </div>

            <div class="presentacion-buscador" style="grid-column:1/-1;">
                <label>Presentación</label>
                <input id="presentacionSearch" type="text" placeholder="Buscar presentación...">
                <input type="hidden" id="presentacion_id">
                <small class="hint" id="presentacionSeleccionada"></small>
                <div class="tabla-wrap tabla-resultados">
                    <table class="tabla" id="tablaResultados">
                        <thead>
                            <tr>
                                <th>Coincidencias</th>
                                <th style="width:90px;">Acción</th>
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
                </select>
            </div>

            <div>
                <label>Cantidad</label>
                <input id="cantidad" type="number" min="0.01" step="0.01" placeholder="Ej. 2.5">
            </div>

            <div>
                <label>Lote (opcional)</label>
                <input id="lote" type="text" maxlength="120" placeholder="Ej. L-2026-01">
            </div>

            <div>
                <label>Caducidad (opcional)</label>
                <input id="caducidad" type="date">
            </div>

            <div style="grid-column:1/-1;">
                <label>Motivo (opcional)</label>
                <input id="motivo" type="text" maxlength="255" placeholder="Ej. Compra / merma / ajuste por conteo">
            </div>

        </div>

        <div class="acciones">
            <button class="btn-secundario" type="button" id="btnAgregar">Agregar a lista</button>
            <button class="btn-secundario" type="button" id="btnLimpiar">Limpiar captura</button>
        </div>

        <hr class="separador">

        <h3 class="subtitulo">Lista de movimientos a guardar</h3>

        {{-- ✅ tabla con scroll horizontal si no caben columnas --}}
        <div class="tabla-wrap">
            <table class="tabla" id="tablaItems">
                <thead>
                    <tr>
                        <th style="width:28%;">Presentación</th>
                        <th style="width:12%;">Tipo</th>
                        <th style="width:10%;">Cantidad</th>
                        <th style="width:15%;">Lote</th>
                        <th style="width:15%;">Caducidad</th>
                        <th>Motivo</th>
                        <th style="width:8%;">Acción</th>
                    </tr>
                </thead>
                <tbody id="tbodyItems">
                    <tr id="filaVacia">
                        <td colspan="7" class="vacio">Aún no agregas movimientos.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Aquí se inyectan inputs hidden items[0][...] --}}
        <div id="hiddenItems"></div>

        <div class="footer-acciones">
            <button class="btn" type="submit" id="btnGuardarTodos" disabled>Guardar todos</button>
            
        </div>
    </form>
</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}
@media(max-width:720px){
    .grid{ grid-template-columns:1fr; }
}

label{ font-weight:700; display:block; margin-bottom:6px; }
input, select{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
}
.grid > div{
    display:flex;
    flex-direction:column;
    gap:6px;
}.hint{ display:block; margin-top:6px; color:#6b6b6b; font-size:13px; }

/* ✅ filtros admin en una fila con scroll horizontal si no cabe */
.filtros-uno{
    display:flex;
    gap:10px;
    align-items:flex-end;
    flex-wrap:nowrap;        /* 🔥 una sola fila */
    overflow-x:auto;         /* 🔥 scroll si no cabe */
    padding-bottom:6px;
    margin:10px 0 18px;
}
.campo-filtro{
    min-width:260px;
    flex:0 0 auto;
}

/* acciones */
.acciones{
    margin-top:14px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
}
.btn:hover{ background:#941c1c; }

/* ✅ secundarios en rojo (como pediste) */
.btn-secundario{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-secundario:hover{ background:#941c1c; }

.btn-cancelar{
    background:#777;
    color:white;
    padding:9px 14px;
    border-radius:8px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
}
.btn-cancelar:hover{ filter:brightness(.95); }

.btn-menu,
.btn-regresar{
    background:#777;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu:hover,
.btn-regresar:hover{ filter:brightness(.95); }

.separador{
    margin:18px 0;
    border:none;
    height:1px;
    background:rgba(0,0,0,.12);
}

.subtitulo{ margin:0 0 10px; }

/* ✅ tabla con scroll horizontal cuando sea necesario */
.tabla-wrap{
    overflow-x:auto;      /* 🔥 scroll horizontal */
    border-radius:10px;
}
.tabla-resultados{
    margin-top:6px;
    border:1px solid rgba(0,0,0,.12);
    background:#fff;
}
.tabla-resultados .tabla{
    min-width:0;
}
.tabla-resultados .tabla thead th{
    padding:8px;
}
.tabla-resultados .tabla td{
    padding:8px;
}
.tabla-resultados .vacio{
    padding:10px;
}
.tabla-resultados button{
    padding:6px 10px;
}

.presentacion-buscador{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.acciones{
    margin-top:10px;
}
.acciones .btn-secundario{
    min-width:140px;
}
.footer-acciones{
    margin-top:12px;
}.tabla-resultados{
    margin-top:8px;
    max-height:180px;
    overflow:auto;
}
.tabla-resultados .tabla{
    min-width:0;
}.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
    min-width:980px;      /* 🔥 fuerza scroll en pantallas chicas */
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
.vacio{
    text-align:center;
    padding:18px;
    color:#666;
    background:#fff7f0;
}

.btn-quitar{
    background:#777;
    border:none;
    color:#fff;
    padding:7px 10px;
    border-radius:8px;
    cursor:pointer;
}
.btn-quitar:hover{ filter:brightness(0.9); }

.footer-acciones{
    margin-top:15px;
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
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
        cantidadInput.value = '';
        loteInput.value = '';
        caducidadInput.value = '';
        motivoInput.value = '';
        presentacionInput.focus();
    }

    function validarCaptura() {
        if (!almacenSelect.value) {
            alert('Selecciona un almacén primero.');
            almacenSelect.focus();
            return false;
        }
        if (!presentacionHidden.value) {
            alert('Selecciona una presentación.');
            presentacionInput.focus();
            return false;
        }
        const cantidad = parseFloat(cantidadInput.value);
        if (!cantidadInput.value || isNaN(cantidad) || cantidad <= 0) {
            alert('Captura una cantidad válida (mayor a 0).');
            cantidadInput.focus();
            return false;
        }
        return true;
    }
    function buildPresentacionTexto(p){
        const desc = p?.descripcion ? (p.contenido ? `${p.descripcion} - ${p.contenido}` : p.descripcion) : '—';
        const unidad = p?.unidad_contenido ?? '—';
        const prod = p?.producto?.nombre ?? '—';
        return `${prod} — ${desc} (${unidad})`;
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
                <td>${escapeHtml(it.lote || '—')}</td>
                <td>${escapeHtml(it.caducidad || '—')}</td>
                <td>${escapeHtml(it.motivo || '—')}</td>
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
            `);
        });
    }

    btnAgregar.addEventListener('click', () => {
        if (!validarCaptura()) return;

        const presentacionId = presentacionHidden.value;
        const presentacionTexto = presentacionSeleccionada.textContent || presentacionInput.value;

        items.push({
            presentacion_id: presentacionId,
            presentacion_texto: presentacionTexto,
            tipo_movimiento: tipoSelect.value,
            cantidad: cantidadInput.value,
            lote: loteInput.value.trim(),
            caducidad: caducidadInput.value,
            motivo: motivoInput.value.trim(),
        });

        renderTabla();
        renderHiddenInputs();
        limpiarCaptura();
    });

    btnLimpiar.addEventListener('click', () => limpiarCaptura());

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
            alert('Selecciona un almacén.');
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














