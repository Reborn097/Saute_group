@extends('layouts.dashboard')

@section('titulo', 'Detalle Informativo del Pedido')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';
    $esAdminPedidos = in_array($role, ['admin','encargado_pedidos','ceo'], true);

    $estado = $pedido->estado;

    // ========= Normalización de detalles =========
    $rows = [];

    foreach(($pedido->detalles ?? []) as $detalle){
        $pres = $detalle->presentacion ?? null;
        $pp = $detalle->productoProveedor ?? null;

        $producto  = $pres?->producto ?? $pp?->producto ?? null;
        $provRel = $pres?->proveedores ?? collect();
        $provSel = $provRel->firstWhere('id', (int)($detalle->producto_proveedor_id ?? 0));
        $primProv = $provSel ?: $provRel->first();
        $proveedor = $primProv?->proveedor ?? $pp?->proveedor ?? null;

        $provNombre     = $proveedor->nombre ?? 'Sin proveedor';
        $productoNombre = $producto->nombre ?? '-';

        $marca = $producto->marca ?? '-';

        $descPresenta = $pres?->descripcion ?? '';
        $contenido = $pres?->contenido ?? null;
        $unidadContenido = $pres?->unidad_contenido ?? ($pres?->unidad_base ?? '');
        $valorMedida = null;
        $unidadMedida = null;

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

        $presentacion = trim((string)$descPresenta);
        if ($presentacion === '') {
            $presentacion = '—';
        }

        $contenidoTexto = '—';
        if($contenido !== null && $contenido !== ''){
            $contenidoTexto = trim($contenido . ' ' . ($unidadContenido ?: ''));
        }elseif($unidadContenido){
            $contenidoTexto = $unidadContenido;
        }elseif($valorMedida !== null && $valorMedida !== ''){
            $contenidoTexto = trim($valorMedida . ' ' . ($unidadMedida ?: ''));
        }

        $descContenido = $descPresenta ?: '—';
        if($contenido !== null && $contenido !== ''){
            $descContenido = trim($descPresenta) . ' - ' . $contenido;
        }

        $sol = (float)($detalle->cantidad_solicitada ?? 0);
        $apr = $detalle->cantidad_aprobada;
        $apr = ($apr === null ? $sol : (float)$apr);

        $activo = $detalle->activo;
        $activo = ($activo === null ? 1 : (int)$activo);

        $rows[] = [
            'prov'                 => $provNombre,
            'contenido'            => $contenidoTexto,
            'presentacion'         => $presentacion,
            'producto'             => $productoNombre,
            'marca'                => $marca,
            'descripcion_contenido'=> $descContenido,
            'unidad_contenido'     => $unidadContenido,
            'apr'                  => $apr,
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
        }
        ksort($aprobadosPorProveedor);
    }else{
        foreach($aprobados as $r){
            $aprobadosFlat[] = $r;
        }
    }
@endphp

<div class="contenedor">
    <h2>Detalle informativo del pedido #{{ $pedido->codigo }}</h2>

    <div class="info-pedido info-grid">
        <p><b>Fecha de solicitud:</b> {{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</p>
        <p><b>Fecha de entrega:</b> {{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('d/m/Y') }}</p>
        <p><b>Usuario:</b> {{ $pedido->usuario->name ?? '-' }}</p>
        <p><b>Estado:</b> <span class="badge">{{ $estado }}</span></p>
    </div>

    <h3 class="titulo-seccion">Productos</h3>

    <div class="tabla-contenedor">
        <table class="tabla-pedidos">
            @if($esAdminPedidos)
                <thead>
                    <tr>
                        <th>Cantidad (aprobada)</th>
                        <th>Contenido</th>
                        <th>Presentacion</th>
                        <th>Producto</th>
                        <th>Marca</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($aprobadosPorProveedor))
                        <tr><td colspan="5" class="text-center">No hay productos aprobados.</td></tr>
                    @else
                        @foreach($aprobadosPorProveedor as $prov => $items)
                            <tr class="prov-row">
                                <td colspan="5"><span class="prov-title">Proveedor: {{ $prov }}</span></td>
                            </tr>

                            @foreach($items as $it)
                                <tr>
                                    <td>{{ rtrim(rtrim(number_format((float)$it['apr'], 2), '0'), '.') }}</td>
                                    <td>{{ $it['contenido'] }}</td>
                                    <td class="t-left">{{ $it['presentacion'] }}</td>
                                    <td class="t-left">{{ $it['producto'] }}</td>
                                    <td class="t-left">{{ $it['marca'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            @else
                <thead>
                    <tr>
                        <th>Cantidad (aprobada)</th>
                        <th>Contenido</th>
                        <th>Presentacion</th>
                        <th>Producto</th>
                        <th>Marca</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($aprobadosFlat))
                        <tr><td colspan="5" class="text-center">No hay productos aprobados.</td></tr>
                    @else
                        @foreach($aprobadosFlat as $it)
                            <tr>
                                <td>{{ rtrim(rtrim(number_format((float)$it['apr'], 2), '0'), '.') }}</td>
                                <td>{{ $it['contenido'] }}</td>
                                <td class="t-left">{{ $it['presentacion'] }}</td>
                                <td class="t-left">{{ $it['producto'] }}</td>
                                <td class="t-left">{{ $it['marca'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            @endif
        </table>
    </div>

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
}
.badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    background:#ffe08a;
    font-weight:800;
}
.titulo-seccion{
    font-size:1.1em;
    margin-top:10px;
    margin-bottom:8px;
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
}
.tabla-pedidos th{
    background:#b22b27;
    color:white;
    padding:10px;
    text-align:center;
    font-weight:800;
    white-space:nowrap;
}
.tabla-pedidos td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #eee;
}
.text-center{ text-align:center; }
.t-left{ text-align:left; }
.prov-row td{
    background:#fff3e4;
    border-bottom:1px solid #f0d6c7;
    text-align:left;
}
.prov-title{ font-weight:900; color:#6b1818; }
.acciones{ text-align:left; margin-top:22px; }
.btn-menu{
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
.btn-menu:hover{ background-color:#941c1c; }
</style>

@endsection
