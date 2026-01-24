@extends('layouts.dashboard')

@section('titulo', 'Detalle del Pedido')

@section('contenido')

<div class="contenedor">

    <a id="btnRegresar" class="btn-menu" href="{{ route('dashboard.pedidos.admin.index') }}">
    Regresar
    </a>


    <h2>Detalle del pedido #{{ $pedido->codigo }}</h2>

    <p><strong>Fecha solicitud:</strong> {{ $pedido->fecha_solicitud }}</p>
    <p><strong>Fecha entrega:</strong> {{ $pedido->fecha_entrega }}</p>
    <p><strong>Total:</strong> ${{ number_format($pedido->total, 2) }}</p>

    <p><strong>Estado actual:</strong>
        <span class="badge estado-{{ strtolower(str_replace(' ', '-', $pedido->estado)) }}">
            {{ ucfirst($pedido->estado) }}
        </span>
    </p>

    <!-- FORMULARIO CAMBIAR ESTADO -->
    <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

        <label><b>Cambiar estado:</b></label>
        <select name="estado" class="input-select" required>
            <option value="pendiente"   {{ $pedido->estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="en proceso"  {{ $pedido->estado === 'en proceso' ? 'selected' : '' }}>En proceso</option>
            <option value="pre-aprobado"{{ $pedido->estado === 'pre-aprobado' ? 'selected' : '' }}>Pre-aprobado</option>
            <option value="aprobado"    {{ $pedido->estado === 'aprobado' ? 'selected' : '' }}>Aprobado</option>
            <option value="finalizado"  {{ $pedido->estado === 'finalizado' ? 'selected' : '' }}>Finalizado</option>
        </select>

        <button class="btn-actualizar">Actualizar</button>
    </form>


    <br>

    <h3>Productos</h3>

    <table class="tabla">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>
            @foreach($pedido->detalles as $d)
            <tr>
                <td>{{ $d->productoProveedor->producto->nombre }}</td>
                <td>{{ $d->productoProveedor->producto->categoria->nombre }}</td>
                <td>{{ $d->cantidad_solicitada }}</td>
                <td>${{ number_format($d->precio_unitario, 2) }}</td>
                <td>${{ number_format($d->cantidad_solicitada * $d->precio_unitario, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

<style>
/* CONTENEDOR */
.contenedor{
    background:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* TABLA */
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

/* BOTÓN REGRESAR */
.btn-menu{
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 15px;
    border-radius:8px;
    cursor:pointer;
    margin-bottom:20px;
}
.btn-menu:hover{
    background:#941c1c;
}

/* SELECT */
.input-select{
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
    margin-bottom:10px;
}

/* BOTÓN ACTUALIZAR */
.btn-actualizar{
    background:#b22b27;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    margin-top:10px;
}
.btn-actualizar:hover{
    background:#941c1c;
}

/* BADGES DE ESTADO (MISMO DISEÑO EN TODAS LAS VISTAS) */
.badge{
    padding:5px 12px;
    border-radius:15px;
    font-size:13px;
    font-weight:600;
    display:inline-block;
}

/* COLORES DE ESTADO */
.estado-pendiente{
    background:#ffe08a;
}
.estado-en-proceso{
    background:#66b3ff;
    color:white;
}
.estado-en-revisión{
    background:#ffcc66;
}
.estado-pre-aprobado{
    background:#a3d977;
}
.estado-aprobado{
    background:#4caf50;
    color:white;
}
.estado-finalizado{
    background:#9e66ff;
    color:white;
}

</style>
<script>
  (function () {
    const btn = document.getElementById('btnRegresar');
    if (!btn) return;

    btn.addEventListener('click', function (e) {
      // evita dobles clicks o clicks “comidos”
      btn.style.pointerEvents = 'none';
      btn.style.opacity = '0.8';

      // navegación segura (aunque haya handlers raros)
      window.location.assign(btn.getAttribute('href'));
      e.preventDefault();
    }, { capture: true });
  })();
</script>
<script>
document.addEventListener('click', function(e) {
  const btn = document.querySelector('#btnRegresar');
  if (!btn) return;

  // Solo cuando intentas clickear el botón
  if (e.target === btn || btn.contains(e.target)) {
    const r = btn.getBoundingClientRect();
    const x = r.left + r.width / 2;
    const y = r.top + r.height / 2;

    const topEl = document.elementFromPoint(x, y);

    console.log('CLICK target:', e.target);
    console.log('Elemento arriba del botón:', topEl);
    console.log('defaultPrevented:', e.defaultPrevented);

    // resalta quién está arriba
    if (topEl) {
      topEl.style.outline = '3px solid red';
      topEl.style.outlineOffset = '2px';
    }
  }
}, true);
</script>


@endsection
