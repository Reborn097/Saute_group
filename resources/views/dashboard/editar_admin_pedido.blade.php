@extends('layouts.dashboard')

@section('titulo', 'Editar Pedido')

@section('contenido')
<div class="contenedor">

    <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.pedidos.admin') }}'">
        Regresar
    </button>

    <h2>Editar pedido #{{ $pedido->codigo }}</h2>

    <p><strong>Fecha solicitud:</strong> {{ $pedido->fecha_solicitud }}</p>
    <p><strong>Fecha entrega:</strong> {{ $pedido->fecha_entrega }}</p>

    <form id="formEditarPedido"
          method="POST"
          action="{{ route('dashboard.pedidos.admin.actualizar', $pedido->codigo) }}">
        @csrf
        <input type="hidden" name="items_json" id="items_json">

        {{-- ============================
                TABLA ACTIVOS
        ============================= --}}
        <h3 class="titulo-seccion">Productos activos en el pedido</h3>

        <div class="tabla-contenedor">
            <table class="tabla" id="tablaActivos">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Unidad</th>

                        <th>Solicitada</th>
                        <th>Aprobada</th>
                        <th>Diferencia</th>

                        <th>Proveedor</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>

                        <th>Estado</th>
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
            <table class="tabla" id="tablaInactivos">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Unidad</th>

                        <th>Solicitada</th>
                        <th>Aprobada</th>
                        <th>Diferencia</th>

                        <th>Proveedor</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>

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
            TABLA DE PRODUCTOS
    ============================= --}}
    <h3 class="titulo-seccion">Productos disponibles</h3>

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
                @foreach($productos as $p)
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
                @endforeach
            </tbody>
        </table>
    </div>

</div>

{{-- ======================================================
                        MODAL AGREGAR / EDITAR
====================================================== --}}
<div id="modalCantidad" class="modal">
    <div class="modal-contenido">
        <h3 id="modalTitulo"></h3>

        <div class="grupo">
            <label>Proveedor</label>
            <select id="proveedorSelect" onchange="actualizarPrecioProveedor()"></select>
        </div>

        <div class="grupo">
            <label>Cantidad solicitada</label>
            {{-- 🔒 en edición se bloqueará para NO pisar la solicitada --}}
            <input type="number" id="cantidadSolicitadaInput" min="0" step="0.01" value="1">
            <small id="hintSolicitada" style="opacity:.7; display:none; margin-top:6px;">
                La solicitada es del pedido original (no se edita aquí).
            </small>
        </div>

        <div class="grupo">
            <label>Cantidad aprobada</label>
            <input type="number" id="cantidadAprobadaInput" min="0" step="0.01" value="1">
            <small style="opacity:.7; display:block; margin-top:6px;">
                Si la aprobada es menor → rechazo. Si es mayor → aumento.
            </small>
        </div>

        <div class="grupo">
            <label>Estado del producto en el pedido</label>
            {{-- ✅ BD: activo=1 activo, activo=0 inactivo --}}
            <select id="activoDetalleInput">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        <p class="precio-linea">
            <strong>Precio unitario:</strong>
            <span id="precioProveedor">$0.00</span>
        </p>

        <div class="modal-acciones">
            <button class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarDetalle()">Actualizar</button>
            <button class="btn-cancelar" type="button" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

{{-- Modal Eliminar --}}
<div id="modalEliminar" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">Eliminar producto</h3>
        <p>¿Seguro que deseas eliminarlo del pedido?</p>
        <div class="modal-acciones">
            <button class="btn" id="btnEliminarSi">Eliminar</button>
            <button class="btn-cancelar" id="btnEliminarNo">Cancelar</button>
        </div>
    </div>
</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
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

/* MODALES */
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
</style>

