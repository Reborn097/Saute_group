@extends('layouts.dashboard')

@section('titulo', 'Consultar Pedidos')

@section('contenido')
<div class="contenedor">
    <div class="acciones-superior">
        <button class="btn" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla-pedidos">
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
                        <td>{{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('d/m/Y') }}</td>
                        <td>{{ $pedido->usuario->name ?? 'Administrador' }}</td>
                        <td>${{ number_format($pedido->total, 2) }}</td>
                        <td>{{ ucfirst($pedido->estado) }}</td>
                        <td>
                            <button class="btn-visualizar" onclick="window.location.href='{{ route('dashboard.pedidos.detalle', $pedido->codigo) }}'">Visualizar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">No hay pedidos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .contenedor {
        background-color: #fae7d0;
        padding: 25px 35px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        max-width: 1100px;
        margin: 0 auto;
    }

    .acciones-superior {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 15px;
    }

    .btn {
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    }

    .btn-visualizar{
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    }

    .btn:hover {
        background-color: #911f1d;
    }

    .tabla-contenedor {
        overflow-x: auto;
    }

    .tabla-pedidos {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        border-radius: 10px;
        overflow: hidden;
    }

    th, td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }

    th {
        background-color: #f5f5f5;
        font-weight: bold;
    }

    tr:hover {
        background-color: #fff4ec;
        transition: background-color 0.2s;
    }

    .btn-editar {
        background-color: #b22b27;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-editar:hover {
        background-color: #911f1d;
    }

    td[colspan="6"] {
        text-align: center;
        color: #444;
        font-style: italic;
        padding: 15px;
    }
</style>
@endsection
