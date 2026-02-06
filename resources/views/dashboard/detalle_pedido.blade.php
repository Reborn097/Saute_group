@extends('layouts.dashboard')

@section('titulo', 'Detalle del Pedido')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    $esAdminPedidos = in_array($role, ['admin','encargado_pedidos','ceo'], true);

    $esAdmin = ($role === 'admin');
    $esCeo   = ($role === 'ceo');

    $estado = $pedido->estado;

    // ========= Normalización de detalles =========
    $rows = [];
    $totalAprobadoGeneral = 0;

    foreach(($pedido->detalles ?? []) as $detalle){
        $pres = $detalle->presentacion ?? null;
        $pp = $detalle->productoProveedor ?? null;

        $producto  = $pres?->producto ?? $pp?->producto ?? null;
        $provRel = $pres?->proveedores ?? collect();
        $primProv = $provRel->first();
        $proveedor = $primProv?->proveedor ?? $pp?->proveedor ?? null;

        $provNombre     = $proveedor->nombre ?? 'Sin proveedor';
        $productoNombre = $producto->nombre ?? '-';

        $marca = $producto->marca ?? '-';

        $descPresenta = $pres?->descripcion ?? '';
        $contenido = $pres?->contenido ?? null;
        $unidadContenido = $pres?->unidad_contenido ?? ($pres?->unidad_base ?? '');

        if(!$pres && $producto){
            $valorMedida  = $producto->valor_medida ?? null;
            $unidadMedida = $producto->unidad_medida ?? ($producto->unidad ?? '');
            $descPresenta = '';
            if($valorMedida !== null && $valorMedida !== '' && $unidadMedida){
                $descPresenta = trim($valorMedida . ' ' . $unidadMedida);
            }elseif($unidadMedida){
                $descPresenta = $unidadMedida;
            }
        }

        $descContenido = $descPresenta ?: '—';
        if($contenido !== null && $contenido !== ''){
            $descContenido = trim($descPresenta) . ' - ' . $contenido;
        }

        $sol = (float)($detalle->cantidad_solicitada ?? 0);
        $apr = $detalle->cantidad_aprobada;
        $apr = ($apr === null ? $sol : (float)$apr);

        $precio = (float)($detalle->precio_unitario ?? 0);

        $activo = $detalle->activo;
        $activo = ($activo === null ? 1 : (int)$activo);

        $subtotal = $detalle->subtotal;
        $subtotal = ($subtotal === null ? ($apr * $precio) : (float)$subtotal);

        $rows[] = [
            'prov'                 => $provNombre,
            'producto'             => $productoNombre,
            'marca'                => $marca,
            'descripcion_contenido'=> $descContenido,
            'unidad_contenido'     => $unidadContenido,
            'sol'                  => $sol,
            'apr'                  => $apr,
            'precio'               => $precio,
            'subtotal'             => $subtotal,
            'activo'               => $activo,
        ];
    }

    $aprobados = array_values(array_filter($rows, function($r){
        return ((int)$r['activo'] === 1) && ((float)$r['apr'] > 0);
    }));

    $aprobadosPorProveedor = [];
    $aprobadosFlat = [];

    if($esAdminPedidos){
        foreach($aprobados as $r){
            $aprobadosPorProveedor[$r['prov']][] = $r;
            $totalAprobadoGeneral += (float)$r['subtotal'];
        }
        ksort($aprobadosPorProveedor);
    }else{
        foreach($aprobados as $r){
            $aprobadosFlat[] = $r;
            $totalAprobadoGeneral += (float)$r['subtotal'];
        }
    }

    $rechazadosPorProveedor = [];
    $aumentosPorProveedor   = [];

    if($esAdminPedidos){
        foreach($rows as $r){
            $sol = (float)$r['sol'];
            $apr = (float)$r['apr'];
            $activo = (int)$r['activo'];

            $esRechazo = ($activo === 0) || ($apr <= 0) || ($apr < $sol);

            if($esRechazo){
                $rechazada = 0;

                if($activo === 0 || $apr <= 0){
                    $rechazada = $sol;
                }else{
                    $rechazada = max(0, $sol - $apr);
                }

                if($rechazada > 0){
                    $rechazadosPorProveedor[$r['prov']][] = [
                        'producto'             => $r['producto'],
                        'marca'                => $r['marca'],
                        'descripcion_contenido'=> $r['descripcion_contenido'],
                        'unidad_contenido'     => $r['unidad_contenido'],
                        'sol'          => $sol,
                        'apr'          => $apr,
                        'rech'         => $rechazada,
                        'precio'       => (float)$r['precio'],
                        'impacto'      => (float)$rechazada * (float)$r['precio'],
                        'badge'        => ($activo === 0 || $apr <= 0) ? 'Desactivado / 0' : 'Reducido',
                    ];
                }
            }

            if($activo === 1 && $apr > $sol){
                $extra = $apr - $sol;
                $aumentosPorProveedor[$r['prov']][] = [
                    'producto'             => $r['producto'],
                    'marca'                => $r['marca'],
                    'descripcion_contenido'=> $r['descripcion_contenido'],
                    'unidad_contenido'     => $r['unidad_contenido'],
                    'sol'          => $sol,
                    'apr'          => $apr,
                    'extra'        => $extra,
                    'precio'       => (float)$r['precio'],
                    'impacto'      => (float)$extra * (float)$r['precio'],
                    'badge'        => 'Aumentado',
                ];
            }
        }

        ksort($rechazadosPorProveedor);
        ksort($aumentosPorProveedor);
    }
