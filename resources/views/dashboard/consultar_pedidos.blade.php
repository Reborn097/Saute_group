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

                    {{-- ============================
                         BADGES DE ESTADO ACTUAL
                       ============================ --}}
                    <td>
                        @php
                            $estado = strtolower($pedido->estado);
                        @endphp

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
                    <td colspan="6" class="text-center">No hay pedidos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
/* ======== CONTENEDOR GENERAL ======== */
.contenedor {
    background-color: #fceede;
    padding: 25px 35px;
    border-radius: 12px;
    max-width: 1100px;
    margin: 0 auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    font-family: 'Poppins', sans-serif;
}

/* ======== ACCIONES ======== */
.acciones-superior {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 20px;
}
.btn-menu {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}
.btn-menu:hover {
    background-color: #941c1c;
}

/* ======== TABLA ======== */
.tabla {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

.tabla th {
    background-color: #b22b27;
    color: white;
    padding: 12px;
    text-align: center;
    font-weight: 600;
}

.tabla td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

.tabla tr:hover {
    background-color: #f8dcdc;
}

/* ======== BOTÓN ======== */
.btn-ver {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.9em;
    font-weight: 600;
    cursor: pointer;
}
.btn-ver:hover {
    background-color: #941c1c;
}

/* ======== BADGES (ESTADOS) ======== */
.badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: 600;
}

/* Igual que Admin Pedidos */
.estado-pendiente { background:#ffe08a; color:#5c3d00; }
.estado-en-proceso { background:#66b3ff; color:white; }
.estado-pre-aprobado { background:#a3d977; color:#244a00; }
.estado-aprobado { background:#4caf50; color:white; }
.estado-finalizado { background:#9e66ff; color:white; }
.estado-revision { background:#ffcc66; color:#5c3d00; }

/* Texto */
.text-center {
    text-align: center;
    color: #444;
    padding: 15px;
    font-style: italic;
}
</style>

@endsection
