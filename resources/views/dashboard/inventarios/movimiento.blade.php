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

        <button class="btn-regresar"
            onclick="window.location.href='{{ url()->previous() }}'">
            Regresar
        </button>
    </div>

    <h2>Registrar movimientos</h2>

    {{-- ✅ SOLO ADMIN: filtro de Unidad Operativa (GET) --}}
    @if($esAdmin)
        <form method="GET" action="{{ route('inventarios.movimiento.form') }}" style="margin:10px 0 18px; display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
            <div style="min-width:260px;">
                <label>Unidad operativa:</label>
                <select name="unidad_operativa_id" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach(($unidadesOperativas ?? []) as $uo)
                        <option value="{{ $uo->id }}" {{ (string)($uoId ?? '') === (string)$uo->id ? 'selected' : '' }}>
                            {{ $uo->nombre }}
                        </option>
                    @endforeach
                </select>
                <small class="hint">Filtra los almacenes disponibles.</small>
            </div>
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

            <div>
                <label>Producto</label>
                <select id="producto_id">
                    <option value="">— Selecciona —</option>
                    @foreach($productos as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </select>
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

        <div class="tabla-wrap">
            <table class="tabla" id="tablaItems">
                <thead>
                    <tr>
                        <th style="width:28%;">Producto</th>
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
            <a class="btn-cancelar" href="{{ route('inventarios.index') }}">Cancelar</a>
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
label{ font-weight:700; display:block; margin-bottom:6px; }
input, select{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
}
.hint{ display:block; margin-top:6px; color:#6b6b6b; }

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

.btn-secundario{
    background:#2b2b2b;
    color:#fff;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-secundario:hover{ filter:brightness(0.9); }

.btn-cancelar{
    background:#777;
    color:white;
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
}
.btn-regresar{
    background:#777;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
}

.separador{
    margin:18px 0;
    border:none;
    height:1px;
    background:rgba(0,0,0,.12);
}

.subtitulo{ margin:0 0 10px; }

.tabla-wrap{ overflow:auto; border-radius:10px; }
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
}
.tabla thead th{
    background:#b22b27;
    color:#fff;
    text-align:left;
    padding:10px;
    font-weight:700;
    position:sticky;
    top:0;
}
.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    vertical-align:top;
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
}
</style>

<script>
(function () {
    const items = [];

    const almacenSelect   = document.getElementById('almacen_id');
    const productoSelect  = document.getElementById('producto_id');
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
        productoSelect.value = '';
        tipoSelect.value = 'entrada';
        cantidadInput.value = '';
        loteInput.value = '';
        caducidadInput.value = '';
        motivoInput.value = '';
        productoSelect.focus();
    }

    function validarCaptura() {
        if (!almacenSelect.value) {
            alert('Selecciona un almacén primero.');
            almacenSelect.focus();
            return false;
        }
        if (!productoSelect.value) {
            alert('Selecciona un producto.');
            productoSelect.focus();
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

    function renderTabla() {
        // limpia tbody
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
                <td>${escapeHtml(it.producto_texto)}</td>
                <td>${escapeHtml(it.tipo_movimiento)}</td>
                <td>${escapeHtml(it.cantidad)}</td>
                <td>${escapeHtml(it.lote || '—')}</td>
                <td>${escapeHtml(it.caducidad || '—')}</td>
                <td>${escapeHtml(it.motivo || '—')}</td>
                <td><button type="button" class="btn-quitar" data-idx="${idx}">Quitar</button></td>
            `;
            tbodyItems.appendChild(tr);
        });

        // engancha botones quitar
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
                <input type="hidden" name="items[${idx}][producto_id]" value="${escapeAttr(it.producto_id)}">
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

        const productoId = productoSelect.value;
        const productoTexto = productoSelect.options[productoSelect.selectedIndex].text;

        items.push({
            producto_id: productoId,
            producto_texto: productoTexto,
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

    // seguridad: no dejes enviar si no hay items
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
        // para atributos value=""
        return String(str ?? '').replace(/"/g, '&quot;');
    }

    // init
    renderTabla();
})();
</script>
@endsection
