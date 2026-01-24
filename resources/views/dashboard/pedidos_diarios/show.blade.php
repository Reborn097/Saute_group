@extends('layouts.dashboard')

@section('titulo', 'Detalle de Pedido Diario')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pedidos-diarios.css') }}">
@endpush

@section('contenido')

@php
    $role = auth()->user()->role ?? '';

    $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos'], true);
    $esCEO = ($role === 'ceo');

    $estadoRaw = trim((string)($pedido->estado ?? ''));

    // acciones admin
    $puedePreaprobar     = $esAdminPedidos && in_array($estadoRaw, ['Visto', 'En revision', 'En revisión'], true);
    $puedeRegresarAVisto = $esAdminPedidos && ($estadoRaw === 'Preaprobado');
    $puedeRechazar       = $esAdminPedidos && in_array($estadoRaw, ['Visto', 'En revision', 'En revisión', 'Preaprobado'], true);

    // acciones ceo
    $puedeCeoAprobar  = $esCEO && ($estadoRaw === 'Preaprobado');
    $puedeCeoRevision = $esCEO && ($estadoRaw === 'Preaprobado');

    // badge estado (reusa tu esquema)
    $estadoNorm = mb_strtolower($estadoRaw);
    $badgeClass = 'pd-badge-revision';
    if ($estadoNorm === 'pendiente') $badgeClass = 'pd-badge-pendiente';
    elseif ($estadoNorm === 'visto') $badgeClass = 'pd-badge-visto';
    elseif (in_array($estadoNorm, ['en revision','en revisión'], true)) $badgeClass = 'pd-badge-revision';
    elseif ($estadoNorm === 'preaprobado') $badgeClass = 'pd-badge-preaprobado';
    elseif ($estadoNorm === 'aprobado') $badgeClass = 'pd-badge-aprobado';
    elseif ($estadoNorm === 'rechazado') $badgeClass = 'pd-badge-rechazado';
@endphp