@endphp

<div class="contenedor">
    <h2>Detalle del pedido #{{ $pedido->codigo }}</h2>

    <div class="info-pedido info-grid">
        <p><b>Fecha de solicitud:</b> {{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</p>
        <p><b>Fecha de entrega:</b> {{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('d/m/Y') }}</p>
        <p><b>Usuario:</b> {{ $pedido->usuario->name ?? '-' }}</p>
    </div>

    <div class="bloque-flujo">
        <div class="flujo-row">
            <div>
                <b>Estado actual:</b>
                <span class="badge">{{ $estado }}</span>

                @if(!empty($pedido->observaciones))
                    <div class="obs">
                        <b>Observaciones:</b> {{ $pedido->observaciones }}
                    </div>
                @endif
            </div>

            <div class="acciones-flujo">
                {{-- ✅ ADMIN (tal cual tu versión buena) --}}
                @if($esAdmin)
                    <form method="GET" action="{{ route('dashboard.pedidos.admin.editar', $pedido->codigo) }}">
                        <button class="btn btn-sec">Editar pedido</button>
                    </form>

                    @if($estado === 'Pendiente')
                        <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}">
                            @csrf
                            <input type="hidden" name="estado" value="Visto">
                            <button class="btn">Marcar como visto</button>
                        </form>
                    @endif

                    @if(in_array($estado, ['Visto', 'En revisión'], true))
                        <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}">
                            @csrf
                            <input type="hidden" name="estado" value="Preaprobado">
                            <button class="btn">Preaprobar</button>
                        </form>
                    @endif

                    @if($estado === 'Preaprobado')
                        <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}">
                            @csrf
                            <input type="hidden" name="estado" value="Visto">
                            <button class="btn btn-sec">Quitar preaprobación</button>
                        </form>
                    @endif

                    @if(!in_array($estado, ['Aprobado','Cancelado'], true))
                        <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}"
                              onsubmit="return confirm('¿Seguro que quieres cancelar este pedido?');">
                            @csrf
                            <input type="hidden" name="estado" value="Cancelado">
                            <button class="btn btn-black">Cancelar</button>
                        </form>
                    @endif
                @endif

                {{-- ✅ CEO (solo Preaprobado) --}}
                @if($esCeo && $estado === 'Preaprobado')
                    <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}">
                        @csrf
                        <input type="hidden" name="estado" value="Aprobado">
                        <button class="btn">Aprobar</button>
                    </form>

                    <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}"
                          onsubmit="return confirm('¿Mandar este pedido a revisión?');">
                        @csrf
                        <input type="hidden" name="estado" value="Visto">
                        <button class="btn btn-sec">Rechazar para revisión</button>
                    </form>

                    <form method="POST" action="{{ route('dashboard.pedidos.admin.estado', $pedido->codigo) }}"
                          onsubmit="return confirm('¿Seguro que quieres cancelar este pedido?');">
                        @csrf
                        <input type="hidden" name="estado" value="Cancelado">
                        <button class="btn btn-black">Cancelar</button>
                    </form>
                @endif

                <form method="GET" action="{{ route('dashboard.pedidos.detalle.informativo', $pedido->codigo) }}">
                    <button class="btn btn-sec">Ver detalle informativo</button>
                </form>
            </div>
        </div>
    </div>

    {{-- DOCUMENTOS DEL PEDIDO ESPECIAL --}}
    @if(isset($pedidoEspecial) && $pedidoEspecial)
        <h3 class="titulo-seccion">Documentos adjuntos del pedido especial</h3>

        <div class="tabla-contenedor">
            <table class="tabla-pedidos tabla-docs">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Ver</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PDF solicitud</td>
                        <td>
                            @if(!empty($pedidoEspecial->solicitud))
                                <a class="btn btn-mini"
                                   href="{{ route('dashboard.pedidos.especiales.pdf.ver', [$pedido->codigo, 'solicitud']) }}"
                                   target="_blank">Ver PDF</a>
                            @else
                                <span class="muted">No disponible</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td>PDF cotización</td>
                        <td>
                            @if(!empty($pedidoEspecial->cotizacion))
                                <a class="btn btn-mini"
                                   href="{{ route('dashboard.pedidos.especiales.pdf.ver', [$pedido->codigo, 'cotizacion']) }}"
                                   target="_blank">Ver PDF</a>
                            @else
                                <span class="muted">No disponible</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td>PDF autorización</td>
                        <td>
                            @if(!empty($pedidoEspecial->autorizacion))
                                <a class="btn btn-mini"
                                   href="{{ route('dashboard.pedidos.especiales.pdf.ver', [$pedido->codigo, 'autorizacion']) }}"
                                   target="_blank">Ver PDF</a>
                            @else
                                <span class="muted">No disponible</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    {{-- BLOQUE PRINCIPAL: PRODUCTOS --}}
    <h3 class="titulo-seccion">Productos</h3>

    <div class="tabla-contenedor">
        <table class="tabla-pedidos">
            @if($esAdminPedidos)
                <thead>
                    <tr>
                        <th>Cantidad (aprobada)</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Descripción - Contenido</th>
                        <th>Unidad contenido</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($aprobadosPorProveedor))
                        <tr><td colspan="7" class="text-center">No hay productos aprobados.</td></tr>
                    @else
                        @foreach($aprobadosPorProveedor as $prov => $items)
                            @php $totalProv = 0; foreach($items as $it){ $totalProv += (float)$it['subtotal']; } @endphp

                            <tr class="prov-row">
                                <td colspan="7"><span class="prov-title">Proveedor: {{ $prov }}</span></td>
                            </tr>

                            @foreach($items as $it)
                                <tr>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['apr'], 2), '0'), '.') }}</td>
                                    <td class="t-left">{{ $it['producto'] }}</td>
                                    <td class="t-left">{{ $it['marca'] }}</td>
                                    <td class="t-left">{{ $it['descripcion_contenido'] }}</td>
                                    <td>{{ $it['unidad_contenido'] }}</td>
                                    <td>${{ number_format((float)$it['precio'], 2) }}</td>
                                    <td>${{ number_format((float)$it['subtotal'], 2) }}</td>
                                </tr>
                            @endforeach

                            <tr class="total-prov">
                                <td colspan="6" class="t-right"><b>Total proveedor</b></td>
                                <td><b>${{ number_format($totalProv, 2) }}</b></td>
                            </tr>
                        @endforeach

                        <tr class="total-general">
                            <td colspan="6" class="t-right"><b>TOTAL GENERAL</b></td>
                            <td><b>${{ number_format($totalAprobadoGeneral, 2) }}</b></td>
                        </tr>
                    @endif
                </tbody>
            @else
                <thead>
                    <tr>
                        <th>Cantidad (aprobada)</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Descripción - Contenido</th>
                        <th>Unidad contenido</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($aprobadosFlat))
                        <tr><td colspan="7" class="text-center">No hay productos aprobados.</td></tr>
                    @else
                        @foreach($aprobadosFlat as $it)
                            <tr>
                                <td>{{ rtrim(rtrim(number_format((float)$it['apr'], 2), '0'), '.') }}</td>
                                <td class="t-left">{{ $it['producto'] }}</td>
                                <td class="t-left">{{ $it['marca'] }}</td>
                                <td class="t-left">{{ $it['descripcion_contenido'] }}</td>
                                <td>{{ $it['unidad_contenido'] }}</td>
                                <td>${{ number_format((float)$it['precio'], 2) }}</td>
                                <td>${{ number_format((float)$it['subtotal'], 2) }}</td>
                            </tr>
                        @endforeach

                        <tr class="total-general">
                            <td colspan="6" class="t-right"><b>TOTAL GENERAL</b></td>
                            <td><b>${{ number_format($totalAprobadoGeneral, 2) }}</b></td>
                        </tr>
                    @endif
                </tbody>
            @endif
        </table>
    </div>

    {{-- AJUSTES --}}
    @if($esAdminPedidos)
        <h3 class="titulo-seccion">Ajustes vs solicitado (control)</h3>
        <div class="tabla-contenedor">
            <table class="tabla-pedidos">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Descripción - Contenido</th>
                        <th>Unidad contenido</th>
                        <th>Solicitado</th>
                        <th>Aprobado</th>
                        <th>Diferencia</th>
                        <th>Precio unitario</th>
                        <th>Impacto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($rechazadosPorProveedor))
                        <tr><td colspan="10" class="text-center">Sin ajustes por reducción o desactivación.</td></tr>
                    @else
                        @foreach($rechazadosPorProveedor as $prov => $items)
                            <tr class="prov-row">
                                <td colspan="10"><span class="prov-title">Proveedor: {{ $prov }}</span></td>
                            </tr>
                            @foreach($items as $it)
                                <tr>
                                    <td class="t-left">{{ $it['producto'] }}</td>
                                    <td class="t-left">{{ $it['marca'] }}</td>
                                    <td class="t-left">{{ $it['descripcion_contenido'] }}</td>
                                    <td>{{ $it['unidad_contenido'] }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['sol'], 2), '0'), '.') }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['apr'], 2), '0'), '.') }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['rech'], 2), '0'), '.') }}</td>
                                    <td>${{ number_format((float)$it['precio'], 2) }}</td>
                                    <td>${{ number_format((float)$it['impacto'], 2) }}</td>
                                    <td><span class="pill {{ $it['badge'] === 'Aumentado' ? 'pill-ok' : 'pill-neg' }}">{{ $it['badge'] }}</span></td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <h3 class="subtitulo">Aumentos vs solicitado</h3>
        <div class="tabla-contenedor">
            <table class="tabla-pedidos">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Descripción - Contenido</th>
                        <th>Unidad contenido</th>
                        <th>Solicitado</th>
                        <th>Aprobado</th>
                        <th>Extra</th>
                        <th>Precio unitario</th>
                        <th>Impacto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($aumentosPorProveedor))
                        <tr><td colspan="10" class="text-center">Sin aumentos registrados.</td></tr>
                    @else
                        @foreach($aumentosPorProveedor as $prov => $items)
                            <tr class="prov-row">
                                <td colspan="10"><span class="prov-title">Proveedor: {{ $prov }}</span></td>
                            </tr>
                            @foreach($items as $it)
                                <tr>
                                    <td class="t-left">{{ $it['producto'] }}</td>
                                    <td class="t-left">{{ $it['marca'] }}</td>
                                    <td class="t-left">{{ $it['descripcion_contenido'] }}</td>
                                    <td>{{ $it['unidad_contenido'] }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['sol'], 2), '0'), '.') }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['apr'], 2), '0'), '.') }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['extra'], 2), '0'), '.') }}</td>
                                    <td>${{ number_format((float)$it['precio'], 2) }}</td>
                                    <td>${{ number_format((float)$it['impacto'], 2) }}</td>
                                    <td><span class="pill pill-ok">{{ $it['badge'] }}</span></td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    <div class="acciones">
        <a class="btn-menu" href="{{ route('dashboard.pedidos.admin') }}">Regresar</a>
    </div>
