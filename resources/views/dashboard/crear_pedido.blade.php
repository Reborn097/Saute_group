@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    // ✅ SOLO estos ven catálogo completo + filtros
    $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos']);

    // ✅ para NO-admin: solo mostrar resultados si escribió algo
    $qActual = trim((string) request('q', ''));
    $mostrarResultadosNoAdmin = ($qActual !== '' && mb_strlen($qActual) >= 2);
@endphp

<div class="contenedor">

    <div class="acciones-superior">
        <button type="button" class="btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
    </div>

    {{-- ============================
            FILTROS SUPERIORES (GET)
         - ADMIN/ENCARGADO: filtros + catálogo
         - NO-ADMIN: solo buscador (sin catálogo completo)
    ============================= --}}
    <form method="GET" action="{{ route('dashboard.pedidos.solicitar') }}" class="filtros" id="formFiltros">

        {{-- ✅ SOLO ADMIN/ENCARGADO PEDIDOS: elegir unidad --}}
        @if($esAdminPedidos)
            <div class="campo" style="grid-column: span 3;">
                <label>Unidad operativa:</label>
                <select id="unidadOperativaSelect">
                    <option value="">— Selecciona —</option>
                    @foreach(($unidadesOperativas ?? []) as $uo)
                        <option value="{{ $uo->id }}">{{ $uo->nombre }}</option>
                    @endforeach
                </select>
                <small style="display:block; margin-top:6px; color:#555;">
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

        {{-- ✅ SOLO ADMIN/ENCARGADO: filtros de proveedor/categoría (autoselección sin botón) --}}
        @if($esAdminPedidos)
            <div class="campo">
                <label>Proveedor:</label>
                <select name="proveedor_id" id="proveedorFiltro">
                    <option value="">Todos</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                            {{ $proveedor->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label>Categoría:</label>
                <select name="categoria_id" id="categoriaFiltro">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="campo" style="{{ $esAdminPedidos ? 'grid-column: span 2;' : 'grid-column: span 3;' }}">
            <label>Buscar por nombre:</label>
            <input type="text"
                   name="q"
                   id="qInput"
                   value="{{ request('q') }}"
                   placeholder="{{ $esAdminPedidos ? 'Ej. Leche, harina, pollo...' : 'Escribe al menos 2 letras...' }}">
            @if(!$esAdminPedidos)
                <small style="display:block; margin-top:6px; color:#555;">
                    * No se muestra el catálogo completo. Solo verás productos cuando busques.
                </small>
            @endif
        </div>

        <div class="campo" style="display:flex; gap:10px; align-items:flex-end;">
            {{-- El botón existe, pero ya NO es necesario (auto-submit) --}}
            <button type="submit" class="btn" style="width:auto;">Buscar</button>

            <a href="{{ route('dashboard.pedidos.solicitar') }}"
               class="btn-cancelar"
               style="padding:8px 13px; border-radius:8px; text-decoration:none; color:white;">
                Limpiar
            </a>
        </div>
    </form>

    {{-- ============================
            PRODUCTOS DISPONIBLES
         - ADMIN/ENCARGADO: catálogo siempre
         - NO-ADMIN: solo mostrar si hay búsqueda válida (>=2)
    ============================= --}}
    <h3 class="titulo-seccion">Productos disponibles</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Unidad</th>
                <th>Precio</th>
                <th>Seleccionar</th>
            </tr>
            </thead>

            <tbody id="tbodyProductosDisponibles">
            @if(!$esAdminPedidos && !$mostrarResultadosNoAdmin)
                <tr>
                    <td colspan="5" style="padding:14px; text-align:center;">
                        Escribe al menos <b>2 letras</b> para buscar productos.
                    </td>
                </tr>
            @else
                @forelse($productos as $p)
                    @php
                        $primero = $p->proveedores->first();
                        $precioPrimero = $primero ? (float)$primero->pivot->precio : null;
                    @endphp
                    <tr>
                        <td>{{ $p->nombre }}</td>
                        <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                        <td>{{ $p->unidad_medida ?? 'N/A' }}</td>
                        <td>
                            @if($precioPrimero !== null)
                                ${{ number_format($precioPrimero, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn-seleccionar" onclick="abrirModalProducto({{ $p->id }})">
                                Seleccionar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding:14px; text-align:center;">
                            No hay productos con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            @endif
            </tbody>
        </table>

        {{-- ✅ Paginación (solo si aplica) --}}
        @if(($esAdminPedidos || $mostrarResultadosNoAdmin) && $productos->hasPages())
            <div class="paginacion" style="margin-top:12px;">
                {{ $productos->links('vendor.pagination.dashboard') }}
            </div>
        @endif
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

    <div class="acciones-final">
        <button type="button" class="btn-confirmar" id="btnHacerPedidoUI">Previsualizar pedido</button>
    </div>

</div>

{{-- ======================================================
                        MODALES
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
            <input type="number" id="cantidadInput" min="0" step="0.01" value="1">
        </div>

        <p class="precio-linea">
            <strong>Precio unitario:</strong>
            <span id="precioProveedor">$0.00</span>
        </p>

        <div class="modal-acciones">
            <button type="button" class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button type="button" class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarCantidad()">Actualizar</button>
            <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<div id="modalAdvertencia" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">⚠️ No se puede continuar</h3>
        <p id="textoAdvertencia">Completa las fechas antes de agregar productos.</p>
        <div class="modal-acciones">
            <button type="button" class="btn" onclick="cerrarModalAdvertencia()">Aceptar</button>
        </div>
    </div>
</div>

<div id="modalEliminar" class="modal">
    <div class="modal-contenido">
        <h3 class="warning-title">Eliminar producto</h3>
        <p>¿Seguro que deseas eliminarlo?</p>
        <div class="modal-acciones">
            <button type="button" class="btn" id="btnEliminarSi">Eliminar</button>
            <button type="button" class="btn-cancelar" id="btnEliminarNo">Cancelar</button>
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
.filtros{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:18px;
    margin-bottom:22px;
}
.campo label{
    display:block;
    font-weight:600;
    margin-bottom:4px;
}
input, select{
    width:100%;
    padding:7px;
    border-radius:6px;
    border:1px solid #ccc;
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
.btn-menu{ background:#999; }
.btn-menu:hover{ background:#777; }
.btn:hover{ background:#941c1c; }
.btn-cancelar{ background:#777; }
.acciones-final{
    margin-top:22px;
    padding-top:12px;
    display:flex;
    justify-content:flex-start;
}

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
.warning-title{ color:#b22b27; }

/* ===== PAGINACIÓN (centrada + iconos chicos) ===== */
.paginacion nav{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:6px;
}
.paginacion svg{
    width:16px !important;
    height:16px !important;
}
.paginacion a,
.paginacion span{
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    line-height:1 !important;
    padding:7px 10px !important;
    border-radius:10px;
}
.paginacion .hidden{
    display:none !important;
}
</style>

<script>
    const ES_ADMIN_PEDIDOS = @json($esAdminPedidos);
    const MOSTRAR_RESULTADOS_NO_ADMIN = @json($mostrarResultadosNoAdmin);

    // ===== refs
    const modalCantidad     = document.getElementById('modalCantidad');
    const modalAdvertencia  = document.getElementById('modalAdvertencia');
    const modalEliminar     = document.getElementById('modalEliminar');

    const modalTitulo       = document.getElementById('modalTitulo');
    const proveedorSelect   = document.getElementById('proveedorSelect');
    const cantidadInput     = document.getElementById('cantidadInput');
    const precioProveedor   = document.getElementById('precioProveedor');
    const btnAgregarModal   = document.getElementById('btnAgregarModal');
    const btnActualizarModal= document.getElementById('btnActualizarModal');

    const fechaSolicitud    = document.getElementById('fechaSolicitud');
    const fechaEntrega      = document.getElementById('fechaEntrega');
    const textoAdvertencia  = document.getElementById('textoAdvertencia');

    const formFiltros       = document.getElementById('formFiltros');
    const qInput            = document.getElementById('qInput');
    const proveedorFiltro   = document.getElementById('proveedorFiltro');
    const categoriaFiltro   = document.getElementById('categoriaFiltro');

    // ✅ solo los items (10)
    const productosData = @json($productos->items());

    let productosPedido = JSON.parse(localStorage.getItem('pedidoActual') || '[]');
    let productoSeleccionado = null;
    let productoEditandoIndex = null;
    let indexEliminar = null;

    // ✅ Unidad select (solo si existe en DOM)
    const unidadSelect = document.getElementById('unidadOperativaSelect');

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
        if (!ES_ADMIN_PEDIDOS || !unidadSelect) return;

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
        if (!ES_ADMIN_PEDIDOS || !unidadSelect) return;

        const idGuardado = localStorage.getItem('unidad_operativa_id');
        if (idGuardado) {
            unidadSelect.value = idGuardado;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // fechas
        const hoy = new Date().toISOString().split("T")[0];
        fechaSolicitud.value = localStorage.getItem('fechaSolicitud') || hoy;
        fechaEntrega.value = localStorage.getItem('fechaEntrega') || fechaSolicitud.value;

        fechaEntrega.addEventListener('change', () => {
            guardarFechasLS();
        });

        // unidad
        restaurarUnidadLS();
        if (unidadSelect) {
            unidadSelect.addEventListener('change', () => {
                guardarUnidadLS();
            });
            guardarUnidadLS();
        }

        // ✅ auto-submit en selects (como querías)
        if (proveedorFiltro) {
            proveedorFiltro.addEventListener('change', () => formFiltros.submit());
        }
        if (categoriaFiltro) {
            categoriaFiltro.addEventListener('change', () => formFiltros.submit());
        }

        // ✅ auto-submit en buscador (sin necesidad de botón)
        // - ADMIN: escribe y se filtra
        // - NO-ADMIN: solo muestra resultados cuando >=2 letras
        let t = null;
        if (qInput) {
            qInput.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => {
                    const q = (qInput.value || '').trim();
                    if (!ES_ADMIN_PEDIDOS) {
                        if (q.length < 2 && q.length > 0) return; // evita recargar a cada tecla si todavía no cumple
                        if (q.length === 0) {
                            // si borra, sí recarga para volver al mensaje "Escribe 2 letras"
                            formFiltros.submit();
                            return;
                        }
                    }
                    formFiltros.submit();
                }, 250);
            });
        }

        actualizarTablaPedido();
    });

    function mostrarAdvertencia(msg){
        textoAdvertencia.textContent = msg;
        modalAdvertencia.style.display = "flex";
    }

    function cerrarModalAdvertencia(){
        modalAdvertencia.style.display = "none";
    }

    function abrirModalProducto(producto_id) {
        if (!fechasValidas()){
            mostrarAdvertencia('Completa correctamente las fechas (la entrega no puede ser menor a la solicitud) antes de agregar productos.');
            return;
        }

        // ✅ NO-ADMIN: si aún no hay resultados válidos, no debería abrir
        if (!ES_ADMIN_PEDIDOS && !MOSTRAR_RESULTADOS_NO_ADMIN) {
            mostrarAdvertencia('Primero busca un producto (mínimo 2 letras) para poder seleccionarlo.');
            return;
        }

        const producto = (productosData || []).find(p => p.id == producto_id);
        if (!producto) {
            alert("Error: Producto no encontrado.");
            return;
        }

        const proveedores = producto.proveedores || [];
        if (proveedores.length === 0) {
            alert("Este producto no tiene proveedores asignados");
            return;
        }

        proveedorSelect.innerHTML = "";
        proveedores.forEach(prov => {
            const obj = {
                producto_proveedor_id: prov.pivot?.id ?? null,
                producto_id: producto.id,
                proveedor_id: prov.id,
                proveedor: prov.nombre,
                precio: num(prov.pivot?.precio, 0)
            };

            const opt = document.createElement("option");
            opt.value = JSON.stringify(obj);
            opt.textContent = `${prov.nombre} — $${obj.precio.toFixed(2)}`;
            proveedorSelect.appendChild(opt);
        });

        const data = JSON.parse(proveedorSelect.value);

        productoSeleccionado = {
            producto_proveedor_id: data.producto_proveedor_id,
            producto_id: data.producto_id,
            proveedor_id: data.proveedor_id,
            proveedor: data.proveedor,
            precio: num(data.precio, 0),
            nombre: producto.nombre,
            categoria: producto.categoria?.nombre ?? "",
            unidad: producto.unidad_medida ?? ""
        };

        precioProveedor.textContent = `$${num(productoSeleccionado.precio, 0).toFixed(2)}`;

        modalTitulo.textContent = `Agregar ${producto.nombre}`;
        btnAgregarModal.style.display = "inline-block";
        btnActualizarModal.style.display = "none";

        cantidadInput.value = 1;
        modalCantidad.style.display = "flex";
    }

    function actualizarPrecioProveedor() {
        const data = JSON.parse(proveedorSelect.value);

        productoSeleccionado.producto_proveedor_id = data.producto_proveedor_id;
        productoSeleccionado.producto_id           = data.producto_id;
        productoSeleccionado.proveedor_id          = data.proveedor_id;
        productoSeleccionado.proveedor             = data.proveedor;
        productoSeleccionado.precio                = num(data.precio, 0);

        precioProveedor.textContent = `$${num(data.precio, 0).toFixed(2)}`;
    }

    function cerrarModal(){
        modalCantidad.style.display = "none";
        productoEditandoIndex = null;
        cantidadInput.value = 1;
    }

    function agregarProducto(){
        if (!productoSeleccionado) return;

        if (!fechasValidas()){
            mostrarAdvertencia('Completa correctamente las fechas antes de agregar productos.');
            return;
        }

        const cant = Math.max(0, num(cantidadInput.value, 1));
        const precio = num(productoSeleccionado.precio, 0);

        const idxExist = productosPedido.findIndex(x => x.producto_proveedor_id == productoSeleccionado.producto_proveedor_id);

        if (idxExist >= 0) {
            productosPedido[idxExist].cantidad = cant;
            productosPedido[idxExist].precio   = precio;
            productosPedido[idxExist].proveedor = productoSeleccionado.proveedor;
            productosPedido[idxExist].subtotal = cant * precio;
        } else {
            productosPedido.push({
                ...productoSeleccionado,
                cantidad: cant,
                precio: precio,
                subtotal: cant * precio
            });
        }

        localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
        guardarFechasLS();
        guardarUnidadLS();

        actualizarTablaPedido();
        cerrarModal();
    }

    function editarProducto(i){
        const p = productosPedido[i];
        productoEditandoIndex = i;

        abrirModalProducto(p.producto_id);

        cantidadInput.value = num(p.cantidad, 1);

        [...proveedorSelect.options].forEach(op => {
            const obj = JSON.parse(op.value);
            if (obj.producto_proveedor_id == p.producto_proveedor_id) {
                proveedorSelect.value = op.value;
            }
        });

        const data = JSON.parse(proveedorSelect.value);
        precioProveedor.textContent = `$${num(data.precio,0).toFixed(2)}`;

        btnAgregarModal.style.display = "none";
        btnActualizarModal.style.display = "inline-block";
        modalTitulo.textContent = `Editar ${p.nombre}`;
    }

    function actualizarCantidad(){
        if (productoEditandoIndex === null) return;

        const nuevaCantidad = Math.max(0, num(cantidadInput.value, 0));

        const prov = JSON.parse(proveedorSelect.value);
        let p = productosPedido[productoEditandoIndex];

        p.cantidad = nuevaCantidad;
        p.producto_proveedor_id = prov.producto_proveedor_id;
        p.proveedor = prov.proveedor;
        p.precio = num(prov.precio, 0);
        p.subtotal = p.cantidad * p.precio;

        localStorage.setItem("pedidoActual", JSON.stringify(productosPedido));
        actualizarTablaPedido();
        cerrarModal();
    }

    function pedirEliminar(i){
        indexEliminar = i;
        modalEliminar.style.display = "flex";
    }

    document.getElementById('btnEliminarSi').onclick = function () {
        if (indexEliminar !== null) {
            productosPedido.splice(indexEliminar, 1);
            localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
            actualizarTablaPedido();
        }
        modalEliminar.style.display = "none";
        indexEliminar = null;
    };

    document.getElementById('btnEliminarNo').onclick = function () {
        modalEliminar.style.display = "none";
        indexEliminar = null;
    };

    function actualizarTablaPedido(){
        const tbody = document.querySelector('#tablaPedido tbody');
        tbody.innerHTML = "";

        productosPedido.forEach((p, i) => {
            const precio = num(p.precio, 0);
            const cantidad = num(p.cantidad, 0);
            const subtotal = cantidad * precio;
            p.subtotal = subtotal;

            tbody.innerHTML += `
                <tr>
                    <td>${p.nombre || ''}</td>
                    <td>${p.categoria || ''}</td>
                    <td>${p.unidad || ''}</td>
                    <td>${cantidad}</td>
                    <td>${p.proveedor || ''}</td>
                    <td>$${precio.toFixed(2)}</td>
                    <td>$${subtotal.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn" onclick="editarProducto(${i})">Editar</button>
                        <button type="button" class="btn-cancelar" onclick="pedirEliminar(${i})">Eliminar</button>
                    </td>
                </tr>
            `;
        });

        localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
    }

    document.getElementById('btnHacerPedidoUI').onclick = () => {
        if (!fechasValidas()){
            mostrarAdvertencia('Completa correctamente las fechas (la entrega no puede ser menor a la solicitud).');
            return;
        }

        if(productosPedido.length === 0){
            mostrarAdvertencia('Agrega al menos un producto para continuar.');
            return;
        }

        // ✅ bloqueo admin/encargado si no eligió unidad
        if (ES_ADMIN_PEDIDOS) {
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