<script>
    const productosData = @json($productos);
    let productosPedido = @json($itemsPedido);

    // ✅ helper num seguro
    function num(v, def = 0) {
        if (v === null || v === undefined) return def;
        if (typeof v === 'string' && v.trim() === '') return def;
        const n = Number(v);
        return Number.isFinite(n) ? n : def;
    }

    // Normalizar
    productosPedido = (productosPedido || []).map(p => ({
        ...p,
        marca: p.marca ?? '',
        precio: num(p.precio, 0),
        cantidad_solicitada: num(p.cantidad_solicitada, 0),

        // si viene null => default a solicitada (solo para mostrar)
        cantidad_aprobada: (p.cantidad_aprobada === null || p.cantidad_aprobada === undefined)
            ? num(p.cantidad_solicitada, 0)
            : num(p.cantidad_aprobada, 0),

        // ✅ BD: activo 1/0 (default 1)
        activo: (p.activo === null || p.activo === undefined) ? 1 : num(p.activo, 1),

        subtotal: num(p.subtotal, 0),
        is_new: num(p.is_new, 0),
    }));

    let productoSeleccionado = null;
    let productoEditandoIndex = null;
    let indexEliminar = null;

    const modalCantidad  = document.getElementById('modalCantidad');
    const modalEliminar  = document.getElementById('modalEliminar');

    const proveedorSelect = document.getElementById('proveedorSelect');
    const cantidadSolicitadaInput = document.getElementById('cantidadSolicitadaInput');
    const cantidadAprobadaInput   = document.getElementById('cantidadAprobadaInput');
    const activoDetalleInput      = document.getElementById('activoDetalleInput');
    const hintSolicitada          = document.getElementById('hintSolicitada');

    const precioProveedor = document.getElementById('precioProveedor');
    const modalTitulo     = document.getElementById('modalTitulo');
    const btnAgregarModal    = document.getElementById('btnAgregarModal');
    const btnActualizarModal = document.getElementById('btnActualizarModal');

    document.addEventListener('DOMContentLoaded', () => {
        actualizarTablas();
    });

    function abrirModalProducto(productoId) {
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
            producto_id:          data.producto_id,
            proveedor_id:         data.proveedor_id,
            proveedor:            data.proveedor,
            precio:               data.precio,

            nombre:               producto.nombre,
            marca:                producto.marca || '',
            categoria:            producto.categoria ? producto.categoria.nombre : '',
            unidad:               producto.unidad_medida || ''
        };

        // ✅ Reset duro (para que no arrastre estados)
        cantidadSolicitadaInput.readOnly = false;
        hintSolicitada.style.display = "none";

        cantidadSolicitadaInput.value = 1;
        cantidadAprobadaInput.value   = 1;
        activoDetalleInput.value      = "1";

        precioProveedor.textContent = `$${num(productoSeleccionado.precio, 0).toFixed(2)}`;

        btnAgregarModal.style.display    = 'inline-block';
        btnActualizarModal.style.display = 'none';

        modalTitulo.textContent = `Agregar ${producto.nombre}`;
        modalCantidad.style.display = 'flex';
    }

    function actualizarPrecioProveedor() {
        const data = JSON.parse(proveedorSelect.value);

        productoSeleccionado.producto_proveedor_id = data.producto_proveedor_id;
        productoSeleccionado.producto_id           = data.producto_id;
        productoSeleccionado.proveedor_id          = data.proveedor_id;
        productoSeleccionado.proveedor             = data.proveedor;
        productoSeleccionado.precio                = data.precio;

        precioProveedor.textContent = `$${num(data.precio, 0).toFixed(2)}`;
    }

    function cerrarModal() {
        modalCantidad.style.display = 'none';
        productoEditandoIndex = null;

        // reset extra
        cantidadSolicitadaInput.readOnly = false;
        hintSolicitada.style.display = "none";
        cantidadSolicitadaInput.value = 1;
        cantidadAprobadaInput.value   = 1;
        activoDetalleInput.value      = "1";
    }

    function agregarProducto() {
        if (!productoSeleccionado) return;

        // ✅ defaults seguros: si aprobada viene vacía, = solicitada
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
            // 🔒 si ya existía, NO tocar solicitada
            existente.cantidad_aprobada = apr;
            existente.activo            = activo;
            existente.precio            = precio;
            existente.subtotal          = (activo === 1 ? (apr * precio) : 0);
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
        cerrarModal();
    }

    function editarProducto(index) {
        const p = productosPedido[index];
        productoEditandoIndex = index;

        abrirModalProducto(p.producto_id);

        // set proveedor actual
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

        precioProveedor.textContent = `$${num(data.precio,0).toFixed(2)}`;

        // 🔒 solicitada NO editable
        cantidadSolicitadaInput.value = num(p.cantidad_solicitada, 0);
        cantidadSolicitadaInput.readOnly = true;
        hintSolicitada.style.display = "block";

        cantidadAprobadaInput.value   = num(p.cantidad_aprobada, num(p.cantidad_solicitada, 0));
        activoDetalleInput.value      = String(num(p.activo, 1));

        btnAgregarModal.style.display    = 'none';
        btnActualizarModal.style.display = 'inline-block';
        modalTitulo.textContent          = `Editar ${p.nombre}`;
    }

    function actualizarDetalle() {
        if (productoEditandoIndex === null) return;

        const apr    = Math.max(0, num(cantidadAprobadaInput.value, 0));
        const activo = num(activoDetalleInput.value, 1);

        const data = JSON.parse(proveedorSelect.value);
        const p    = productosPedido[productoEditandoIndex];

        p.producto_proveedor_id = data.producto_proveedor_id;
        p.proveedor_id          = data.proveedor_id;
        p.proveedor             = data.proveedor;
        p.precio                = num(data.precio, 0);

        // 🔒 NO tocar solicitada
        p.cantidad_aprobada     = apr;
        p.activo                = activo;

        p.subtotal              = (activo === 1 ? (apr * p.precio) : 0);

        actualizarTablas();
        cerrarModal();
    }

    function badgeDelta(delta){
        const d = num(delta, 0);
        if (d > 0) return `<span class="badge-mini badge-ok">+${d}</span>`;
        if (d < 0) return `<span class="badge-mini badge-neg">${d}</span>`;
        return `<span class="badge-mini badge-zero">0</span>`;
    }

    // ✅ cambiar activo desde select (tabla activos)
    function cambiarActivo(index, value){
        productosPedido[index].activo = num(value, 1);
        actualizarTablas();
    }

    // ✅ reactivar desde tabla inactivos
    function reactivarProducto(index){
        productosPedido[index].activo = 1;

        // opcional: si aprobada es 0, vuelve a solicitada
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
            const apr    = num(p.cantidad_aprobada, sol);
            const activo = num(p.activo, 1);

            const delta = (apr - sol);
            const subtotal = (activo === 1 ? (apr * precio) : 0);
            p.subtotal = subtotal;

            if (activo === 1) {
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
            } else {
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
            }
        });
    }

    function abrirModalEliminar(i) {
        indexEliminar = i;
        modalEliminar.style.display = 'flex';
    }

    document.getElementById('btnEliminarSi').onclick = function () {
        if (indexEliminar !== null) {
            productosPedido.splice(indexEliminar, 1);
            actualizarTablas();
        }
        modalEliminar.style.display = 'none';
    };

    document.getElementById('btnEliminarNo').onclick = function () {
        modalEliminar.style.display = 'none';
    };

    document.getElementById('btnGuardarCambios').onclick = function () {
        if (productosPedido.length === 0) {
            alert('El pedido debe tener al menos un producto.');
            return;
        }
        document.getElementById('items_json').value = JSON.stringify(productosPedido);
        document.getElementById('formEditarPedido').submit();
    };
</script>
@endsection
