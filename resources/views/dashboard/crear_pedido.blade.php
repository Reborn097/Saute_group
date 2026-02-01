@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos'], true);

    $qActual = trim((string) request('q', ''));
    $mostrarResultadosNoAdmin = ($qActual !== '' && mb_strlen($qActual) >= 2);

    // ✅ fallback: si el controlador manda $presentaciones (nuevo) o $productos (viejo)
    $lista = $presentaciones ?? $productos ?? collect();
    $items = method_exists($lista, 'items') ? $lista->items() : (is_iterable($lista) ? $lista : []);
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button type="button" class="btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
    </div>

    {{-- ==========================
            FILTROS
    ========================== --}}
    <form method="GET" action="{{ route('dashboard.pedidos.solicitar') }}" class="filtros" id="formFiltros">

        @if($esAdminPedidos)
            <div class="campo campo-span3">
                <label>Unidad operativa:</label>
                <select id="unidadOperativaSelect">
                    <option value="">— Selecciona —</option>
                    @foreach(($unidadesOperativas ?? []) as $uo)
                        <option value="{{ $uo->id }}">{{ $uo->nombre }}</option>
                    @endforeach
                </select>
                <small class="hint">
                    * Esta unidad se usará para registrar el pedido.
                </small>
            </div>
        @endif

        <div class="campo">
            <label>Fecha de solicitud:</label>
            <input type="date" id="fechaSolicitud" readonly>
        </div>

        <div class="campo">
            <label>Fecha de entrega:</label>
            <input type="date" id="fechaEntrega">
        </div>

        {{-- Solo admin: filtros extra --}}
        @if($esAdminPedidos)
            <div class="campo">
                <label>Proveedor:</label>
                <select name="proveedor_id" id="proveedorFiltro">
                    <option value="">Todos</option>
                    @foreach(($proveedores ?? []) as $prov)
                        <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                            {{ $prov->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label>Categoría:</label>
                <select name="categoria_id" id="categoriaFiltro">
                    <option value="">Todas</option>
                    @foreach(($categorias ?? []) as $cat)
                        <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="campo {{ $esAdminPedidos ? 'campo-span2' : 'campo-span3' }}">
            <label>Buscar:</label>
            <input type="text"
                   name="q"
                   id="qInput"
                   value="{{ request('q') }}"
                   placeholder="{{ $esAdminPedidos ? 'Ej. Coca, leche, harina...' : 'Escribe al menos 2 letras...' }}">
            @if(!$esAdminPedidos)
                <small class="hint">
                    * No se muestra el catálogo completo. Solo verás resultados al buscar.
                </small>
            @endif
        </div>

        <div class="campo campo-actions">
            <button type="submit" class="btn btn-buscar">Buscar</button>

            <a href="{{ route('dashboard.pedidos.solicitar') }}"
               class="btn-cancelar btn-link">
                Limpiar
            </a>
        </div>
    </form>

    {{-- ==========================
            CATÁLOGO
    ========================== --}}
    <h3 class="titulo-seccion">Presentaciones disponibles</h3>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
            <tr>
                <th>Producto</th>
                <th>Marca</th>
                <th>Descripción - Contenido</th>
                <th>Unidad contenido</th>
                <th>Precio</th>
                <th>Seleccionar</th>
            </tr>
            </thead>

            <tbody>
            @if(!$esAdminPedidos && !$mostrarResultadosNoAdmin)
                <tr>
                    <td colspan="6" class="empty-row">
                        Escribe al menos <b>2 letras</b> para buscar.
                    </td>
                </tr>
            @else
                @forelse($items as $pres)
                    @php
                        $esPresentacion = isset($pres->producto_id) && isset($pres->descripcion);

                        $nombreProducto = $esPresentacion ? ($pres->producto->nombre ?? '—') : ($pres->nombre ?? '—');
                        $descPresenta   = $esPresentacion ? ($pres->descripcion ?? '—') : '—';
                        $unidad         = $esPresentacion ? ($pres->producto->unidad_medida ?? '—') : ($pres->unidad_medida ?? '—');
                        $marca          = $esPresentacion ? ($pres->producto->marca ?? '—') : ($pres->marca ?? '—');
                        $contenido      = $esPresentacion ? ($pres->contenido ?? null) : ($pres->contenido ?? null);
                        $unidadContenido= $esPresentacion ? ($pres->unidad_contenido ?? '—') : ($pres->unidad_contenido ?? '—');

                        $precioDefault = null;

                        if ($esPresentacion) {
                            if (isset($pres->pp_default_precio)) {
                                $precioDefault = $pres->pp_default_precio !== null ? (float)$pres->pp_default_precio : null;
                            } else {
                                $prim = ($pres->proveedores ?? collect())->first();
                                $precioDefault = isset($prim->precio_vigente) ? (float)$prim->precio_vigente : null;
                            }
                            if ($precioDefault !== null && $precioDefault <= 0) {
                                $prim = ($pres->proveedores ?? collect())->first();
                                $fallback = isset($prim->precio_vigente) ? (float)$prim->precio_vigente : null;
                                $precioDefault = ($fallback && $fallback > 0) ? $fallback : null;
                            }
                        } else {
                            $precioDefault = isset($pres->pp_default_precio) ? (float)$pres->pp_default_precio : null;
                            if ($precioDefault !== null && $precioDefault <= 0) {
                                $precioDefault = null;
                            }
                        }

                        $descContenido = $descPresenta;
                        if ($contenido !== null && $contenido !== '') {
                            $descContenido = trim($descPresenta) . ' - ' . $contenido;
                        }
                    @endphp

                    <tr>
                        <td>{{ $nombreProducto }}</td>
                        <td>{{ $marca }}</td>
                        <td>{{ $descContenido }}</td>
                        <td>{{ $unidadContenido }}</td>
                        <td>
                            @if($precioDefault !== null)
                                ${{ number_format($precioDefault, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn-seleccionar"
                                    onclick="abrirModalPresentacion({{ $pres->id }})">
                                Seleccionar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-row">
                            Sin resultados con los filtros.
                        </td>
                    </tr>
                @endforelse
            @endif
            </tbody>
        </table>

        {{-- ✅ paginación si aplica --}}
        @if(method_exists($lista, 'hasPages') && ($esAdminPedidos || $mostrarResultadosNoAdmin) && $lista->hasPages())
            <div class="paginacion" style="margin-top:12px;">
                {{ $lista->links('vendor.pagination.dashboard') }}
            </div>
        @endif
    </div>

    {{-- ==========================
            PEDIDO ACTUAL
    ========================== --}}
    <h3 class="titulo-seccion">Presentaciones en el pedido</h3>

    <div class="tabla-wrap">
        <table class="tabla" id="tablaPedido">
            <thead>
            <tr>
                <th>Producto</th>
                <th>Marca</th>
                <th>Descripción - Contenido</th>
                <th>Unidad contenido</th>
                <th>Cantidad</th>
                @if($esAdminPedidos)
                    <th>Proveedor</th>
                @endif
                <th>Precio unitario</th>
                <th>Subtotal</th>
                <th style="min-width:160px;">Acciones</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="acciones-final">
        <button type="button" class="btn-confirmar" id="btnHacerPedidoUI">Previsualizar pedido</button>
    </div>

</div>

{{-- ==========================
        MODAL CANTIDAD
========================== --}}
<div id="modalCantidad" class="modal">
    <div class="modal-contenido">
        <h3 id="modalTitulo"></h3>

        @if($esAdminPedidos)
            <div class="grupo">
                <label>Proveedor</label>
                <select id="proveedorSelect" onchange="actualizarPrecioProveedor()"></select>
            </div>
        @endif

        <div class="grupo">
            <label>Cantidad</label>
            <input type="number" id="cantidadInput" min="0" step="0.01" value="1">
        </div>

        <p class="precio-linea">
            <strong>Precio unitario:</strong>
            <span id="precioProveedor">$0.00</span>
        </p>

        <div class="modal-acciones">
            <button type="button" class="btn" id="btnAgregarModal" onclick="agregarItem()">Agregar</button>
            <button type="button" class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarItem()">Actualizar</button>
            <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<div id="modalAdvertencia" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">⚠️ No se puede continuar</h3>
        <p id="textoAdvertencia">Completa las fechas antes de agregar.</p>
        <div class="modal-acciones">
            <button type="button" class="btn" onclick="cerrarModalAdvertencia()">Aceptar</button>
        </div>
    </div>
</div>

<div id="modalEliminar" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">Eliminar</h3>
        <p>¿Seguro que deseas eliminarlo?</p>
        <div class="modal-acciones">
            <button type="button" class="btn" id="btnEliminarSi">Eliminar</button>
            <button type="button" class="btn-cancelar" id="btnEliminarNo">Cancelar</button>
        </div>
    </div>
</div>

<style>
/* ✅ Base */
*{ box-sizing:border-box; }
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
    font-family:'Poppins', sans-serif;
}
.titulo-seccion{
    margin-top:25px;
    margin-bottom:10px;
    font-size:20px;
    font-weight:800;
    color:#0e2238;
}

/* ✅ Filtros responsivos */
.filtros{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:18px;
    margin-bottom:22px;
}
.campo label{ display:block; font-weight:700; margin-bottom:6px; color:#333; }
input, select{
    width:100%;
    padding:9px 10px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
    font-family:'Poppins', sans-serif;
}
input:focus, select:focus{
    outline:none;
    border-color:#b22b27;
    box-shadow:0 0 0 3px rgba(178,43,39,.18);
}
.hint{ display:block; margin-top:6px; color:#555; font-size:13px; }

.campo-span3{ grid-column: span 3; }
.campo-span2{ grid-column: span 2; }
.campo-actions{
    display:flex;
    gap:10px;
    align-items:flex-end;
    justify-content:flex-start;
}

/* ✅ Botones */
.btn-menu,
.btn,
.btn-seleccionar,
.btn-confirmar{
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    font-family:'Poppins', sans-serif;
    white-space:nowrap;
}
.btn-menu{ background:#999; }
.btn-menu:hover{ background:#777; }
.btn:hover{ background:#941c1c; }

.btn-cancelar{
    background:#777;
    color:#fff;
    border:none;
    padding:9px 14px;
    border-radius:10px;
    cursor:pointer;
    font-weight:800;
    font-family:'Poppins', sans-serif;
    white-space:nowrap;
}
.btn-cancelar:hover{ opacity:.9; }

.btn-link{
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}

/* ✅ Tablas responsivas (sin deformar) */
.tabla-wrap{
    width:100%;
    overflow-x:auto;            /* ✅ scroll horizontal si se ocupa */
    border-radius:10px;
}
.tabla{
    width:100%;
    min-width:900px;            /* ✅ evita que se aplaste feo en móvil */
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
}
.tabla th{
    background:#b22b27;
    color:white;
    padding:12px;
    text-align:center;
    font-weight:900;
    white-space:nowrap;
}
.tabla td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}
.tabla tr:hover{ background:#f5d6d6; }
.empty-row{
    padding:14px;
    text-align:center;
    background:#fff;
}

/* ✅ Acciones final */
.acciones-final{
    margin-top:22px;
    padding-top:12px;
    display:flex;
    justify-content:flex-start;
}

/* ✅ Modales */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    align-items:center;
    justify-content:center;
    z-index:5000;
    padding:15px;
}
.modal-contenido{
    background:white;
    width:min(420px, 100%);
    padding:20px;
    border-radius:12px;
    text-align:center;
}
.modal-acciones{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:15px;
    flex-wrap:wrap;
}
.warning-title{ color:#b22b27; }

/* ✅ Breakpoints */
@media (max-width: 980px){
    .filtros{ grid-template-columns:1fr 1fr; }
    .campo-span3{ grid-column: span 2; }
    .campo-span2{ grid-column: span 2; }
}
@media (max-width: 680px){
    .contenedor{ padding:18px 16px; }
    .filtros{ grid-template-columns:1fr; }
    .campo-span3, .campo-span2{ grid-column: span 1; }
    .campo-actions{ flex-direction:column; align-items:stretch; }
    .btn-buscar, .btn-link{ width:100%; }
}
</style>

<script>
const ES_ADMIN = @json($esAdminPedidos);
const MOSTRAR_NO_ADMIN = @json($mostrarResultadosNoAdmin);

// ✅ Este array trae SOLO lo de la página actual (paginación)
const presentacionesData = @json($items);

const modalCantidad = document.getElementById('modalCantidad');
const modalAdvertencia = document.getElementById('modalAdvertencia');
const modalEliminar = document.getElementById('modalEliminar');

const modalTitulo = document.getElementById('modalTitulo');
const cantidadInput = document.getElementById('cantidadInput');
const precioProveedor = document.getElementById('precioProveedor');

const btnAgregarModal = document.getElementById('btnAgregarModal');
const btnActualizarModal = document.getElementById('btnActualizarModal');

const fechaSolicitud = document.getElementById('fechaSolicitud');
const fechaEntrega = document.getElementById('fechaEntrega');
const textoAdvertencia = document.getElementById('textoAdvertencia');

const formFiltros = document.getElementById('formFiltros');
const qInput = document.getElementById('qInput');
const proveedorFiltro = document.getElementById('proveedorFiltro');
const categoriaFiltro = document.getElementById('categoriaFiltro');

const unidadSelect = document.getElementById('unidadOperativaSelect');
const proveedorSelect = document.getElementById('proveedorSelect');

let pedido = JSON.parse(localStorage.getItem('pedidoActual') || '[]');
let actual = null;
let editIndex = null;
let deleteIndex = null;

function num(v, def = 0){
    if (v === null || v === undefined) return def;
    if (typeof v === 'string' && v.trim() === '') return def;
    const n = Number(v);
    return Number.isFinite(n) ? n : def;
}

function guardarFechasLS(){
    localStorage.setItem('fechaSolicitud', fechaSolicitud.value);
    localStorage.setItem('fechaEntrega', fechaEntrega.value);
}

function fechasValidas(){
    const fs = (fechaSolicitud.value || '').trim();
    const fe = (fechaEntrega.value || '').trim();
    if (!fs || !fe) return false;
    if (fe < fs) return false;
    return true;
}

function guardarUnidadLS(){
    if (!ES_ADMIN || !unidadSelect) return;
    const id = (unidadSelect.value || '').trim();
    if (!id) {
        localStorage.removeItem('unidad_operativa_id');
        localStorage.removeItem('unidad_operativa_nombre');
        return;
    }
    const nombre = unidadSelect.options[unidadSelect.selectedIndex]?.textContent?.trim() || '';
    localStorage.setItem('unidad_operativa_id', id);
    localStorage.setItem('unidad_operativa_nombre', nombre);
}

function restaurarUnidadLS(){
    if (!ES_ADMIN || !unidadSelect) return;
    const id = localStorage.getItem('unidad_operativa_id');
    if (id) unidadSelect.value = id;
}

function mostrarAdvertencia(msg){
    textoAdvertencia.textContent = msg;
    modalAdvertencia.style.display = "flex";
}
function cerrarModalAdvertencia(){ modalAdvertencia.style.display = "none"; }

document.addEventListener('DOMContentLoaded', () => {
    const hoy = new Date().toISOString().split("T")[0];
    fechaSolicitud.value = localStorage.getItem('fechaSolicitud') || hoy;
    fechaEntrega.value = localStorage.getItem('fechaEntrega') || fechaSolicitud.value;
    fechaEntrega.addEventListener('change', guardarFechasLS);

    restaurarUnidadLS();
    if (unidadSelect) {
        unidadSelect.addEventListener('change', guardarUnidadLS);
        guardarUnidadLS();
    }

    if (proveedorFiltro) proveedorFiltro.addEventListener('change', () => formFiltros.submit());
    if (categoriaFiltro) categoriaFiltro.addEventListener('change', () => formFiltros.submit());

    let t = null;
    if (qInput) {
        qInput.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => {
                const q = (qInput.value || '').trim();
                if (!ES_ADMIN) {
                    if (q.length < 2 && q.length > 0) return;
                    if (q.length === 0) { formFiltros.submit(); return; }
                }
                formFiltros.submit();
            }, 250);
        });
    }

    renderPedido();
});

function abrirModalPresentacion(presentacionId){
    if (!fechasValidas()){
        mostrarAdvertencia('Completa correctamente las fechas antes de agregar.');
        return false;
    }
    if (!ES_ADMIN && !MOSTRAR_NO_ADMIN) {
        mostrarAdvertencia('Primero busca (mínimo 2 letras) para poder seleccionar.');
        return false;
    }

    const pres = (presentacionesData || []).find(x => x.id == presentacionId);
    if (!pres) { alert('No encontrada en esta página'); return false; }

    const productoNombre = pres.producto?.nombre ?? pres.nombre ?? '';
    const presentacionDesc = pres.descripcion ?? '—';
    const unidad = pres.producto?.unidad_medida ?? pres.unidad_medida ?? '';
    const marca = pres.producto?.marca ?? pres.marca ?? '—';
    const contenido = pres.contenido ?? null;
    const unidadContenido = pres.unidad_contenido ?? '—';
    const descContenido = (contenido !== null && contenido !== '') ? `${presentacionDesc} - ${contenido}` : presentacionDesc;

    if (!ES_ADMIN) {
        let ppId = pres.pp_default_id ?? null;
        let precio = num(pres.pp_default_precio, 0);
        if ((!ppId || precio <= 0) && Array.isArray(pres.proveedores) && pres.proveedores.length) {
            const prim = pres.proveedores[0];
            ppId = prim.id ?? ppId;
            precio = num(prim.precio_vigente, precio);
        }

        if (!ppId) {
            alert('Esta presentación no tiene proveedor principal asignado.');
            return;
        }

        actual = {
            presentacion_id: pres.id,
            producto_proveedor_id: ppId,
            producto: productoNombre,
            marca: marca,
            descripcion_contenido: descContenido,
            unidad_contenido: unidadContenido,
            unidad: unidad,
            proveedor: 'Proveedor asignado',
            precio: precio
        };

        precioProveedor.textContent = `$${precio.toFixed(2)}`;
    } else {
        const provs = pres.proveedores || [];
        if (!provs.length) { alert('Sin proveedores para esta presentación'); return; }
        if (!proveedorSelect) { alert('No existe selector proveedor'); return; }

        actual = {};
        proveedorSelect.innerHTML = '';
        provs.forEach(pp => {
            const obj = {
                producto_proveedor_id: pp.id,
                proveedor_id: pp.proveedor_id,
                proveedor: pp.proveedor?.nombre ?? '—',
                precio: num(pp.precio_vigente, 0)
            };
            const opt = document.createElement('option');
            opt.value = JSON.stringify(obj);
            opt.textContent = `${obj.proveedor} — $${obj.precio.toFixed(2)}`;
            proveedorSelect.appendChild(opt);
        });

        actualizarPrecioProveedor();

        actual.presentacion_id = pres.id;
        actual.producto = productoNombre;
        actual.marca = marca;
        actual.descripcion_contenido = descContenido;
        actual.unidad_contenido = unidadContenido;
        actual.unidad = unidad;
    }

    modalTitulo.textContent = `Agregar: ${productoNombre} (${presentacionDesc})`;
    cantidadInput.value = 1;

    btnAgregarModal.style.display = "inline-block";
    btnActualizarModal.style.display = "none";

    modalCantidad.style.display = "flex";
    return true;
}

function actualizarPrecioProveedor(){
    if (!ES_ADMIN || !proveedorSelect) return;
    const data = JSON.parse(proveedorSelect.value);

    actual = actual || {};
    actual.producto_proveedor_id = data.producto_proveedor_id;
    actual.proveedor = data.proveedor;
    actual.precio = num(data.precio, 0);

    precioProveedor.textContent = `$${num(actual.precio, 0).toFixed(2)}`;
}

function cerrarModal(){
    modalCantidad.style.display = "none";
    editIndex = null;
}

function agregarItem(){
    if (!actual) return;

    const cant = Math.max(0, num(cantidadInput.value, 1));
    const precio = num(actual.precio, 0);

    const key = String(actual.producto_proveedor_id);

    const idx = pedido.findIndex(x => String(x.producto_proveedor_id) === key);
    const item = {
        ...actual,
        cantidad: cant,
        subtotal: cant * precio
    };

    if (idx >= 0) pedido[idx] = item;
    else pedido.push(item);

    localStorage.setItem('pedidoActual', JSON.stringify(pedido));
    guardarFechasLS();
    guardarUnidadLS();

    renderPedido();
    cerrarModal();
}

function editarItem(i){
    const p = pedido[i];
    if (!abrirModalPresentacion(p.presentacion_id)) return;
    editIndex = i;
    cantidadInput.value = num(p.cantidad, 1);

    btnAgregarModal.style.display = "none";
    btnActualizarModal.style.display = "inline-block";
    modalTitulo.textContent = `Editar: ${p.producto} (${p.descripcion_contenido || ''})`;

    if (ES_ADMIN && proveedorSelect && p.producto_proveedor_id) {
        const opts = Array.from(proveedorSelect.options);
        const match = opts.find(o => {
            try { return JSON.parse(o.value).producto_proveedor_id == p.producto_proveedor_id; }
            catch (e) { return false; }
        });
        if (match) {
            proveedorSelect.value = match.value;
            actualizarPrecioProveedor();
        }
    }
}

function actualizarItem(){
    if (editIndex === null) return;
    const cant = Math.max(0, num(cantidadInput.value, 0));

    let p = pedido[editIndex];
    p.cantidad = cant;

    if (ES_ADMIN && proveedorSelect) {
        const prov = JSON.parse(proveedorSelect.value);
        p.producto_proveedor_id = prov.producto_proveedor_id;
        p.proveedor = prov.proveedor;
        p.precio = num(prov.precio, 0);
    }

    p.subtotal = p.cantidad * num(p.precio, 0);
    pedido[editIndex] = p;

    localStorage.setItem('pedidoActual', JSON.stringify(pedido));
    renderPedido();
    cerrarModal();
}

function pedirEliminar(i){
    deleteIndex = i;
    modalEliminar.style.display = "flex";
}
document.getElementById('btnEliminarSi').onclick = () => {
    if (deleteIndex !== null) {
        pedido.splice(deleteIndex, 1);
        localStorage.setItem('pedidoActual', JSON.stringify(pedido));
        renderPedido();
    }
    modalEliminar.style.display = "none";
    deleteIndex = null;
};
document.getElementById('btnEliminarNo').onclick = () => {
    modalEliminar.style.display = "none";
    deleteIndex = null;
};

function renderPedido(){
    const tbody = document.querySelector('#tablaPedido tbody');
    tbody.innerHTML = '';

    pedido.forEach((p, i) => {
        const precio = num(p.precio, 0);
        const cantidad = num(p.cantidad, 0);
        const subtotal = cantidad * precio;

        tbody.innerHTML += `
            <tr>
                <td>${p.producto || ''}</td>
                <td>${p.marca || ''}</td>
                <td>${p.descripcion_contenido || ''}</td>
                <td>${p.unidad_contenido || ''}</td>
                <td>${cantidad}</td>
                ${ES_ADMIN ? `<td>${p.proveedor || ''}</td>` : ``}
                <td>$${precio.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td style="white-space:nowrap;">
                    <button type="button" class="btn" onclick="editarItem(${i})">Editar</button>
                    <button type="button" class="btn-cancelar" onclick="pedirEliminar(${i})">Eliminar</button>
                </td>
            </tr>
        `;
    });

    localStorage.setItem('pedidoActual', JSON.stringify(pedido));
}

document.getElementById('btnHacerPedidoUI').onclick = () => {
    if (!fechasValidas()){
        mostrarAdvertencia('Completa correctamente las fechas.');
        return;
    }
    if (!pedido.length){
        mostrarAdvertencia('Agrega al menos un item para continuar.');
        return;
    }
    if (ES_ADMIN) {
        const uo = localStorage.getItem('unidad_operativa_id');
        if (!uo) {
            mostrarAdvertencia('Selecciona una unidad operativa antes de continuar.');
            return;
        }
    }
    guardarFechasLS();
    guardarUnidadLS();
    window.location.href = "{{ route('dashboard.pedidos.previsualizar') }}";
};

function irMenuPrincipal(){
    localStorage.removeItem('pedidoActual');
    localStorage.removeItem('fechaSolicitud');
    localStorage.removeItem('fechaEntrega');
    localStorage.removeItem('unidad_operativa_id');
    localStorage.removeItem('unidad_operativa_nombre');
    window.location.href = "{{ route('dashboard.admin') }}";
}
</script>

@endsection
