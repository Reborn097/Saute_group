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
                    <td>
                        @if(strtolower($pedido->estado) === 'en revisión')
                            <span class="badge revision">En revisión</span>
                        @elseif(strtolower($pedido->estado) === 'listo')
                            <span class="badge listo">Listo</span>
                        @elseif(strtolower($pedido->estado) === 'pendiente')
                            <span class="badge pendiente">Pendiente</span>
                        @elseif(strtolower($pedido->estado) === 'cancelado')
                            <span class="badge cancelado">Cancelado</span>
                        @else
                            <span class="badge">{{ ucfirst($pedido->estado) }}</span>
                        @endif
                    </td>
                    <td class="acciones">
                        <button class="btn-ver" onclick="window.location.href='{{ route('dashboard.pedidos.detalle', $pedido->codigo) }}'">Visualizar</button>
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
    transition: background-color 0.2s ease;
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

/* ======== BOTONES ======== */
.btn-ver {
    background-color: #b22b27;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.9em;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
}
.btn-ver:hover {
    background-color: #941c1c;
}

/* ======== ESTADOS (BADGES) ======== */
.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    color: #fff;
    font-weight: 600;
    font-size: 0.85em;
}

/* En revisión → Amarillo */
.badge.revision {
    background-color: #ffb400;
    color: #fff;
}

/* Listo → Verde */
.badge.listo {
    background-color: #28a745;
}

/* Pendiente → Naranja suave */
.badge.pendiente {
    background-color: #ff9800;
}

/* Cancelado → Gris */
.badge.cancelado {
    background-color: #6c757d;
}

/* ======== TEXTO ======== */
.text-center {
    text-align: center;
    color: #444;
    font-style: italic;
    padding: 15px;
}
</style>
@endsection
