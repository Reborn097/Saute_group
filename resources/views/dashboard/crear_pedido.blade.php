@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido')

@section('contenido')
<div class="contenedor">
    <div class="acciones-superior">
        <button class="btn-menu" onclick="irMenuPrincipal()">Menú principal</button>
    </div>

    {{-- Encabezado --}}
    <div class="filtros">
        <div class="campo">
            <label>Fecha de solicitud:</label>
            <input type="date" id="fechaSolicitud" required readonly>
        </div>
        <div class="campo">
            <label>Fecha de entrega:</label>
            <input type="date" id="fechaEntrega" required>
        </div>
        <div class="campo">
            <label>Proveedor:</label>
            <select id="proveedorFiltro">
                <option value="todos">Todos</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->nombre }}">{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tabla de productos disponibles --}}
    <h3>Productos disponibles</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Unidad de medida</th>
                <th>Precio</th>
                <th>Seleccionar</th>
            </tr>
            </thead>
            <tbody>
            @foreach($productos as $p)
                <tr data-proveedor="{{ $p->proveedores->first()->nombre ?? 'N/A' }}">
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                    <td>{{ $p->unidad_medida ?? 'N/A' }}</td>
                    <td>${{ number_format($p->proveedores->first()->pivot->precio ?? 0, 2) }}</td>
                    <td>
                        <button class="btn-seleccionar"
                            onclick="abrirModal({{ $p->id }}, '{{ $p->nombre }}', '{{ $p->categoria->nombre ?? '' }}', '{{ $p->unidad_medida }}')">
                            Seleccionar
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Tabla de productos en el pedido --}}
    <h3>Productos en el pedido</h3>
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

    {{-- Botón final --}}
    <div class="acciones-final">
        <button class="btn-confirmar" id="btnHacerPedidoUI">Hacer pedido</button>
    </div>
    <button id="btnHacerPedido" style="display:none;"></button>
</div>

{{-- Modal: seleccionar proveedor y cantidad --}}
<div id="modalCantidad" class="modal">
    <div class="modal-contenido">
        <h3 id="modalTitulo"></h3>

        <div class="grupo">
            <label for="proveedorSelect">Proveedor</label>
            <select id="proveedorSelect" onchange="actualizarPrecioProveedor()"></select>
        </div>

        <div class="grupo">
            <label for="cantidadInput">Cantidad</label>
            <input type="number" id="cantidadInput" min="1" value="1">
        </div>

        <p class="precio-linea">
            <strong>Precio unitario:</strong>
            <span id="precioProveedor">$0.00</span>
        </p>

        <div class="modal-acciones">
            <button class="btn" id="btnAgregarModal" onclick="agregarProducto()">Agregar</button>
            <button class="btn" id="btnActualizarModal" style="display:none;" onclick="actualizarCantidad()">Actualizar</button>
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

{{-- Modal confirmar pedido --}}
<div id="modalConfirmar" class="modal">
    <div class="modal-contenido">
        <h3>Confirmar pedido</h3>
        <p>Verifica que los datos sean correctos antes de continuar.</p>
        <div class="modal-acciones">
            <button class="btn" id="btnConfirmarSi">Continuar</button>
            <button class="btn-cancelar" id="btnConfirmarNo">Cancelar</button>
        </div>
    </div>
</div>

{{-- Modal advertencia fechas --}}
<div id="modalAdvertencia" class="modal">
    <div class="modal-contenido">
        <h3 style="color:#b22b27; display:flex; align-items:center; justify-content:center; gap:8px;">⚠️
            <span>No se puede continuar</span></h3>
        <p>Por favor selecciona las fechas antes de agregar productos.</p>
        <div class="modal-acciones">
            <button class="btn" onclick="cerrarModalAdvertencia()">Aceptar</button>
        </div>
    </div>
</div>

{{-- Modal eliminar --}}
<div id="modalEliminar" class="modal">
    <div class="modal-contenido">
        <h3 style="color:#b22b27;">Eliminar producto</h3>
        <p>¿Seguro que deseas eliminar este producto del pedido?</p>
        <div class="modal-acciones">
            <button class="btn" id="btnEliminarSi">Eliminar</button>
            <button class="btn-cancelar" id="btnEliminarNo">Cancelar</button>
        </div>
    </div>
</div>

