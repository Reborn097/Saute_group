@extends('layouts.dashboard')

@section('titulo', 'Detalle de Pedido Diario')

@section('contenido')

<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu" onclick="history.back()">← Volver</button>
    </div>

    {{-- ============================
        DATOS GENERALES
    ============================= --}}
    <div class="info-grid">
        <div><strong>Tipo:</strong> {{ strtoupper($pedido->tipo) }}</div>
        <div><strong>Unidad operativa:</strong> {{ $pedido->unidadOperativa->nombre ?? 'N/A' }}</div>
        <div><strong>Semana:</strong>
            {{ \Carbon\Carbon::parse($pedido->semana_inicio)->format('d/m/Y') }}
            –
            {{ \Carbon\Carbon::parse($pedido->semana_fin)->format('d/m/Y') }}
        </div>
        <div><strong>Estado:</strong>
            <span class="estado estado-{{ strtolower($pedido->estado) }}">
                {{ $pedido->estado }}
            </span>
        </div>
        <div><strong>Creado por:</strong> {{ $pedido->usuario->name ?? 'N/A' }}</div>
        <div><strong>Fecha creación:</strong> {{ $pedido->created_at->format('d/m/Y H:i') }}</div>
    </div>

    {{-- ============================
        TABLA DETALLE
    ============================= --}}
    <h3 class="titulo-seccion">Detalle del pedido</h3>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
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
                    @endphp

                    <tr>
                        <td style="text-align:left;">
                            <strong>{{ $producto->nombre }}</strong><br>
                            <small>{{ $producto->unidad_medida }}</small>
                        </td>

                        @foreach($days as $d)
                            @php
                                $cantidad = $cantidades[$producto->id][$d] ?? 0;
                                $totalProducto += $cantidad;
                            @endphp
                            <td>{{ $cantidad > 0 ? $cantidad : '—' }}</td>
                        @endforeach

                        @php
                            $precio = $producto->productoProveedor->precio ?? 0;
                            $subtotal = $totalProducto * $precio;
                            $totalGeneral += $subtotal;
                        @endphp

                        <td><strong>{{ $totalProducto }}</strong></td>
                        <td>${{ number_format($precio, 2) }}</td>
                        <td><strong>${{ number_format($subtotal, 2) }}</strong></td>
                    </tr>
                @endforeach

            </tbody>

            <tfoot>
                <tr>
                    <th colspan="{{ count($days) + 3 }}" style="text-align:right;">TOTAL GENERAL</th>
                    <th>${{ number_format($totalGeneral, 2) }}</th>
                </tr>
            </tfoot>
        </table>
        <button class="btn" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pdf', $pedido->id) }}'">
            Descargar PDF
        </button>

    </div>

    {{-- ============================
        OBSERVACIONES / RECHAZO
    ============================= --}}
    @if($pedido->motivo_rechazo)
        <div class="alerta">
            <strong>Motivo de rechazo:</strong><br>
            {{ $pedido->motivo_rechazo }}
        </div>
    @endif

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
    margin-bottom:15px;
}

.btn-menu{
    background:#999;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:12px;
    margin-bottom:25px;
    font-size:15px;
}

.titulo-seccion{
    margin:25px 0 10px;
    font-size:20px;
    font-weight:700;
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
    padding:10px;
    text-align:center;
}

.tabla td{
    padding:9px;
    text-align:center;
    border-bottom:1px solid #eee;
}

.tabla tr:hover{
    background:#f5d6d6;
}

.estado{
    padding:4px 10px;
    border-radius:6px;
    font-weight:600;
    font-size:13px;
}

.estado-borrador{ background:#999; color:white; }
.estado-preaprobado{ background:#f0ad4e; color:white; }
.estado-aprobado{ background:#5cb85c; color:white; }
.estado-rechazado{ background:#d9534f; color:white; }

.alerta{
    margin-top:20px;
    background:#ffe1e1;
    border-left:5px solid #b22b27;
    padding:14px;
    border-radius:8px;
}
</style>

@endsection