</div>

<style>
/* ======== CONTENEDOR GENERAL ======== */
.contenedor{
    background-color:#fae7d0;
    padding:25px 35px;
    border-radius:12px;
    box-shadow:0 4px 8px rgba(0,0,0,0.15);
    max-width:1200px;
    margin:0 auto;
    font-family:'Poppins', sans-serif;
}
.contenedor h2{
    font-size:1.4em;
    font-weight:700;
    margin-bottom:10px;
    color:#6b1818;
}
.info-pedido{
    background-color:#fff8f0;
    padding:10px 15px;
    border-radius:8px;
    margin-bottom:18px;
    line-height:1.6;
}
.info-grid{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px 20px;
    align-items: center;
}
.info-grid p{
    margin: 0;
    background: #ffffff;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 0.95em;
    box-shadow: inset 0 0 0 1px rgba(0,0,0,.05);
}
.titulo-seccion{
    margin-top:22px;
    font-size:1.1em;
    color:#6b1818;
    font-weight:800;
}
.subtitulo{
    margin-top:14px;
    font-size:1.02em;
    color:#6b1818;
    font-weight:800;
    opacity:.95;
}
.bloque-flujo{
    background:#fff8f0;
    padding:12px 15px;
    border-radius:10px;
    margin:12px 0 18px;
    border:1px solid rgba(0,0,0,.08);
}
.flujo-row{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:flex-start;
    justify-content:space-between;
}
.acciones-flujo{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:flex-end;
}
.obs{ margin-top:8px; font-size:.92em; }
.badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    background:#ffe08a;
    font-weight:800;
}
.tabla-contenedor{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    border-radius:10px;
}
.tabla-pedidos{
    width:100%;
    border-collapse:collapse;
    background-color:white;
    border-radius:10px;
    overflow:hidden;
    margin-top:10px;
    box-shadow:0 3px 6px rgba(0,0,0,0.1);
    min-width:980px;
}
.tabla-pedidos th{
    background-color:#b22b27;
    color:white;
    padding:10px;
    text-align:center;
}
.tabla-pedidos td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #ddd;
}
.tabla-pedidos tr:hover{ background-color:#f8dcdc; }

.tabla-docs{ min-width: 520px; }
.tabla-docs td{ padding:6px 10px; }
.tabla-docs .btn-mini{ padding:6px 10px; font-size:.88em; border-radius:6px; }

.text-center{ text-align:center; }
.t-left{ text-align:left; }
.t-right{ text-align:right; }
.muted{ opacity:.7; }

.prov-row td{
    background:#fff3e4;
    border-bottom:1px solid #f0d6c7;
    text-align:left;
}
.prov-title{ font-weight:900; color:#6b1818; }
.total-prov td{ background:#fff8f0; }
.total-general td{ background:#ffe7cf; }

.pill{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-weight:900;
    font-size:.85em;
    border:1px solid rgba(0,0,0,.08);
}
.pill-ok{ background:#dcfce7; color:#166534; }
.pill-warn{ background:#fef3c7; color:#92400e; }
.pill-neg{ background:#fee2e2; color:#991b1b; }

.acciones{ text-align:left; margin-top:22px; }
.btn, .btn-menu{
    background-color:#b22b27;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}
.btn:hover, .btn-menu:hover{ background-color:#941c1c; }
.btn-sec{ background:#666; }
.btn-black{ background:#000; }
.btn-mini{ padding:6px 10px; font-size:.88em; border-radius:6px; }
</style>

@endsection
