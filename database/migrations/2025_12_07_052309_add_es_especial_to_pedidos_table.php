@extends('layouts.dashboard')

@section('titulo', 'Consultar Pedidos')

@section('contenido')
<div class="contenedor">
    <div class="acciones-superior">
        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha solicitud</th>
                <th>Usuario</th>
                <th>Total</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>

        <tbody>
            @forelse($pedidos as $pedido)
                <tr>

                    <td>{{ $pedido->codigo }}</td>

                    <td>{{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</td>

                    <td>{{ $pedido->usuario->name ?? 'Administrador' }}</td>

                    <td>${{ number_format($pedido->total, 2) }}</td>

                    {{-- =====================================
                          ● TIPO DE PEDIDO
                       ===================================== --}}
                    <td>
                        @if($pedido->es_especial)
                            <span class="badge tipo-especial">Especial</span>
                        @else
                            <span class="badge tipo-normal">Normal</span>
                        @endif
                    </td>

                    {{-- =====================================
                          ● ESTADO
                       ===================================== --}}
                    <td>
                        @php $estado = strtolower($pedido->estado); @endphp

                        @switch($estado)
                            @case('pendiente')
                                <span class="badge estado-pendiente">Pendiente</span>
                                @break
                            @case('en proceso')
                                <span class="badge estado-en-proceso">En proceso</span>
                                @break
                            @case('pre-aprobado')
                                <span class="badge estado-pre-aprobado">Pre-aprobado</span>
                                @break
                            @case('aprobado')
                                <span class="badge estado-aprobado">Aprobado</span>
                                @break
                            @case('finalizado')
                                <span class="badge estado-finalizado">Finalizado</span>
                                @break
                            @default
                                <span class="badge estado-revision">En revisión</span>
                        @endswitch
                    </td>

                    <td class="acciones">
                        <button class="btn-ver"
                            onclick="window.location.href='{{ route('dashboard.pedidos.detalle', $pedido->codigo) }}'">
                            Visualizar
                        </button>
                    </td>

                </tr>

            @empty
                <tr>
                    <td colspan="7" class="text-center">No hay pedidos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
/* ===== TIPOS ===== */
.tipo-normal{
    background:#7895a1;
    color:white;
    padding:6px 14px;
    border-radius:12px;
    font-weight:600;
}
.tipo-especial{
    background:#f7c55f;
    color:#5c3d00;
    padding:6px 14px;
    border-radius:12px;
    font-weight:600;
}

/* Estados igual que antes */
.badge{
    display:inline-block;
}
</style>
@endsection
