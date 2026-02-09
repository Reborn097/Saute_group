@extends('layouts.dashboard')

@section('titulo', 'Detalle del Pedido')

@section('contenido')

@php
    $role = strtolower(trim(auth()->user()->role ?? ''));

    $esAdmin = ($role === 'admin');
    $esCeo   = ($role === 'ceo');

    // ✅ Admin y CEO pueden: aprobar, mandar a revisión, cancelar
    $puedeAccionesCeoAdmin = ($esAdmin || $esCeo);

    // Normaliza estado para comparaciones (sin romper lo que guardas en BD)
    $estadoRaw = (string)($pedido->estado ?? '');
    $estado = strtolower(trim($estadoRaw)); // ej: "Preaprobado" -> "preaprobado"
@endphp

<div class="contenedor">

    <a id="btnRegresar" class="btn-menu" href="{{ route('dashboard.pedidos.admin') }}">
        Regresar
    </a>

    <h2>Detalle del pedido #{{ $pedido->codigo }}</h2>

    <p><strong>Fecha solicitud:</strong> {{ $pedido->fecha_solicitud }}</p>
    <p><strong>Fecha entrega:</strong> {{ $pedido->fecha_entrega }}</p>
    <p><strong>Total:</strong> ${{ number_format($pedido->total, 2) }}</p>

    <p><strong>Estado actual:</strong>
        <span class="badge estado-{{ strtolower(str_replace(' ', '-', trim($estadoRaw))) }}">
            {{ trim($estadoRaw) }}
        </span>
    </p>

    {{-- Alertas --}}
    @if(session('success')) <div class="alert ok">{{ session('success') }}</div> @endif
    @if(session('warning')) <div class="alert warn">{{ session('warning') }}</div> @endif
    @if(session('error')) <div class="alert err">{{ session('error') }}</div> @endif

    {{-- =========================
        ACCIONES (Admin/CEO)
        - Aprobar (Aprobado)
        - Rechazar para revisión (Visto)
        - Cancelar (Cancelado)
        * Solo admin puede descancelar (NO se muestra aquí a CEO)
    ========================== --}}
    @if($puedeAccionesCeoAdmin)
        <div class="box-acciones">
            <h3 class="subtitulo">Acciones del pedido</h3>

            <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}" class="acciones-form">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

                <div class="acciones-row">

                    {{-- ✅ Aprobar / Revisión: solo si está PREAPROBADO --}}
                    @if($estado === 'preaprobado')
                        <button type="submit" name="estado" value="Aprobado" class="btn-accion btn-verde">
                            Aprobar
                        </button>

                        <button type="submit" name="estado" value="Visto" class="btn-accion btn-ambar">
                            Rechazar para revisión
                        </button>
                    @else
                        <button type="button" class="btn-accion btn-verde disabled" disabled
                            title="Solo disponible cuando el pedido está en Preaprobado">
                            Aprobar
                        </button>

                        <button type="button" class="btn-accion btn-ambar disabled" disabled
                            title="Solo disponible cuando el pedido está en Preaprobado">
                            Rechazar para revisión
                        </button>
                    @endif

                    {{-- ✅ Cancelar: admin/ceo (si NO está cancelado) --}}
                    @if($estado !== 'cancelado')
                        <button type="submit" name="estado" value="Cancelado" class="btn-accion btn-rojo"
                            onclick="return window.sauteConfirmAction(event, '¿Seguro que deseas CANCELAR este pedido?');">
                            Cancelar
                        </button>
                    @else
                        <button type="button" class="btn-accion btn-rojo disabled" disabled>
                            Cancelado
                        </button>
                    @endif

                    {{-- ✅ Quitar cancelación: SOLO ADMIN (y solo si está cancelado) --}}
                    @if($esAdmin)
                        @if($estado === 'cancelado')
                            <button type="submit" name="estado" value="Preaprobado" class="btn-accion btn-gris">
                                Quitar cancelación
                            </button>
                        @else
                            <button type="button" class="btn-accion btn-gris disabled" disabled
                                title="Solo disponible cuando el pedido está Cancelado">
                                Quitar cancelación
                            </button>
                        @endif
                    @endif

                </div>

                <p class="nota">
                    * Aprobar/Rechazar solo aplica cuando está <b>Preaprobado</b>.
                </p>
            </form>
        </div>
    @endif

    <br>

    <h3>Productos</h3>

    <table class="tabla">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Marca</th>
                <th>Descripción - Contenido</th>
                <th>Unidad contenido</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>
            @foreach($pedido->detalles as $d)
            @php
                $pres = $d->presentacion ?? null;
                $pp = $d->productoProveedor ?? null;
                $prod = $pres?->producto ?? $pp?->producto ?? null;
                $descPresenta = $pres?->descripcion ?? '';
                $contenido = $pres?->contenido ?? null;
                $descContenido = $descPresenta ?: '—';
                if ($contenido !== null && $contenido !== '') {
                    $descContenido = trim($descPresenta) . ' - ' . $contenido;
                }
                $unidadContenido = $pres?->unidad_contenido ?? ($pres?->unidad_base ?? '');
            @endphp
            <tr>
                <td>{{ $prod?->nombre ?? '—' }}</td>
                <td>{{ $prod?->marca ?? '—' }}</td>
                <td>{{ $descContenido }}</td>
                <td>{{ $unidadContenido ?: '—' }}</td>
                <td>{{ $d->cantidad_solicitada }}</td>
                <td>${{ number_format($d->precio_unitario, 2) }}</td>
                <td>${{ number_format($d->cantidad_solicitada * $d->precio_unitario, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

.alert{ padding:10px 12px; border-radius:10px; margin:10px 0; font-weight:600; }
.alert.ok{ background:#e9f7ec; border:1px solid #bfe8c8; color:#1f7a36; }
.alert.warn{ background:#fff5da; border:1px solid #f1d38a; color:#8a5a00; }
.alert.err{ background:#fde2e2; border:1px solid #f3a8a8; color:#9b1c1c; }

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

.btn-menu{
    display:inline-block;
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 15px;
    border-radius:8px;
    cursor:pointer;
    margin-bottom:20px;
    text-decoration:none;
}
.btn-menu:hover{ background:#941c1c; }

.badge{
    padding:5px 12px;
    border-radius:15px;
    font-size:13px;
    font-weight:600;
    display:inline-block;
}

/* estados (tu controller) */
.estado-pendiente{ background:#ffe08a; }
.estado-visto{ background:#66b3ff; color:white; }
.estado-preaprobado{ background:#a3d977; }
.estado-aprobado{ background:#4caf50; color:white; }
.estado-cancelado{ background:#d9534f; color:white; }

.box-acciones{
    background:#fff;
    border:1px solid #f0d6bf;
    border-radius:12px;
    padding:14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.subtitulo{
    margin:0 0 10px;
    color:#a13c2f;
    font-family:'Poppins', sans-serif;
}
.acciones-row{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:center;
}
.btn-accion{
    border:none;
    padding:10px 14px;
    border-radius:10px;
    cursor:pointer;
    font-weight:700;
}
.btn-verde{ background:#2e7d32; color:#fff; }
.btn-ambar{ background:#c98700; color:#fff; }
.btn-rojo{  background:#b22b27; color:#fff; }
.btn-gris{  background:#6b7280; color:#fff; }
.btn-accion:hover{ filter: brightness(0.95); }
.disabled{ opacity:.45 !important; cursor:not-allowed !important; filter:none !important; }

.nota{ margin:10px 0 0; font-size:13px; color:#6b2b23; }
</style>

<script>
(function () {
  const btn = document.getElementById('btnRegresar');
  if (!btn) return;

  btn.addEventListener('click', function (e) {
    btn.style.pointerEvents = 'none';
    btn.style.opacity = '0.85';
    window.location.assign(btn.getAttribute('href'));
    e.preventDefault();
  }, { capture: true });
})();
</script>

@endsection
