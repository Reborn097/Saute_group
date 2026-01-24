@extends('layouts.dashboard')

@section('titulo', 'Detalle de Pedido Diario')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';

    // ✅ roles correctos
    $esAdminPedidos = in_array($role, ['admin', 'encargado_pedidos'], true);
    $esCEO = ($role === 'ceo');

    $estadoRaw = trim((string)($pedido->estado ?? ''));

    // ✅ acciones admin
    $puedePreaprobar     = $esAdminPedidos && in_array($estadoRaw, ['Visto', 'En revision'], true);
    $puedeRegresarAVisto = $esAdminPedidos && ($estadoRaw === 'Preaprobado');
    $puedeRechazar       = $esAdminPedidos && in_array($estadoRaw, ['Visto', 'En revision', 'Preaprobado'], true);

    // ✅ acciones ceo
    $puedeCeoAprobar  = $esCEO && ($estadoRaw === 'Preaprobado');
    $puedeCeoRevision = $esCEO && ($estadoRaw === 'Preaprobado');
@endphp

<div class="contenedor">

    {{-- ============================
        BARRA SUPERIOR
    ============================= --}}
    <div class="acciones-superior">
        {{-- ✅ NO history.back() (evita n+1 clics) --}}
        <button class="btn-menu" type="button"
            onclick="window.location.href='{{ route('dashboard.pedidos_diarios.index') }}'">
            ← Volver
        </button>

        <div class="acciones-right">

            @if($puedePreaprobar)
                <form method="POST" action="{{ route('dashboard.pedidos_diarios.preaprobar', $pedido->id) }}">
                    @csrf
                    <button type="submit" class="btn-accion btn-preaprobar">Preaprobar</button>
                </form>
            @endif

            @if($puedeRegresarAVisto)
                <button type="button" class="btn-accion btn-regresar" onclick="abrirModalRegresarAVisto()">
                    Regresar a Visto
                </button>
            @endif

            @if($puedeRechazar)
                <button type="button" class="btn-accion btn-rechazar" onclick="abrirModalRechazo()">
                    Rechazar
                </button>
            @endif

            @if($puedeCeoAprobar)
                <form method="POST" action="{{ route('dashboard.pedidos_diarios.ceo.aprobar', $pedido->id) }}">
                    @csrf
                    <button type="submit" class="btn-accion btn-aprobar">Aprobar</button>
                </form>
            @endif

            @if($puedeCeoRevision)
                <button type="button" class="btn-accion btn-revision" onclick="abrirModalRevision()">
                    Mandar a revisión
                </button>
            @endif

            <button class="btn-accion btn-pdf" type="button"
                onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pdf', $pedido->id) }}'">
                Descargar PDF
            </button>
        </div>
    </div>

    {{-- ============================
        MENSAJES FLASH
    ============================= --}}
    @if(session('success'))
        <div class="alert alert-ok">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warn">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-err">{{ session('error') }}</div>
    @endif

    {{-- ============================
        DATOS GENERALES
    ============================= --}}
    <div class="info-grid">
        <div><strong>Código:</strong> {{ $pedido->codigo ?? '—' }}</div>
        <div><strong>Tipo:</strong> {{ strtoupper($pedido->tipo) }}</div>
        <div><strong>Unidad operativa:</strong> {{ $pedido->unidadOperativa->nombre ?? 'N/A' }}</div>

        <div><strong>Semana:</strong>
            {{ \Carbon\Carbon::parse($pedido->semana_inicio)->format('d/m/Y') }}
            –
            {{ \Carbon\Carbon::parse($pedido->semana_fin)->format('d/m/Y') }}
        </div>

        <div><strong>Estado:</strong>
            @php
                $estadoNorm = mb_strtolower($estadoRaw);
                $estadoClass = match(true) {
                    $estadoNorm === 'pendiente' => 'estado-pendiente',
                    $estadoNorm === 'visto' => 'estado-visto',
                    $estadoNorm === 'en revision' || $estadoNorm === 'en revisión' => 'estado-revision',
                    $estadoNorm === 'preaprobado' => 'estado-preaprobado',
                    $estadoNorm === 'aprobado' => 'estado-aprobado',
                    $estadoNorm === 'rechazado' => 'estado-rechazado',
                    default => 'estado-otro',
                };
            @endphp
            <span class="estado {{ $estadoClass }}">{{ $estadoRaw ?: '—' }}</span>
        </div>

        <div><strong>Creado por:</strong> {{ $pedido->usuario->name ?? 'N/A' }}</div>
        <div><strong>Fecha creación:</strong> {{ optional($pedido->created_at)->format('d/m/Y H:i') ?? '—' }}</div>

        <div class="grid-wide">
            <strong>Observaciones:</strong>
            <div class="obs-box">
                {{ $pedido->observaciones ?: '—' }}
            </div>
        </div>
    </div>

    {{-- ============================
        TABLA DETALLE
    ============================= --}}
    <h3 class="titulo-seccion">Detalle del pedido</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th class="th-left">Producto</th>
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
                        <td class="td-left">
                            <strong>{{ $producto->nombre }}</strong><br>
                            <small>{{ $producto->unidad_medida }}</small>
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
                    <th colspan="{{ 1 + count($days) + 2 }}" style="text-align:right;">TOTAL GENERAL</th>
                    <th>${{ number_format($totalGeneral, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

{{-- ============================
    MODAL: REGRESAR A VISTO (ADMIN)
============================= --}}
<div class="modal" id="modalRegresar" aria-hidden="true">
    <div class="modal-backdrop" onclick="cerrarModalRegresarAVisto()"></div>

    <div class="modal-card">
        <div class="modal-header">
            <div>
                <div class="modal-title">Regresar a Visto</div>
                <div class="modal-sub">Opcional: deja una nota de por qué se regresó.</div>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModalRegresarAVisto()">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.pedidos_diarios.regresarAVisto', $pedido->id) }}">
            @csrf

            <div class="modal-body">
                <label class="lbl">Observación (opcional)</label>
                <textarea name="observacion" rows="3" class="txt"
                    placeholder="Ej: Falta ajustar cantidades del jueves"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-accion btn-sec" onclick="cerrarModalRegresarAVisto()">Cancelar</button>
                <button type="submit" class="btn-accion btn-regresar">Confirmar</button>
            </div>
        </form>
    </div>
</div>

{{-- ============================
    MODAL: RECHAZAR (ADMIN)
============================= --}}
<div class="modal" id="modalRechazo" aria-hidden="true">
    <div class="modal-backdrop" onclick="cerrarModalRechazo()"></div>

    <div class="modal-card">
        <div class="modal-header">
            <div>
                <div class="modal-title">Rechazar pedido</div>
                <div class="modal-sub">Este pedido quedará en estado Rechazado.</div>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModalRechazo()">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.pedidos_diarios.rechazar', $pedido->id) }}">
            @csrf

            <div class="modal-body">
                <label class="lbl">Motivo (obligatorio)</label>
                <textarea name="observacion" rows="4" class="txt" required minlength="3"
                    placeholder="Ej: Cantidades no justificadas / falta evidencia / no procede"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-accion btn-sec" onclick="cerrarModalRechazo()">Cancelar</button>
                <button type="submit" class="btn-accion btn-rechazar">Rechazar</button>
            </div>
        </form>
    </div>
</div>

{{-- ============================
    MODAL: CEO ENVIAR A REVISIÓN
============================= --}}
<div class="modal" id="modalRevision" aria-hidden="true">
    <div class="modal-backdrop" onclick="cerrarModalRevision()"></div>

    <div class="modal-card">
        <div class="modal-header">
            <div>
                <div class="modal-title">Mandar a revisión</div>
                <div class="modal-sub">El pedido pasará a estado "En revision".</div>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModalRevision()">✕</button>
        </div>

        <form method="POST" action="{{ route('dashboard.pedidos_diarios.ceo.revision', $pedido->id) }}">
            @csrf

            <div class="modal-body">
                <label class="lbl">Observación (obligatoria)</label>
                <textarea name="observacion" rows="4" class="txt" required minlength="3"
                    placeholder="Ej: Ajustar cantidades de martes y justificar proveedor"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-accion btn-sec" onclick="cerrarModalRevision()">Cancelar</button>
                <button type="submit" class="btn-accion btn-revision">Enviar</button>
            </div>
        </form>
    </div>
</div>

{{-- ============================
        ESTILOS
============================= --}}
<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1200px;
    margin:auto;
}

.acciones-superior{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
    flex-wrap:wrap;
}

.acciones-right{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
}

.btn-menu{
    background:#999;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu:hover{ background:#777; }

.btn-accion{
    border:none;
    padding:8px 13px;
    border-radius:8px;
    cursor:pointer;
    color:white;
    font-weight:700;
    font-size:14px;
}

.btn-preaprobar{ background:#b22b27; }
.btn-preaprobar:hover{ background:#941c1c; }

.btn-aprobar{ background:#4caf50; }
.btn-aprobar:hover{ opacity:.9; }

.btn-revision{ background:#ff9800; }
.btn-revision:hover{ opacity:.9; }

.btn-regresar{ background:#2f80ed; }
.btn-regresar:hover{ opacity:.9; }

.btn-rechazar{ background:#d9534f; }
.btn-rechazar:hover{ opacity:.9; }

.btn-pdf{ background:#555; }
.btn-pdf:hover{ opacity:.9; }

.btn-sec{
    background:#777;
}
.btn-sec:hover{ opacity:.9; }

/* Alerts */
.alert{
    padding:10px 12px;
    border-radius:10px;
    margin:10px 0 14px;
    font-weight:600;
}
.alert-ok{ background:#e7f7ea; border:1px solid #bfe8c8; color:#1f5f2c; }
.alert-warn{ background:#fff6e3; border:1px solid #ffe0a3; color:#6a4a00; }
.alert-err{ background:#ffecec; border:1px solid #f5b5b5; color:#7a1111; }

.info-grid{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:12px;
    margin-bottom:20px;
    font-size:15px;
}

.grid-wide{
    grid-column: span 3;
}

.obs-box{
    margin-top:6px;
    background:#fff;
    border:1px solid #eee;
    border-radius:10px;
    padding:10px 12px;
}

.titulo-seccion{
    margin:18px 0 10px;
    font-size:20px;
    font-weight:700;
}

.tabla-contenedor{
    overflow:auto;
    border-radius:10px;
}

.tabla{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    min-width:900px;
}

.tabla th{
    background:#b22b27;
    color:white;
    padding:10px;
    text-align:center;
}

.tabla td{
    padding:9px;
    text-align:center;
    border-bottom:1px solid #eee;
}

.tabla tr:hover td{
    background:#f5d6d6;
}

.th-left, .td-left{ text-align:left; }

/* Estado badge */
.estado{
    padding:4px 10px;
    border-radius:8px;
    font-weight:800;
    font-size:13px;
    display:inline-block;
}
.estado-pendiente{ background:#ffe08a; color:#5c3d00; }
.estado-visto{ background:#66b3ff; color:white; }
.estado-revision{ background:#ffcc66; color:#5c3d00; }
.estado-preaprobado{ background:#a3d977; color:#244a00; }
.estado-aprobado{ background:#4caf50; color:white; }
.estado-rechazado{ background:#d9534f; color:white; }
.estado-otro{ background:#999; color:white; }

/* Modal */
.modal{
    position:fixed;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
}
.modal.show{ display:flex; }

.modal-backdrop{
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.35);
}

.modal-card{
    position:relative;
    width:min(560px, 92vw);
    background:#fff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.25);
    z-index:1;
}

.modal-header{
    padding:14px 16px;
    border-bottom:1px solid #eee;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
}

.modal-title{ font-weight:900; font-size:16px; }
.modal-sub{ font-size:12px; opacity:.75; margin-top:2px; }

.modal-close{
    background:transparent;
    border:none;
    font-size:18px;
    cursor:pointer;
    opacity:.75;
}
.modal-close:hover{ opacity:1; }

.modal-body{
    padding:14px 16px;
}

.lbl{
    display:block;
    font-weight:800;
    margin-bottom:6px;
}

.txt{
    width:100%;
    border:1px solid #ccc;
    border-radius:10px;
    padding:10px 12px;
    outline:none;
    font-family:inherit;
    resize:vertical;
}
.txt:focus{
    border-color:#b22b27;
    box-shadow:0 0 0 2px rgba(178,43,39,.15);
}

.modal-footer{
    padding:12px 16px 16px;
    display:flex;
    gap:10px;
    justify-content:flex-end;
    border-top:1px solid #eee;
}

/* Responsive */
@media (max-width: 900px){
    .info-grid{ grid-template-columns:1fr; }
    .grid-wide{ grid-column:auto; }
}
</style>

{{-- ============================
        SCRIPTS
============================= --}}
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

// ✅ Para evitar data vieja cuando se vuelve con BFCache
window.addEventListener('pageshow', function (event) {
  if (event.persisted) window.location.reload();
});
</script>

@endsection
