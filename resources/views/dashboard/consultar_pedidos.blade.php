@extends('layouts.dashboard')

@section('titulo', 'Consultar Pedidos')

@section('contenido')
<div class="contenedor">

    {{-- ============================
          BOTÓN MENÚ PRINCIPAL
    ============================= --}}
    <div class="acciones-superior">
        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>
    </div>

    {{-- ============================
          FILTRO DE TIPOS
    ============================= --}}
    <form method="GET" class="filtro-form">
        <label for="tipo">Mostrar:</label>
        <select name="tipo" id="tipo" onchange="this.form.submit()">
            <option value="todos" {{ (request('tipo') == 'todos') ? 'selected' : '' }}>Todos</option>
            <option value="normales" {{ (request('tipo') == 'normales') ? 'selected' : '' }}>Pedidos normales</option>
            <option value="especiales" {{ (request('tipo') == 'especiales') ? 'selected' : '' }}>Pedidos especiales</option>
        </select>
    </form>

    {{-- ============================
          TABLA DE PEDIDOS
    ============================= --}}
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
                <tr class="{{ $pedido->es_especial ? 'row-especial' : '' }}">
                    <td>{{ $pedido->codigo }}</td>

                    <td>{{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y') }}</td>

                    <td>{{ $pedido->usuario->name ?? 'Administrador' }}</td>

                    <td>${{ number_format($pedido->total, 2) }}</td>

                    {{-- TIPO DEL PEDIDO --}}
                    <td>
                        @if($pedido->es_especial)
                            <span class="badge tipo-especial">Especial</span>
                        @else
                            <span class="badge tipo-normal">Normal</span>
                        @endif
                    </td>

                    {{-- ESTADOS --}}
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

{{-- ============================
          ESTILOS
============================= --}}
<style>
.contenedor {
    background-color: #fceede;
    padding: 25px 35px;
    border-radius: 12px;
    max-width: 1100px;
    margin: 0 auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    font-family: 'Poppins', sans-serif;
}

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
.btn-menu:hover { background-color: #941c1c; }

/* FILTRO */
.filtro-form {
    margin-bottom: 20px;
    font-size: 1rem;
}
.filtro-form select {
    padding: 8px;
    border-radius: 6px;
}

/* TABLA */
.tabla {
    width: 100%;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    border-collapse: collapse;
}

.tabla th {
    background-color: #b22b27;
    color: white;
    padding: 12px;
    text-align: center;
}

.tabla td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}

/* DIFERENCIAR FILAS ESPECIALES */
.row-especial {
    background-color: #fff3cd !important;
}

/* BADGES */
.badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 15px;
    font-weight: 600;
}

/* Tipo */
.tipo-normal { background:#6c8793; color:white; }
.tipo-especial { background:#ff9800; color:white; }

/* Estados */
.estado-pendiente { background:#ffe08a; color:#5c3d00; }
.estado-en-proceso { background:#66b3ff; color:white; }
.estado-pre-aprobado { background:#a3d977; color:#244a00; }
.estado-aprobado { background:#4caf50; color:white; }
.estado-finalizado { background:#9e66ff; color:white; }
.estado-revision { background:#ffcc66; color:#5c3d00; }

/* BOTÓN VER */
.btn-ver {
    background-color: #b22b27;
    color: white;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
}
.btn-ver:hover { background-color:#941c1c; }

.text-center { text-align:center; padding:15px; font-style:italic; }

</style>

@endsection