<div class="contenedor pd-contenedor-show">

    {{-- =========================
        BARRA SUPERIOR (SHOW)
    ========================= --}}
    <div class="pd-acciones-superior">
        <div class="pd-acciones-left">
            <button class="btn-menu" type="button"
                onclick="window.location.href='{{ route('dashboard.pedidos_diarios.index') }}'">
                ← Volver
            </button>
        </div>

        <div class="pd-acciones-right pd-acciones-right-wrap">

            @if($puedePreaprobar)
                <form method="POST" action="{{ route('dashboard.pedidos_diarios.preaprobar', $pedido->id) }}">
                    @csrf
                    <button type="submit" class="pd-btn-accion pd-btn-preaprobar">Preaprobar</button>
                </form>
            @endif

            @if($puedeRegresarAVisto)
                <button type="button" class="pd-btn-accion pd-btn-regresar" onclick="abrirModalRegresarAVisto()">
                    Regresar a Visto
                </button>
            @endif

            @if($puedeRechazar)
                <button type="button" class="pd-btn-accion pd-btn-rechazar" onclick="abrirModalRechazo()">
                    Rechazar
                </button>
            @endif

            @if($puedeCeoAprobar)
                <form method="POST" action="{{ route('dashboard.pedidos_diarios.ceo.aprobar', $pedido->id) }}">
                    @csrf
                    <button type="submit" class="pd-btn-accion pd-btn-aprobar">Aprobar</button>
                </form>
            @endif

            @if($puedeCeoRevision)
                <button type="button" class="pd-btn-accion pd-btn-revision" onclick="abrirModalRevision()">
                    Mandar a revisión
                </button>
            @endif

            <button class="pd-btn-accion pd-btn-pdf" type="button"
                onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pdf', $pedido->id) }}'">
                Descargar PDF
            </button>
        </div>
    </div>

    {{-- =========================
        FLASH
    ========================= --}}
    @if(session('success'))
        <div class="pd-alerta pd-alerta-ok">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="pd-alerta pd-alerta-warn">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="pd-alerta pd-alerta-err">{{ session('error') }}</div>
    @endif

    {{-- =========================
        DATOS GENERALES
    ========================= --}}
    <div class="pd-info-grid">
        <div class="pd-info-item"><strong>Código:</strong> {{ $pedido->codigo ?? '—' }}</div>
        <div class="pd-info-item"><strong>Tipo:</strong> {{ strtoupper($pedido->tipo) }}</div>
        <div class="pd-info-item"><strong>Unidad operativa:</strong> {{ $pedido->unidadOperativa->nombre ?? 'N/A' }}</div>

        <div class="pd-info-item">
            <strong>Semana:</strong>
            {{ \Carbon\Carbon::parse($pedido->semana_inicio)->format('d/m/Y') }}
            –
            {{ \Carbon\Carbon::parse($pedido->semana_fin)->format('d/m/Y') }}
        </div>

        <div class="pd-info-item">
            <strong>Estado:</strong>
            <span class="pd-badge {{ $badgeClass }}">{{ $estadoRaw ?: '—' }}</span>
        </div>

        <div class="pd-info-item"><strong>Creado por:</strong> {{ $pedido->usuario->name ?? 'N/A' }}</div>
        <div class="pd-info-item"><strong>Fecha creación:</strong> {{ optional($pedido->created_at)->format('d/m/Y H:i') ?? '—' }}</div>

        <div class="pd-info-wide">
            <strong>Observaciones:</strong>
            <div class="pd-obs-box">{{ $pedido->observaciones ?: '—' }}</div>
        </div>
    </div>

    {{-- =========================
        TABLA DETALLE
    ========================= --}}
    <h3 class="pd-titulo">Detalle del pedido</h3>

    <div class="pd-tabla-contenedor pd-tabla-detalle-scroll">
        <table class="pd-tabla pd-tabla-detalle">
            <thead>
                <tr>
                    <th class="pd-th-left">Producto</th>
                    @foreach($days as $d)
                        <th>{{ \Carbon\Carbon::parse($d)->format('D d') }}</th>
                    @endforeach
                    <th>Total</th>
                    <th>Precio unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>
                @php $totalGeneral = 0; @endphp

                @foreach($productos as $producto)
                    @php
                        $totalProducto = 0;
                        $precio = $precios[$producto->id] ?? 0;
                    @endphp

                    <tr>
                        <td class="pd-td-left">
                            <strong>{{ $producto->nombre }}</strong><br>
                            <small class="pd-muted">{{ $producto->unidad_medida }}</small>
                        </td>

                        @foreach($days as $d)
                            @php
                                $cant = (float)($cantidades[$producto->id][$d] ?? 0);
                                $totalProducto += $cant;
                            @endphp
                            <td>{{ $cant > 0 ? rtrim(rtrim(number_format($cant, 2), '0'), '.') : '—' }}</td>
                        @endforeach

                        @php
                            $sub = $totalProducto * $precio;
                            $totalGeneral += $sub;
                        @endphp

                        <td><strong>{{ rtrim(rtrim(number_format($totalProducto, 2), '0'), '.') }}</strong></td>
                        <td>${{ number_format($precio, 2) }}</td>
                        <td><strong>${{ number_format($sub, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <th colspan="{{ 1 + count($days) + 2 }}" class="pd-text-right">TOTAL GENERAL</th>
                    <th>${{ number_format($totalGeneral, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

{{-- =========================
    MODAL: REGRESAR A VISTO
========================= --}}
<div class="pd-modal" id="modalRegresar" aria-hidden="true">
    <div class="pd-modal-backdrop" onclick="cerrarModalRegresarAVisto()"></div>

    <div class="pd-modal-card">
        <div class="pd-modal-header">
            <div>
                <div class="pd-modal-title">Regresar a Visto</div>
                <div class="pd-modal-sub">Opcional: deja una nota de por qué se regresó.</div>
            </div>
            <button type="button" class="pd-modal-close" onclick="cerrarModalRegresarAVisto()">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.pedidos_diarios.regresarAVisto', $pedido->id) }}">
            @csrf

            <div class="pd-modal-body">
                <label class="pd-lbl">Observación (opcional)</label>
                <textarea name="observacion" rows="3" class="pd-txt"
                    placeholder="Ej: Falta ajustar cantidades del jueves"></textarea>
            </div>

            <div class="pd-modal-footer">
                <button type="button" class="pd-btn-accion pd-btn-sec" onclick="cerrarModalRegresarAVisto()">Cancelar</button>
                <button type="submit" class="pd-btn-accion pd-btn-regresar">Confirmar</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================
    MODAL: RECHAZAR
========================= --}}
<div class="pd-modal" id="modalRechazo" aria-hidden="true">
    <div class="pd-modal-backdrop" onclick="cerrarModalRechazo()"></div>

    <div class="pd-modal-card">
        <div class="pd-modal-header">
            <div>
                <div class="pd-modal-title">Rechazar pedido</div>
                <div class="pd-modal-sub">Este pedido quedará en estado Rechazado.</div>
            </div>
            <button type="button" class="pd-modal-close" onclick="cerrarModalRechazo()">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.pedidos_diarios.rechazar', $pedido->id) }}">
            @csrf

            <div class="pd-modal-body">
                <label class="pd-lbl">Motivo (obligatorio)</label>
                <textarea name="observacion" rows="4" class="pd-txt" required minlength="3"
                    placeholder="Ej: Cantidades no justificadas / falta evidencia / no procede"></textarea>
            </div>

            <div class="pd-modal-footer">
                <button type="button" class="pd-btn-accion pd-btn-sec" onclick="cerrarModalRechazo()">Cancelar</button>
                <button type="submit" class="pd-btn-accion pd-btn-rechazar">Rechazar</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================
    MODAL: CEO ENVIAR A REVISIÓN
========================= --}}
<div class="pd-modal" id="modalRevision" aria-hidden="true">
    <div class="pd-modal-backdrop" onclick="cerrarModalRevision()"></div>

    <div class="pd-modal-card">
        <div class="pd-modal-header">
            <div>
                <div class="pd-modal-title">Mandar a revisión</div>
                <div class="pd-modal-sub">El pedido pasará a estado "En revision".</div>
            </div>
            <button type="button" class="pd-modal-close" onclick="cerrarModalRevision()">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.pedidos_diarios.ceo.revision', $pedido->id) }}">
            @csrf

            <div class="pd-modal-body">
                <label class="pd-lbl">Observación (obligatoria)</label>
                <textarea name="observacion" rows="4" class="pd-txt" required minlength="3"
                    placeholder="Ej: Ajustar cantidades de martes y justificar proveedor"></textarea>
            </div>

            <div class="pd-modal-footer">
                <button type="button" class="pd-btn-accion pd-btn-sec" onclick="cerrarModalRevision()">Cancelar</button>
                <button type="submit" class="pd-btn-accion pd-btn-revision">Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalRegresarAVisto(){
    const m = document.getElementById('modalRegresar');
    if(!m) return;
    m.classList.add('show');
    m.setAttribute('aria-hidden','false');
    setTimeout(() => {
        const t = m.querySelector('textarea');
        if(t) t.focus();
    }, 60);
}
function cerrarModalRegresarAVisto(){
    const m = document.getElementById('modalRegresar');
    if(!m) return;
    m.classList.remove('show');
    m.setAttribute('aria-hidden','true');
}

function abrirModalRechazo(){
    const m = document.getElementById('modalRechazo');
    if(!m) return;
    m.classList.add('show');
    m.setAttribute('aria-hidden','false');
    setTimeout(() => {
        const t = m.querySelector('textarea');
        if(t) t.focus();
    }, 60);
}
function cerrarModalRechazo(){
    const m = document.getElementById('modalRechazo');
    if(!m) return;
    m.classList.remove('show');
    m.setAttribute('aria-hidden','true');
}

function abrirModalRevision(){
    const m = document.getElementById('modalRevision');
    if(!m) return;
    m.classList.add('show');
    m.setAttribute('aria-hidden','false');
    setTimeout(() => {
        const t = m.querySelector('textarea');
        if(t) t.focus();
    }, 60);
}
function cerrarModalRevision(){
    const m = document.getElementById('modalRevision');
    if(!m) return;
    m.classList.remove('show');
    m.setAttribute('aria-hidden','true');
}

// evitar BFCache
window.addEventListener('pageshow', function (event) {
  if (event.persisted) window.location.reload();
});
</script>

@endsection
