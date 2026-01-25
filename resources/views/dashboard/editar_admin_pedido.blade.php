@extends('layouts.dashboard')

@section('titulo', 'Editar Pedido')

@section('contenido')
@php
    $role = auth()->user()->role ?? '';
    $esAdminPedidos = isset($esAdminPedidos)
        ? $esAdminPedidos
        : in_array($role, ['admin','encargado_pedidos','ceo'], true);

    $esOperativoPedidos = in_array($role, ['encargado_cocina','encargado_cafeteria'], true);

    // ✅ Regla que dijiste:
    // - Operativos: SOLO Pendiente
    // - Admin/encargado_pedidos/ceo: Pendiente o Visto
    $puedeEditarPDFs = ($esAdminPedidos && in_array($pedido->estado, ['Pendiente','Visto'], true))
        || ($esOperativoPedidos && $pedido->estado === 'Pendiente');
@endphp


<div class="contenedor">

    <button class="btn-menu"
        onclick="window.location.href='{{ $esAdminPedidos ? route('dashboard.pedidos.admin') : route('dashboard.pedidos.consultar') }}'">
        Regresar
    </button>

    <h2>Editar pedido #{{ $pedido->codigo }}</h2>

    <p><strong>Fecha solicitud:</strong> {{ $pedido->fecha_solicitud }}</p>
    <p><strong>Fecha entrega:</strong> {{ $pedido->fecha_entrega }}</p>

    {{-- ============================
            PDFs PEDIDO ESPECIAL
    ============================= --}}
    @if(isset($pedidoEspecial) && $pedidoEspecial)
        <h3 class="titulo-seccion">Documentos PDF del pedido especial</h3>

        {{-- ✅ FORM SOLO PARA PDFs (NO mezclar con items_json) --}}
        <form method="POST"
            action="{{ route('dashboard.pedidos.especiales.pdfs.actualizar', $pedido->codigo) }}"
            enctype="multipart/form-data">
            @csrf

            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Ver PDF</th>
                            <th>Reemplazar PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- SOLICITUD --}}
                        <tr>
                            <td><strong>Solicitud</strong></td>
                            <td>
                                @if(!empty($pedidoEspecial->solicitud))
                                    <a class="btn" href="{{ route('dashboard.pedidos.especiales.pdf.ver', [$pedido->codigo,'solicitud']) }}" target="_blank">Ver PDF</a>
                                @else
                                    <span style="opacity:.7;">No disponible</span>
                                @endif
                            </td>
                            <td>
                                @if($puedeEditarPDFs)
                                    <div class="file-row">
                                        <input type="file" id="pdf_solicitud_file" name="pdf_solicitud_file" accept="application/pdf" class="file-hidden">
                                        <button type="button" class="btn" onclick="triggerFile('pdf_solicitud_file')">Seleccionar archivo</button>
                                        <span class="file-name" id="name_pdf_solicitud_file">Ningún archivo seleccionado</span>
                                    </div>
                                    <small class="file-hint">Reemplaza el PDF actual.</small>
                                @else
                                    <span style="opacity:.7;">Bloqueado por estado</span>
                                @endif
                            </td>
                        </tr>

                        {{-- COTIZACION --}}
                        <tr>
                            <td><strong>Cotización</strong></td>
                            <td>
                                @if(!empty($pedidoEspecial->cotizacion))
                                    <a class="btn" href="{{ route('dashboard.pedidos.especiales.pdf.ver', [$pedido->codigo,'cotizacion']) }}" target="_blank">Ver PDF</a>
                                @else
                                    <span style="opacity:.7;">No disponible</span>
                                @endif
                            </td>
                            <td>
                                @if($puedeEditarPDFs)
                                    <div class="file-row">
                                        <input type="file" id="pdf_cotizacion_file" name="pdf_cotizacion_file" accept="application/pdf" class="file-hidden">
                                        <button type="button" class="btn" onclick="triggerFile('pdf_cotizacion_file')">Seleccionar archivo</button>
                                        <span class="file-name" id="name_pdf_cotizacion_file">Ningún archivo seleccionado</span>
                                    </div>
                                    <small class="file-hint">Reemplaza el PDF actual.</small>
                                @else
                                    <span style="opacity:.7;">Bloqueado por estado</span>
                                @endif
                            </td>
                        </tr>

                        {{-- AUTORIZACION --}}
                        <tr>
                            <td><strong>Autorización</strong></td>
                            <td>
                                @if(!empty($pedidoEspecial->autorizacion))
                                    <a class="btn" href="{{ route('dashboard.pedidos.especiales.pdf.ver', [$pedido->codigo,'autorizacion']) }}" target="_blank">Ver PDF</a>
                                @else
                                    <span style="opacity:.7;">No disponible</span>
                                @endif
                            </td>
                            <td>
                                @if($puedeEditarPDFs)
                                    <div class="file-row">
                                        <input type="file" id="pdf_autorizacion_file" name="pdf_autorizacion_file" accept="application/pdf" class="file-hidden">
                                        <button type="button" class="btn" onclick="triggerFile('pdf_autorizacion_file')">Seleccionar archivo</button>
                                        <span class="file-name" id="name_pdf_autorizacion_file">Ningún archivo seleccionado</span>
                                    </div>
                                    <small class="file-hint">Reemplaza el PDF actual.</small>
                                @else
                                    <span style="opacity:.7;">Bloqueado por estado</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- ✅ BOTÓN REAL PARA ENVIAR LOS ARCHIVOS --}}
            @if($puedeEditarPDFs)
                <div class="acciones-final">
                    <button type="submit" class="btn-confirmar">Guardar PDFs</button>
                </div>
            @endif
        </form>
    @endif



    <form id="formEditarPedido"
          method="POST"
          enctype="multipart/form-data"
          action="{{ route('dashboard.pedidos.admin.actualizar', $pedido->codigo) }}">
        @csrf
        <input type="hidden" name="items_json" id="items_json">

        {{-- ============================
                TABLA ACTIVOS
        ============================= --}}
        <h3 class="titulo-seccion">Productos activos en el pedido</h3>

        <div class="tabla-contenedor">
            <table class="tabla tabla-items" id="tablaActivos">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Unidad</th>

                        <th>Solicitada</th>
                        @if($esAdminPedidos)
                            <th>Aprobada</th>
                            <th>Diferencia</th>
                            <th>Proveedor</th>
                            <th>Precio unitario</th>
                            <th>Subtotal</th>
                            <th>Estado</th>
                        @else
                            {{-- No-admin: no existe aprobada/ proveedor / precio / subtotal por proveedor --}}
                            <th>Estado</th>
                        @endif

                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyActivos"></tbody>
            </table>
        </div>

        {{-- ============================
                TABLA INACTIVOS
        ============================= --}}
        <h3 class="titulo-seccion">Productos inactivos en el pedido</h3>

        <div class="tabla-contenedor">
            <table class="tabla tabla-items" id="tablaInactivos">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Unidad</th>

                        <th>Solicitada</th>

                        @if($esAdminPedidos)
                            <th>Aprobada</th>
                            <th>Diferencia</th>
                            <th>Proveedor</th>
                            <th>Precio unitario</th>
                            <th>Subtotal</th>
                        @endif

                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyInactivos"></tbody>
            </table>
        </div>

        {{-- BOTÓN GUARDAR --}}
        <div class="acciones-final">
            <button type="button" class="btn-confirmar" id="btnGuardarCambios">
                Guardar cambios
            </button>
        </div>
    </form>

    {{-- ============================
        PRODUCTOS DISPONIBLES (SOLO ADMIN)
    ============================= --}}
    @if($esAdminPedidos)
        <h3 class="titulo-seccion">Productos disponibles</h3>

        <form method="GET" action="{{ url()->current() }}" class="filtros-pedidos">
            <div class="filtro">
                <label>Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre o marca...">
            </div>

            <div class="filtro">
                <label>Categoría</label>
                <select name="categoria_id">
                    <option value="">Todas</option>
                    @foreach($categorias as $c)
                        <option value="{{ $c->id }}" {{ request('categoria_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filtro">
                <label>Proveedor</label>
                <select name="proveedor_id">
                    <option value="">Todos</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                            {{ $prov->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filtro acciones">
                <button class="btn" type="submit">Filtrar</button>
                <a class="btn-cancelar" href="{{ url()->current() }}">Limpiar</a>
            </div>
        </form>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Unidad</th>
                        <th>Precio (primer proveedor)</th>
                        <th>Seleccionar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $p)
                        @php $primero = $p->proveedores->first(); @endphp
                        <tr>
                            <td>{{ $p->nombre }}</td>
                            <td>{{ $p->marca ?? '—' }}</td>
                            <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                            <td>{{ $p->unidad_medida ?? 'N/A' }}</td>
                            <td>
                                @if($primero)
                                    ${{ number_format($primero->pivot->precio, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <button type="button"
                                        class="btn-seleccionar"
                                        onclick="abrirModalProducto({{ $p->id }})">
                                    Seleccionar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center" style="padding:14px;">
                                No hay productos con esos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($productos->hasPages())
            <div class="paginacion-wrap">
                {{ $productos->links('vendor.pagination.dashboard') }}
            </div>
        @endif
    @endif

</div>

{{-- ======================================================
                        MODAL AGREGAR / EDITAR
====================================================== --}}
<div id="modalCantidad" class="modal">
    <div class="modal-contenido">
        <h3 id="modalTitulo"></h3>

        {{-- ✅ SOLO ADMIN: selector proveedor --}}
        @if($esAdminPedidos)
            <div class="grupo">
                <label>Proveedor</label>
                <select id="proveedorSelect" onchange="actualizarPrecioProveedor()"></select>
            </div>
        @endif

        <div class="grupo">
            <label>Cantidad solicitada</label>
            <input type="number" id="cantidadSolicitadaInput" min="0" step="0.01" value="1">
            <small id="hintSolicitada" style="opacity:.7; display:none; margin-top:6px;">
                La solicitada es del pedido original (no se edita aquí).
            </small>
        </div>

        {{-- ✅ SOLO ADMIN: aprobada --}}
        @if($esAdminPedidos)
            <div class="grupo">
                <label>Cantidad aprobada</label>
                <input type="number" id="cantidadAprobadaInput" min="0" step="0.01" value="1">
                <small style="opacity:.7; display:block; margin-top:6px;">
                    Si la aprobada es menor → rechazo. Si es mayor → aumento.
                </small>
            </div>
        @endif

        <div class="grupo">
            <label>Estado del producto en el pedido</label>
            <select id="activoDetalleInput">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        {{-- ✅ SOLO ADMIN: precio --}}
        @if($esAdminPedidos)
            <p class="precio-linea">
                <strong>Precio unitario:</strong>
                <span id="precioProveedor">$0.00</span>
            </p>
        @endif

        <div class="modal-acciones">
            <button type="button" class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button type="button" class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarDetalle()">Actualizar</button>
            {{-- ✅ ESTE CANCELAR SOLO CIERRA ESTE MODAL --}}
            <button class="btn-cancelar" type="button" onclick="cerrarModalCantidad()">Cancelar</button>
        </div>
    </div>
</div>

{{-- Modal Eliminar --}}
<div id="modalEliminar" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">Eliminar producto</h3>
        <p>¿Seguro que deseas eliminarlo del pedido?</p>
        <div class="modal-acciones">
            <button type="button" class="btn" id="btnEliminarSi">Eliminar</button>
            {{-- ✅ ESTE CANCELAR SOLO CIERRA ELIMINAR --}}
            <button type="button" class="btn-cancelar" id="btnEliminarNo">Cancelar</button>
        </div>
    </div>
</div>

<style>
.file-hidden{
    position:absolute;
    left:-9999px;
    width:1px;
    height:1px;
    overflow:hidden;
}

.file-row{
    display:flex;
    align-items:center;
    gap:10px;
    justify-content:center;
    flex-wrap:wrap;
}

.file-name{
    background:#fff;
    border:1px solid #ddd;
    border-radius:10px;
    padding:8px 12px;
    min-width:240px;
    text-align:left;
    opacity:.9;
    font-size:.95em;
}

.file-hint{
    display:block;
    opacity:.7;
    margin-top:6px;
    text-align:center;
}

.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1300px;
    margin:auto;
}
.titulo-seccion{
    margin-top:25px;
    margin-bottom:10px;
    font-size:20px;
    font-weight:700;
}
.tabla-contenedor{ margin-top:10px; }
.tabla{
    width:100%;
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
}
.tabla td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
}
.tabla tr:hover{ background:#f5d6d6; }
.tabla-contenedor{
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 10px; /* conserva redondeado */
}

/* 2) La tabla no se fuerza a “romper” el layout */
.tabla{
    width: 100%;
    min-width: 980px;      /* ajusta si quieres, evita que se aplaste */
}

/* 3) Evita que la última columna (acciones) empuje todo */
/* ✅ SOLO tablas de ITEMS (productos), NO PDFs */
.tabla-items th:last-child,
.tabla-items td:last-child{
    width: 160px;
    min-width: 160px;
    white-space: nowrap;
}

.tabla-items td:last-child{
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
}

.tabla-items td:last-child .btn,
.tabla-items td:last-child .btn-cancelar{
    padding: 6px 10px;
    font-size: 13px;
    border-radius: 8px;
}

.btn-menu,
.btn,
.btn-seleccionar,
.btn-confirmar{
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 13px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu{ margin-bottom:15px; }
.btn:hover{ background:#941c1c; }

.btn-cancelar{
    background:#777;
    color:white;
    border:none;
    padding:8px 13px;
    border-radius:8px;
    cursor:pointer;
}
.acciones-final{
    margin-top:20px;
    text-align:right;
}

.badge-mini{
    padding:4px 8px;
    border-radius:8px;
    font-weight:700;
    font-size:.85em;
    display:inline-block;
}
.badge-ok{ background:#1f8f4a; color:#fff; }
.badge-neg{ background:#b22b27; color:#fff; }
.badge-zero{ background:#888; color:#fff; }

.filtros-pedidos{
    display:grid;
    grid-template-columns: 1.2fr 1fr 1fr auto;
    gap:12px;
    margin: 10px 0 12px 0;
    align-items:end;
}
.filtros-pedidos .filtro label{
    display:block;
    font-weight:700;
    margin-bottom:6px;
}
.filtros-pedidos input,
.filtros-pedidos select{
    width:100%;
    padding:10px;
    border-radius:10px;
    border:1px solid #ddd;
}
.filtros-pedidos .acciones{
    display:flex;
    gap:10px;
    justify-content:flex-end;
}

.paginacion-wrap{ margin-top:14px; }
.paginacion-wrap svg{ width:16px !important; height:16px !important; }

.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    align-items:center;
    justify-content:center;
    z-index:5000;
}
.modal-contenido{
    background:white;
    width:420px;
    padding:20px;
    border-radius:12px;
    text-align:left;
}
.modal-acciones{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:15px;
}
.grupo{ margin:12px 0; }
.grupo label{ display:block; font-weight:700; margin-bottom:6px; }
.grupo input, .grupo select{
    width:100%;
    padding:10px;
    border-radius:10px;
    border:1px solid #ddd;
}
.warning-title{ color:#b22b27; }
.text-center{ text-align:center; }

/* ===== PDF BOX (NUEVO, MISMA PALETA) ===== */
.pdf-box{
    background:#fff;
    border-radius:12px;
    padding:14px 16px;
    margin: 14px 0 8px 0;
    border:1px solid #f0d2d2;
}
.pdf-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:10px;
}
.pdf-title{
    margin:0;
    font-size:18px;
    font-weight:800;
    color:#b22b27;
}
.pdf-grid{
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap:10px;
}
.pdf-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:10px 12px;
    border-radius:10px;
    border:1px solid #eee;
    text-decoration:none;
    color:#111;
    background:#fff;
}
.pdf-item:hover{
    background:#f5d6d6;
}
.pdf-name{
    font-weight:800;
}
.pdf-action{
    color:#b22b27;
    font-weight:800;
}
@media (max-width: 900px){
    .pdf-grid{ grid-template-columns: 1fr; }
}
</style>

<script>
    const ES_ADMIN_PEDIDOS = @json($esAdminPedidos);

    // ✅ Solo admin tiene catálogo paginado
    const productosData = ES_ADMIN_PEDIDOS ? @json(isset($productos) ? $productos->items() : []) : [];

    let productosPedido = @json($itemsPedido);

    function num(v, def = 0) {
        if (v === null || v === undefined) return def;
        if (typeof v === 'string' && v.trim() === '') return def;
        const n = Number(v);
        return Number.isFinite(n) ? n : def;
    }

    productosPedido = (productosPedido || []).map(p => ({
        ...p,
        marca: p.marca ?? '',
        precio: num(p.precio, 0),
        cantidad_solicitada: num(p.cantidad_solicitada, 0),
        // no-admin: aprobada se alinea a solicitada (backend también lo fuerza)
        cantidad_aprobada: ES_ADMIN_PEDIDOS
            ? ((p.cantidad_aprobada === null || p.cantidad_aprobada === undefined)
                ? num(p.cantidad_solicitada, 0)
                : num(p.cantidad_aprobada, 0))
            : num(p.cantidad_solicitada, 0),
        activo: (p.activo === null || p.activo === undefined) ? 1 : num(p.activo, 1),
        subtotal: num(p.subtotal, 0),
        is_new: num(p.is_new, 0),
    }));

    let productoSeleccionado = null;
    let productoEditandoIndex = null;
    let indexEliminar = null;

    const modalCantidad  = document.getElementById('modalCantidad');
    const modalEliminar  = document.getElementById('modalEliminar');

    const cantidadSolicitadaInput = document.getElementById('cantidadSolicitadaInput');
    const activoDetalleInput      = document.getElementById('activoDetalleInput');
    const hintSolicitada          = document.getElementById('hintSolicitada');

    const modalTitulo       = document.getElementById('modalTitulo');
    const btnAgregarModal   = document.getElementById('btnAgregarModal');
    const btnActualizarModal= document.getElementById('btnActualizarModal');

    // ✅ Solo admin existen estos elementos en DOM
    const proveedorSelect = ES_ADMIN_PEDIDOS ? document.getElementById('proveedorSelect') : null;
    const cantidadAprobadaInput = ES_ADMIN_PEDIDOS ? document.getElementById('cantidadAprobadaInput') : null;
    const precioProveedor = ES_ADMIN_PEDIDOS ? document.getElementById('precioProveedor') : null;

    document.addEventListener('DOMContentLoaded', () => {
        // ✅ Cancelar eliminar
        const btnNo = document.getElementById('btnEliminarNo');
        if (btnNo) {
            btnNo.addEventListener('click', () => cerrarModalEliminar());
        }

        // ✅ Click afuera cierra SOLO el modal que está abierto
        modalCantidad.addEventListener('click', (e) => {
            if (e.target === modalCantidad) cerrarModalCantidad();
        });
        modalEliminar.addEventListener('click', (e) => {
            if (e.target === modalEliminar) cerrarModalEliminar();
        });

        actualizarTablas();
    });

    // ============== MODALES (ARREGLADOS) ==============
    function cerrarModalCantidad() {
        modalCantidad.style.display = 'none';
        productoEditandoIndex = null;

        cantidadSolicitadaInput.readOnly = false;
        hintSolicitada.style.display = "none";
        cantidadSolicitadaInput.value = 1;
        activoDetalleInput.value = "1";

        if (ES_ADMIN_PEDIDOS && cantidadAprobadaInput) {
            cantidadAprobadaInput.value = 1;
        }
    }

    function cerrarModalEliminar() {
        modalEliminar.style.display = 'none';
        indexEliminar = null;
    }

    function cerrarAmbosModales() {
        cerrarModalCantidad();
        cerrarModalEliminar();
    }

    // ============== MODAL (ADMIN: desde catálogo) ==============
    function abrirModalProducto(productoId) {
        if (!ES_ADMIN_PEDIDOS) return; // no-admin no agrega desde catálogo

        // 🔒 si estaba abierto eliminar, lo cerramos
        cerrarModalEliminar();

        const producto = productosData.find(p => p.id == productoId);

        if (!producto || !producto.proveedores || producto.proveedores.length === 0) {
            alert('Este producto no tiene proveedores asignados.');
            return;
        }

        proveedorSelect.innerHTML = '';
        producto.proveedores.forEach(prov => {
            const data = {
                producto_proveedor_id: prov.pivot.id,
                producto_id: producto.id,
                proveedor_id: prov.id,
                proveedor: prov.nombre,
                precio: parseFloat(prov.pivot.precio)
            };
            const opt = document.createElement('option');
            opt.value = JSON.stringify(data);
            opt.textContent = `${prov.nombre} — $${data.precio.toFixed(2)}`;
            proveedorSelect.appendChild(opt);
        });

        const data = JSON.parse(proveedorSelect.value);

        productoSeleccionado = {
            producto_proveedor_id: data.producto_proveedor_id,
            producto_id:           data.producto_id,
            proveedor_id:          data.proveedor_id,
            proveedor:             data.proveedor,
            precio:                data.precio,

            nombre:                producto.nombre,
            marca:                 producto.marca || '',
            categoria:             producto.categoria ? producto.categoria.nombre : '',
            unidad:                producto.unidad_medida || ''
        };

        cantidadSolicitadaInput.readOnly = false;
        hintSolicitada.style.display = "none";

        cantidadSolicitadaInput.value = 1;
        if (cantidadAprobadaInput) cantidadAprobadaInput.value = 1;
        activoDetalleInput.value = "1";

        if (precioProveedor) {
            precioProveedor.textContent = `$${num(productoSeleccionado.precio, 0).toFixed(2)}`;
        }

        btnAgregarModal.style.display = 'inline-block';
        btnActualizarModal.style.display = 'none';

        modalTitulo.textContent = `Agregar ${producto.nombre}`;
        modalCantidad.style.display = 'flex';
    }

    function actualizarPrecioProveedor() {
        if (!ES_ADMIN_PEDIDOS) return;

        const data = JSON.parse(proveedorSelect.value);

        productoSeleccionado.producto_proveedor_id = data.producto_proveedor_id;
        productoSeleccionado.producto_id           = data.producto_id;
        productoSeleccionado.proveedor_id          = data.proveedor_id;
        productoSeleccionado.proveedor             = data.proveedor;
        productoSeleccionado.precio                = data.precio;

        if (precioProveedor) {
            precioProveedor.textContent = `$${num(data.precio, 0).toFixed(2)}`;
        }
    }

    // ============== AGREGAR / ACTUALIZAR ==============
    function agregarProducto() {
        if (!ES_ADMIN_PEDIDOS) return; // no-admin no agrega nuevos
        if (!productoSeleccionado) return;

        const cantSol = num(cantidadSolicitadaInput.value, 1);
        const cantApr = num(cantidadAprobadaInput.value, cantSol);
        const activo  = num(activoDetalleInput.value, 1);

        const sol = Math.max(0, cantSol);
        const apr = Math.max(0, cantApr);
        const precio = num(productoSeleccionado.precio, 0);

        const existente = productosPedido.find(p =>
            p.producto_proveedor_id == productoSeleccionado.producto_proveedor_id
        );

        if (existente) {
            existente.cantidad_solicitada = sol;
            existente.cantidad_aprobada   = apr;
            existente.activo              = activo;
            existente.precio              = precio;
            existente.subtotal            = (activo === 1 ? (apr * precio) : 0);
        } else {
            productosPedido.push({
                ...productoSeleccionado,
                cantidad_solicitada: sol,
                cantidad_aprobada:   apr,
                activo:              activo,
                precio:              precio,
                subtotal:            (activo === 1 ? (apr * precio) : 0),
                is_new:              1,
            });
        }

        actualizarTablas();
        cerrarModalCantidad();
    }

    // ============== EDITAR ==============
    function editarProducto(index) {
        // 🔒 si está abierto eliminar, lo cerramos
        cerrarModalEliminar();

        const p = productosPedido[index];
        productoEditandoIndex = index;

        // ✅ NO-ADMIN: editar directo desde el item (sin catálogo, sin proveedor, sin precio)
        if (!ES_ADMIN_PEDIDOS) {
            productoSeleccionado = { ...p };

            cantidadSolicitadaInput.readOnly = false;
            hintSolicitada.style.display = "none";

            cantidadSolicitadaInput.value = num(p.cantidad_solicitada, 0);
            activoDetalleInput.value      = String(num(p.activo, 1));

            btnAgregarModal.style.display    = 'none';
            btnActualizarModal.style.display = 'inline-block';
            modalTitulo.textContent          = `Editar ${p.nombre}`;

            modalCantidad.style.display = 'flex';
            return;
        }

        // ✅ ADMIN: tu flujo actual (con proveedor)
        abrirModalProducto(p.producto_id);

        [...proveedorSelect.options].forEach(opt => {
            const obj = JSON.parse(opt.value);
            if (obj.producto_proveedor_id == p.producto_proveedor_id) {
                proveedorSelect.value = opt.value;
            }
        });

        const data = JSON.parse(proveedorSelect.value);
        productoSeleccionado.producto_proveedor_id = data.producto_proveedor_id;
        productoSeleccionado.proveedor_id          = data.proveedor_id;
        productoSeleccionado.proveedor             = data.proveedor;
        productoSeleccionado.precio                = data.precio;

        if (precioProveedor) {
            precioProveedor.textContent = `$${num(data.precio,0).toFixed(2)}`;
        }

        // 🔒 tu regla original para admin: solicitada no se edita
        cantidadSolicitadaInput.value = num(p.cantidad_solicitada, 0);
        cantidadSolicitadaInput.readOnly = true;
        hintSolicitada.style.display = "block";

        if (cantidadAprobadaInput) {
            cantidadAprobadaInput.value = num(p.cantidad_aprobada, num(p.cantidad_solicitada, 0));
        }

        activoDetalleInput.value = String(num(p.activo, 1));

        btnAgregarModal.style.display    = 'none';
        btnActualizarModal.style.display = 'inline-block';
        modalTitulo.textContent          = `Editar ${p.nombre}`;
    }

    function actualizarDetalle() {
        if (productoEditandoIndex === null) return;

        const p = productosPedido[productoEditandoIndex];
        const activo = num(activoDetalleInput.value, 1);

        // ✅ NO-ADMIN: solo solicitada + activo, aprobada = solicitada
        if (!ES_ADMIN_PEDIDOS) {
            const sol = Math.max(0, num(cantidadSolicitadaInput.value, 0));
            p.cantidad_solicitada = sol;
            p.cantidad_aprobada   = sol;
            p.activo              = activo;

            const precio = num(p.precio, 0);
            p.subtotal = (activo === 1 ? (sol * precio) : 0);

            actualizarTablas();
            cerrarModalCantidad();
            return;
        }

        // ✅ ADMIN: puede cambiar proveedor + aprobada
        const apr = Math.max(0, num(cantidadAprobadaInput.value, 0));
        const data = JSON.parse(proveedorSelect.value);

        p.producto_proveedor_id = data.producto_proveedor_id;
        p.proveedor_id          = data.proveedor_id;
        p.proveedor             = data.proveedor;
        p.precio                = num(data.precio, 0);

        p.cantidad_aprobada     = apr;
        p.activo                = activo;

        p.subtotal              = (activo === 1 ? (apr * p.precio) : 0);

        actualizarTablas();
        cerrarModalCantidad();
    }

    // ============== TABLAS ==============
    function badgeDelta(delta){
        const d = num(delta, 0);
        if (d > 0) return `<span class="badge-mini badge-ok">+${d}</span>`;
        if (d < 0) return `<span class="badge-mini badge-neg">${d}</span>`;
        return `<span class="badge-mini badge-zero">0</span>`;
    }

    function cambiarActivo(index, value){
        productosPedido[index].activo = num(value, 1);
        actualizarTablas();
    }

    function reactivarProducto(index){
        productosPedido[index].activo = 1;

        if (num(productosPedido[index].cantidad_aprobada, 0) === 0) {
            productosPedido[index].cantidad_aprobada = num(productosPedido[index].cantidad_solicitada, 0);
        }

        actualizarTablas();
    }

    function actualizarTablas() {
        const tbodyActivos = document.getElementById('tbodyActivos');
        const tbodyInactivos = document.getElementById('tbodyInactivos');

        tbodyActivos.innerHTML = '';
        tbodyInactivos.innerHTML = '';

        productosPedido.forEach((p, i) => {
            const precio = num(p.precio, 0);
            const sol    = num(p.cantidad_solicitada, 0);
            const apr    = ES_ADMIN_PEDIDOS ? num(p.cantidad_aprobada, sol) : sol;
            const activo = num(p.activo, 1);

            const delta = (apr - sol);
            const subtotal = (activo === 1 ? (apr * precio) : 0);
            p.subtotal = subtotal;

            if (activo === 1) {
                if(ES_ADMIN_PEDIDOS){
                    tbodyActivos.innerHTML += `
                        <tr>
                            <td>${p.nombre || ''}</td>
                            <td>${p.marca || '—'}</td>
                            <td>${p.categoria || ''}</td>
                            <td>${p.unidad || ''}</td>

                            <td>${sol}</td>
                            <td>${apr}</td>
                            <td>${badgeDelta(delta)}</td>

                            <td>${p.proveedor || ''}</td>
                            <td>$${precio.toFixed(2)}</td>
                            <td>$${subtotal.toFixed(2)}</td>

                            <td>
                                <select onchange="cambiarActivo(${i}, this.value)">
                                    <option value="1" selected>Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </td>

                            <td>
                                <button type="button" class="btn" onclick="editarProducto(${i})">Editar</button>
                                <button type="button" class="btn-cancelar" onclick="abrirModalEliminar(${i})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }else{
                    tbodyActivos.innerHTML += `
                        <tr>
                            <td>${p.nombre || ''}</td>
                            <td>${p.marca || '—'}</td>
                            <td>${p.categoria || ''}</td>
                            <td>${p.unidad || ''}</td>

                            <td>${sol}</td>

                            <td>
                                <select onchange="cambiarActivo(${i}, this.value)">
                                    <option value="1" selected>Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </td>

                            <td>
                                <button type="button" class="btn" onclick="editarProducto(${i})">Editar</button>
                                <button type="button" class="btn-cancelar" onclick="abrirModalEliminar(${i})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }
            } else {
                if(ES_ADMIN_PEDIDOS){
                    tbodyInactivos.innerHTML += `
                        <tr style="opacity:.6;">
                            <td>${p.nombre || ''}</td>
                            <td>${p.marca || '—'}</td>
                            <td>${p.categoria || ''}</td>
                            <td>${p.unidad || ''}</td>

                            <td>${sol}</td>
                            <td>${apr}</td>
                            <td>${badgeDelta(delta)}</td>

                            <td>${p.proveedor || ''}</td>
                            <td>$${precio.toFixed(2)}</td>
                            <td>$${subtotal.toFixed(2)}</td>

                            <td>
                                <button type="button" class="btn" onclick="reactivarProducto(${i})">Reactivar</button>
                                <button type="button" class="btn-cancelar" onclick="abrirModalEliminar(${i})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }else{
                    tbodyInactivos.innerHTML += `
                        <tr style="opacity:.6;">
                            <td>${p.nombre || ''}</td>
                            <td>${p.marca || '—'}</td>
                            <td>${p.categoria || ''}</td>
                            <td>${p.unidad || ''}</td>

                            <td>${sol}</td>

                            <td>
                                <button type="button" class="btn" onclick="reactivarProducto(${i})">Reactivar</button>
                                <button type="button" class="btn-cancelar" onclick="abrirModalEliminar(${i})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }
            }
        });
    }

    // ============== ELIMINAR (ARREGLADO) ==============
    function abrirModalEliminar(i) {
        // 🔥 clave: cerrar el modal de cantidad si estaba abierto
        cerrarModalCantidad();

        indexEliminar = i;
        modalEliminar.style.display = 'flex';
    }

    document.getElementById('btnEliminarSi').onclick = function () {
        if (indexEliminar !== null) {
            productosPedido.splice(indexEliminar, 1);
            actualizarTablas();
        }
        cerrarModalEliminar();
    };

    // btnEliminarNo se asigna en DOMContentLoaded para que no se pierda

    // ============== GUARDAR ==============
    document.getElementById('btnGuardarCambios').onclick = function () {
        if (productosPedido.length === 0) {
            alert('El pedido debe tener al menos un producto.');
            return;
        }

        // ✅ NO-ADMIN: fuerza aprobada = solicitada antes de enviar (backend también lo hace)
        if(!ES_ADMIN_PEDIDOS){
            productosPedido = productosPedido.map(p => ({
                ...p,
                cantidad_aprobada: num(p.cantidad_solicitada, 0),
            }));
        }

        document.getElementById('items_json').value = JSON.stringify(productosPedido);
        document.getElementById('formEditarPedido').submit();
    };
</script>
<script>
function triggerFile(id){
    const input = document.getElementById(id);
    if(input) input.click();
}

document.addEventListener('DOMContentLoaded', () => {
    const ids = ['pdf_solicitud_file','pdf_cotizacion_file','pdf_autorizacion_file'];
    ids.forEach(id => {
        const input = document.getElementById(id);
        if(!input) return;

        input.addEventListener('change', () => {
            const nameEl = document.getElementById('name_' + id);
            if(!nameEl) return;

            const file = input.files && input.files[0] ? input.files[0].name : '';
            nameEl.textContent = file ? file : 'Ningún archivo seleccionado';
        });
    });
});
</script>

@endsection
