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
                TABLA DE PRODUCTOS
        ============================= --}}
        <h3 class="titulo-seccion">Productos disponibles</h3>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Unidad</th>
                        <th>Precio (primer proveedor)</th>
                        <th>Seleccionar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos as $p)
                        @php
                            $primero = $p->proveedores->first();
                        @endphp
                        <tr>
                            <td>{{ $p->nombre }}</td>
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

        {{-- ============================
                TABLA DEL PEDIDO
        ============================= --}}
        <h3 class="titulo-seccion">Productos en el pedido</h3>

        <div class="tabla-contenedor">
            <table class="tabla" id="tablaPedido">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Unidad</th>
                        <th>Cantidad</th>
                        <th>Proveedor</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- BOTÓN GUARDAR --}}
        <div class="acciones-final">
            <button type="button" class="btn-confirmar" id="btnGuardarCambios">
                Guardar cambios
            </button>
        </div>
    </form>
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
            <label>Cantidad</label>
            <input type="number" id="cantidadInput" min="1" value="1">
        </div>

        <p class="precio-linea">
            <strong>Precio unitario:</strong>
            <span id="precioProveedor">$0.00</span>
        </p>

        <div class="modal-acciones">
            <button class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarCantidad()">Actualizar</button>
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


{{-- ======================================================
                        ESTILOS
====================================================== --}}
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

.tabla-contenedor{
    margin-top:10px;
}

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

.tabla tr:hover{
    background:#f5d6d6;
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

.btn-menu{
    margin-bottom:15px;
}

.btn:hover{
    background:#941c1c;
}

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
    width:380px;
    padding:20px;
    border-radius:12px;
    text-align:center;
}

.modal-acciones{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:15px;
}

.warning-title{
    color:#b22b27;
}
</style>


{{-- ======================================================
                        SCRIPTS
====================================================== --}}
<script>
    // Datos desde el backend
    const productosData   = @json($productos);
    let productosPedido   = @json($itemsPedido);
    let productoSeleccionado = null;
    let productoEditandoIndex = null;
    let indexEliminar = null;

    const modalCantidad  = document.getElementById('modalCantidad');
    const modalEliminar  = document.getElementById('modalEliminar');
    const proveedorSelect = document.getElementById('proveedorSelect');
    const cantidadInput   = document.getElementById('cantidadInput');
    const precioProveedor = document.getElementById('precioProveedor');
    const modalTitulo     = document.getElementById('modalTitulo');
    const btnAgregarModal    = document.getElementById('btnAgregarModal');
    const btnActualizarModal = document.getElementById('btnActualizarModal');

    document.addEventListener('DOMContentLoaded', () => {
        actualizarTablaPedido();
    });

    // Abrir modal para agregar
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
            categoria:            producto.categoria ? producto.categoria.nombre : '',
            unidad:               producto.unidad_medida || ''
        };

        cantidadInput.value = 1;
        precioProveedor.textContent = `$${productoSeleccionado.precio.toFixed(2)}`;

        btnAgregarModal.style.display    = 'inline-block';
        btnActualizarModal.style.display = 'none';

        modalTitulo.textContent = `Agregar ${producto.nombre}`;
        modalCantidad.style.display = 'flex';
    }

    // Actualizar precio cuando cambia proveedor
    function actualizarPrecioProveedor() {
        const data = JSON.parse(proveedorSelect.value);

        productoSeleccionado.producto_proveedor_id = data.producto_proveedor_id;
        productoSeleccionado.producto_id           = data.producto_id;
        productoSeleccionado.proveedor_id          = data.proveedor_id;
        productoSeleccionado.proveedor             = data.proveedor;
        productoSeleccionado.precio                = data.precio;

        precioProveedor.textContent = `$${data.precio.toFixed(2)}`;
    }

    function cerrarModal() {
        modalCantidad.style.display = 'none';
    }

    // Agregar producto NUEVO al pedido
    function agregarProducto() {
        const cant = parseFloat(cantidadInput.value);

        if (cant <= 0 || !productoSeleccionado) {
            return;
        }

        // Buscar si ya existe misma combinación producto_proveedor
        const existente = productosPedido.find(p =>
            p.producto_proveedor_id == productoSeleccionado.producto_proveedor_id
        );

        if (existente) {
            existente.cantidad  = parseFloat(existente.cantidad) + cant;
            existente.precio    = parseFloat(productoSeleccionado.precio);
            existente.subtotal  = existente.cantidad * existente.precio;
        } else {
            productosPedido.push({
                ...productoSeleccionado,
                cantidad: cant,
                precio: parseFloat(productoSeleccionado.precio),
                subtotal: cant * parseFloat(productoSeleccionado.precio)
            });
        }

        actualizarTablaPedido();
        cerrarModal();
    }

    // Editar una fila existente
    function editarProducto(index) {
        const p = productosPedido[index];
        productoEditandoIndex = index;

        // usamos el mismo flujo que agregar, pero precargando
        abrirModalProducto(p.producto_id);

        cantidadInput.value = p.cantidad;

        // seleccionar el proveedor actual en el combo
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
        precioProveedor.textContent                = `$${data.precio.toFixed(2)}`;

        btnAgregarModal.style.display    = 'none';
        btnActualizarModal.style.display = 'inline-block';
        modalTitulo.textContent          = `Editar ${p.nombre}`;
    }

    function actualizarCantidad() {
        const nuevaCant = parseFloat(cantidadInput.value);

        if (nuevaCant <= 0 || productoEditandoIndex === null) {
            return;
        }

        const data = JSON.parse(proveedorSelect.value);
        const p    = productosPedido[productoEditandoIndex];

        p.producto_proveedor_id = data.producto_proveedor_id;
        p.proveedor_id          = data.proveedor_id;
        p.proveedor             = data.proveedor;
        p.precio                = data.precio;
        p.cantidad              = nuevaCant;
        p.subtotal              = nuevaCant * p.precio;

        actualizarTablaPedido();
        cerrarModal();
    }

    // Tabla
    function actualizarTablaPedido() {
        const tbody = document.querySelector('#tablaPedido tbody');
        tbody.innerHTML = '';

        productosPedido.forEach((p, i) => {
            const precio   = parseFloat(p.precio);
            const subtotal = parseFloat(p.subtotal ?? (p.cantidad * precio));

            tbody.innerHTML += `
                <tr>
                    <td>${p.nombre}</td>
                    <td>${p.categoria}</td>
                    <td>${p.unidad}</td>
                    <td>${p.cantidad}</td>
                    <td>${p.proveedor}</td>
                    <td>$${precio.toFixed(2)}</td>
                    <td>$${subtotal.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn" onclick="editarProducto(${i})">Editar</button>
                        <button type="button" class="btn-cancelar" onclick="abrirModalEliminar(${i})">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    }

    function abrirModalEliminar(i) {
        indexEliminar = i;
        modalEliminar.style.display = 'flex';
    }

    document.getElementById('btnEliminarSi').onclick = function () {
        if (indexEliminar !== null) {
            productosPedido.splice(indexEliminar, 1);
            actualizarTablaPedido();
        }
        modalEliminar.style.display = 'none';
    };
    document.getElementById('btnEliminarNo').onclick = function () {
        modalEliminar.style.display = 'none';
    };

    // Enviar al backend
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