<style>
.contenedor{
    background:#fceede; padding:25px 35px; border-radius:12px;
    box-shadow:0 0 10px rgba(0,0,0,.1); max-width:1100px; margin:0 auto;
    font-family:'Poppins',sans-serif;
}
.acciones-superior{display:flex; justify-content:flex-start; margin-bottom:16px}
.btn-menu{background:#b22b27; color:#fff; border:none; border-radius:8px; padding:8px 14px; font-weight:600; cursor:pointer}
.btn-menu:hover{background:#941c1c}
.filtros{display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-bottom:16px}
.campo label{display:block; margin-bottom:6px; font-weight:600; color:#333}
.campo input,.campo select{width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; font-size:1em}
.tabla{width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 3px 6px rgba(0,0,0,.1)}
.tabla th{background:#b22b27; color:#fff; padding:10px; text-align:center}
.tabla td{padding:10px; border-bottom:1px solid #eee; text-align:center}
.tabla tr:hover{background:#f8dcdc}
.btn-seleccionar,.btn-confirmar,.btn,.btn-editar,.btn-eliminar{
    background:#b22b27; color:#fff; border:none; border-radius:8px; padding:8px 12px; font-weight:600; cursor:pointer; transition:.2s}
.btn-seleccionar:hover,.btn-confirmar:hover,.btn:hover{background:#941c1c}
.btn-eliminar{background:#888}.btn-eliminar:hover{background:#666}
.acciones-final{display:flex; justify-content:flex-end; margin-top:18px}
.modal{display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:999; align-items:center; justify-content:center}
.modal-contenido{background:#fff; width:380px; max-width:90vw; border-radius:12px; padding:22px 24px; box-shadow:0 8px 24px rgba(0,0,0,.25); text-align:center}
.modal-acciones{display:flex; gap:12px; justify-content:center; margin-top:14px}
.btn-cancelar{background:#888}.btn-cancelar:hover{background:#666}
.grupo{text-align:left; margin:10px 0}
.grupo select,.grupo input{width:100%; padding:8px; border-radius:8px; border:1px solid #ccc}
</style>

<script>
let productoSeleccionado = null;
let productoEditandoIndex = null;
const productosPedido = [];

/* === Abrir modal con proveedores === */
function abrirModal(id, nombre, categoria, unidad, editar=false){
    const fs=document.getElementById('fechaSolicitud').value;
    const fe=document.getElementById('fechaEntrega').value;
    if(!fs||!fe){mostrarModalAdvertencia();return;}

    const productos=@json($productos);
    const producto=productos.find(p=>p.id==id);
    const proveedores=producto?.proveedores ?? [];

    const sel=document.getElementById('proveedorSelect');
    sel.innerHTML='';
    if(proveedores.length===0){
        sel.innerHTML="<option value=''>Sin proveedores</option>";
    }else{
        proveedores.forEach(prov=>{
            const precio=parseFloat(prov.pivot.precio).toFixed(2);
            const opt=document.createElement('option');
            // ✅ Guardamos el ID real del pivote (producto_proveedor.id)
            opt.value=JSON.stringify({
                producto_proveedor_id: prov.pivot.id,
                nombre: prov.nombre,
                precio: precio
            });
            opt.textContent=`${prov.nombre} — $${precio}`;
            sel.appendChild(opt);
        });
    }

    const precioInicial=proveedores.length?proveedores[0].pivot.precio:0;
    productoSeleccionado={
        id, nombre, categoria, unidad,
        producto_proveedor_id: proveedores.length ? proveedores[0].pivot.id : null,
        proveedor: proveedores.length ? proveedores[0].nombre : 'N/A',
        precio: parseFloat(precioInicial)
    };

    document.getElementById('precioProveedor').textContent=`$${parseFloat(precioInicial).toFixed(2)}`;
    document.getElementById('modalTitulo').textContent=editar?`Editar ${nombre}`:`Agregar ${nombre}`;
    document.getElementById('btnAgregarModal').style.display=editar?'none':'inline-block';
    document.getElementById('btnActualizarModal').style.display=editar?'inline-block':'none';
    document.getElementById('modalCantidad').style.display='flex';
}

/* === Cambiar proveedor => actualizar precio === */
function actualizarPrecioProveedor(){
    const val=document.getElementById('proveedorSelect').value;
    if(!val) return;
    const obj=JSON.parse(val);
    productoSeleccionado.producto_proveedor_id=obj.producto_proveedor_id; // ✅ actualiza pivote correctamente
    productoSeleccionado.proveedor=obj.nombre;
    productoSeleccionado.precio=parseFloat(obj.precio);
    document.getElementById('precioProveedor').textContent=`$${parseFloat(obj.precio).toFixed(2)}`;
}

/* Fecha actual */
document.addEventListener('DOMContentLoaded',()=>{
    const hoy=new Date();
    document.getElementById('fechaSolicitud').value=hoy.toISOString().split('T')[0];
});

function cerrarModal(){ document.getElementById('modalCantidad').style.display='none'; productoEditandoIndex=null; }

/* === Agregar producto === */
function agregarProducto(){
    const cant=parseFloat(document.getElementById('cantidadInput').value);
    if(!cant || cant<=0) return alert('Cantidad inválida');
    if(!productoSeleccionado.producto_proveedor_id){
        alert("Error: No se identificó el proveedor del producto.");
        return;
    }
    const subtotal=cant*productoSeleccionado.precio;
    productosPedido.push({
        ...productoSeleccionado,
        cantidad:cant,
        subtotal,
        producto_proveedor_id: productoSeleccionado.producto_proveedor_id // ✅ forzado a guardar el pivote real
    });
    actualizarTablaPedido(); cerrarModal();
}

/* === Editar / Actualizar / Eliminar === */
function editarProducto(i){
    const p=productosPedido[i]; productoSeleccionado=p; productoEditandoIndex=i;
    document.getElementById('cantidadInput').value=p.cantidad;
    abrirModal(p.id,p.nombre,p.categoria,p.unidad,true);
}
function actualizarCantidad(){
    const n=parseFloat(document.getElementById('cantidadInput').value);
    if(!n||n<=0) return alert('Cantidad inválida');
    const p=productosPedido[productoEditandoIndex]; p.cantidad=n; p.subtotal=p.precio*n;
    actualizarTablaPedido(); cerrarModal();
}
let idxEliminar=null;
function eliminarProducto(i){ idxEliminar=i; document.getElementById('modalEliminar').style.display='flex'; }
document.getElementById('btnEliminarNo').onclick=()=>{ document.getElementById('modalEliminar').style.display='none'; idxEliminar=null; };
document.getElementById('btnEliminarSi').onclick=()=>{
    if(idxEliminar!==null){ productosPedido.splice(idxEliminar,1); actualizarTablaPedido(); }
    document.getElementById('modalEliminar').style.display='none'; idxEliminar=null;
};

/* === Render tabla === */
function actualizarTablaPedido(){
    const tbody=document.querySelector('#tablaPedido tbody'); tbody.innerHTML='';
    if(productosPedido.length===0){
        tbody.innerHTML=`<tr><td colspan="8" style="text-align:center;">No hay productos seleccionados.</td></tr>`;
        return;
    }
    productosPedido.forEach((p,i)=>{
        tbody.innerHTML+=`
            <tr>
                <td>${p.nombre}</td>
                <td>${p.categoria}</td>
                <td>${p.unidad}</td>
                <td>${p.cantidad}</td>
                <td>${p.proveedor}</td>
                <td>$${p.precio.toFixed(2)}</td>
                <td>$${p.subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn-editar btn" onclick="editarProducto(${i})">Editar</button>
                    <button class="btn-eliminar" onclick="eliminarProducto(${i})">Eliminar</button>
                </td>
            </tr>`;
    });
    console.log("Productos guardados localStorage:", productosPedido); // 👀 verificación
    localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
}

/* === Confirmar pedido === */
document.getElementById('btnHacerPedidoUI').onclick=()=>{
    if(productosPedido.length===0){ alert('Agrega al menos un producto al pedido.'); return; }
    document.getElementById('modalConfirmar').style.display='flex';
};
document.getElementById('btnConfirmarNo').onclick=()=>{ document.getElementById('modalConfirmar').style.display='none'; };
document.getElementById('btnConfirmarSi').onclick=()=>{
    console.log("Pedido final:", productosPedido); // 👀 aseguramos que el pivote exista
    localStorage.setItem('pedidoActual', JSON.stringify(productosPedido));
    localStorage.setItem('fechaSolicitud', document.getElementById('fechaSolicitud').value);
    localStorage.setItem('fechaEntrega', document.getElementById('fechaEntrega').value);
    window.location.href="{{ route('dashboard.pedidos.previsualizar') }}";
};

/* === Modales auxiliares === */
function mostrarModalAdvertencia(){ document.getElementById('modalAdvertencia').style.display='flex'; }
function cerrarModalAdvertencia(){ document.getElementById('modalAdvertencia').style.display='none'; }
function irMenuPrincipal(){ localStorage.clear(); window.location.href="{{ route('dashboard.admin') }}"; }
</script>
@endsection
