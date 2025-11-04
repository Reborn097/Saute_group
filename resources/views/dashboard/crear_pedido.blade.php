@extends('layouts.dashboard')

@section('titulo', 'Crear Pedido')

@section('contenido')
<div class="contenedor">

    {{-- 🔙 Botón de regreso al menú --}}
    <div class="acciones-superior">
        <button class="btn-volver" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>
    </div>

    {{-- Encabezado de fechas y proveedor --}}
    <div class="filtros-superiores">
        <div class="campo">
            <label>Fecha de solicitud:</label>
            <input type="date" name="fecha_solicitud">
        </div>
        <div class="campo">
            <label>Fecha de entrega:</label>
            <input type="date" name="fecha_entrega">
        </div>
        <div class="campo">
            <label>Proveedor:</label>
            <select name="proveedor" id="proveedorSelect" onchange="filtrarPorProveedor()">
                <option value="todos">Todos</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tabla de productos disponibles --}}
    <h3>Productos disponibles</h3>
    <div class="tabla-contenedor">
        <table class="tabla-productos" id="tabla-productos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Unidad de medida</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody id="productos-lista">
                @foreach($productos as $producto)
                    <tr onclick="mostrarModalProducto({{ $producto->id }})"
                        data-proveedor="{{ $producto->proveedores->first()->id ?? '' }}">
                        <td>{{ $producto->nombre }}</td>
                        <td>{{ $producto->categoria->nombre ?? 'Sin categoría' }}</td>
                        <td>{{ $producto->unidad_medida }}</td>
                        <td>
                            @if($producto->proveedores->isNotEmpty())
                                ${{ number_format($producto->proveedores->first()->pivot->precio, 2) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal para agregar producto --}}
    <div class="modal" id="modalProducto">
        <div class="modal-content">
            <span class="cerrar" onclick="cerrarModal()">&times;</span>
            <h4 id="nombreProducto"></h4>
            <p id="unidadMedida"></p>
            <p id="precioProducto"></p>

            <label for="cantidad">Cantidad:</label>
            <input type="number" id="cantidad" min="1" value="1">

            <button class="btn-agregar" onclick="agregarProducto()">Agregar</button>
        </div>
    </div>

    {{-- Tabla de productos seleccionados --}}
    <h3>Productos en el pedido</h3>
    <div class="tabla-contenedor">
        <table class="tabla-productos" id="tablaSeleccionados">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Unidad de medida</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="productosSeleccionados">
                {{-- Productos agregados dinámicamente --}}
            </tbody>
        </table>
    </div>

    {{-- Botón de hacer pedido --}}
    <div class="acciones">
        <button class="btn-confirmar" onclick="redirigirPrevisualizacion()">Hacer pedido</button>
    </div>

</div>

{{-- 🎨 Estilos --}}
<style>
    .contenedor {
        background-color: #fae7d0;
        padding: 25px 35px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        max-width: 1100px;
        margin: 0 auto;
    }

    .acciones-superior {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 20px;
    }

    .btn-volver {
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 8px;
        font-size: 1em;
        cursor: pointer;
    }

    .btn-volver:hover {
        background-color: #911f1d;
    }

    .filtros-superiores {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
    }

    .campo {
        display: flex;
        flex-direction: column;
        font-weight: bold;
    }

    input[type="date"], select, input[type="number"] {
        padding: 6px;
        border-radius: 8px;
        border: 1px solid #aaa;
        font-size: 1em;
    }

    .tabla-contenedor {
        overflow-x: auto;
        margin-top: 15px;
        margin-bottom: 25px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
    }

    th, td {
        border: 1px solid #aaa;
        padding: 10px;
        text-align: left;
    }

    th {
        background-color: #f0f0f0;
    }

    tr:hover {
        background-color: #f8f2ec;
        cursor: pointer;
    }

    .btn-agregar, .btn-confirmar {
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
    }

    .btn-agregar:hover, .btn-confirmar:hover {
        background-color: #911f1d;
    }

    .acciones {
        display: flex;
        justify-content: flex-end;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 10;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fff8f0;
        margin: 10% auto;
        padding: 20px;
        border-radius: 12px;
        width: 300px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.25);
        text-align: center;
    }

    .cerrar {
        float: right;
        font-size: 1.2em;
        cursor: pointer;
    }
</style>

{{-- 💻 Script --}}
<script>
    let productos = @json($productos);
    let productosSeleccionados = [];

    function filtrarPorProveedor() {
        const filtro = document.getElementById('proveedorSelect').value;
        const filas = document.querySelectorAll('#productos-lista tr');
        filas.forEach(fila => {
            const proveedor = fila.getAttribute('data-proveedor');
            fila.style.display = (filtro === 'todos' || proveedor === filtro) ? '' : 'none';
        });
    }

    function mostrarModalProducto(id) {
        const producto = productos.find(p => p.id === id);
        if (!producto) return;

        const modal = document.getElementById('modalProducto');
        modal.style.display = 'block';

        document.getElementById('nombreProducto').innerText = producto.nombre;
        document.getElementById('unidadMedida').innerText = "Unidad de medida: " + producto.unidad_medida;

        let precio = 0;
        if (producto.proveedores && producto.proveedores.length > 0) {
            const valor = producto.proveedores[0]?.pivot?.precio ?? 0;
            precio = parseFloat(valor);
        }

        const precioFormateado = isNaN(precio) ? 0 : precio;
        document.getElementById('precioProducto').innerText = "Precio: $" + precioFormateado.toFixed(2);
        document.getElementById('cantidad').value = 1;
        document.getElementById('cantidad').setAttribute('data-id', id);
        document.getElementById('cantidad').setAttribute('data-precio', precioFormateado);
    }

    function cerrarModal() {
        document.getElementById('modalProducto').style.display = 'none';
    }

    function agregarProducto() {
        const id = parseInt(document.getElementById('cantidad').getAttribute('data-id'));
        const precio = parseFloat(document.getElementById('cantidad').getAttribute('data-precio')) || 0;
        const cantidad = parseFloat(document.getElementById('cantidad').value);
        const producto = productos.find(p => p.id === id);

        if (!producto || cantidad <= 0) return;

        const subtotal = precio * cantidad;

        productosSeleccionados.push({
            id: producto.id,
            nombre: producto.nombre,
            categoria: producto.categoria?.nombre ?? 'Sin categoría',
            unidad: producto.unidad_medida,
            cantidad,
            subtotal
        });

        actualizarTablaSeleccionados();
        cerrarModal();
    }

    function actualizarTablaSeleccionados() {
        const cuerpo = document.getElementById('productosSeleccionados');
        cuerpo.innerHTML = '';

        productosSeleccionados.forEach(p => {
            const fila = `
                <tr>
                    <td>${p.nombre}</td>
                    <td>${p.categoria}</td>
                    <td>${p.unidad}</td>
                    <td>${p.cantidad}</td>
                    <td>$${p.subtotal.toFixed(2)}</td>
                </tr>
            `;
            cuerpo.insertAdjacentHTML('beforeend', fila);
        });
    }

    function redirigirPrevisualizacion() {
        localStorage.setItem('pedidoActual', JSON.stringify(productosSeleccionados));
        window.location.href = "{{ route('dashboard.pedidos.previsualizar') }}";

    }
</script>
@endsection
