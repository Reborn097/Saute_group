@extends('layouts.dashboard')

@section('titulo', 'Administrar Pedidos')

@section('contenido')

<div class="contenedor">

    <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
        Menú principal
    </button>

    {{-- Filtro de tipo de pedido --}}
    <div class="filtros">
        <label for="filtroTipo"><strong>Mostrar:</strong></label>
        <select id="filtroTipo">
            <option value="todos">Todos</option>
            <option value="normal">Pedidos normales</option>
            <option value="especial">Pedidos especiales</option>
        </select>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha solicitud</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($pedidos as $p)
                    @php
                        // Detectar tipo de pedido:
                        // 1) si el controlador manda $p->tipo (normal/especial), úsalo
                        // 2) si sólo manda $p->es_especial (true/false), lo convertimos
                        $tipo = $p->tipo ?? (($p->es_especial ?? false) ? 'especial' : 'normal');
                    @endphp

                    <tr class="fila-pedido pedido-{{ $tipo }}" data-tipo="{{ $tipo }}">
                        <td>{{ $p->codigo }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->fecha_solicitud)->format('d/m/Y') }}</td>
                        <td>${{ number_format($p->total, 2) }}</td>

                        <td>
                            <span class="badge estado-{{ strtolower(str_replace(' ', '-', $p->estado)) }}">
                                {{ $p->estado }}
                            </span>
                        </td>

                        <td>
                            <button class="btn-ver" onclick="window.location.href='{{ route('dashboard.pedidos.admin.detalle', $p->codigo) }}'">Ver</button>
                            @if(!in_array($p->estado, ['Preaprobado', 'Aprobado']))
                                <a href="{{ route('dashboard.pedidos.admin.editar', $p->codigo) }}"
                                class="btn-editar">
                                    Editar
                                </a>
                            @else
                                <span class="badge badge-warning"
                                    title="Este pedido ya fue {{ strtolower($p->estado) }} y no puede editarse">
                                    🔒 
                                </span>
                            @endif
                            <button class="btn-pdf" onclick="window.location.href='{{ route('dashboard.pedidos.admin.pdf', $p->codigo) }}'">PDF</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>

</div>


<style>
/* CONTENEDOR GENERAL */
.contenedor{
    background:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* FILTRO */
.filtros{
    margin-top:15px;
    margin-bottom:10px;
    display:flex;
    align-items:center;
    gap:10px;
}

.filtros select{
    padding:6px 10px;
    border-radius:8px;
    border:1px solid #ccc;
    background:white;
}

/* TABLA GENERAL */
.tabla-contenedor{
    margin-top:10px;
}

.tabla{
    width:100%;
    border-collapse:collapse;
    border-radius:12px;
    overflow:hidden;
    background:white;
    box-shadow:0 3px 5px rgba(0,0,0,0.1);
}

.tabla th{
    background:#b22b27;
    color:white;
    padding:12px;
    text-align:center;
    font-weight:700;
}

.tabla td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
    font-size:15px;
}

/* Alternar filas SOLO para pedidos normales */
.tabla tbody tr.pedido-normal:nth-child(even){
    background:#f7f1ef;
}

/* Hover general */
.tabla tbody tr:hover{
    background:#f0d8d5;
}

/* Estilo base para normales (blanco) */
.tabla tbody tr.pedido-normal{
    background:#ffffff;
}

/* Estilo para pedidos ESPECIALES */
.tabla tbody tr.pedido-especial{
    background:#fff7c2 !important;   /* amarillo suave */
}

/* BOTONES */
.btn-menu{
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 15px;
    border-radius:8px;
    cursor:pointer;
}

.btn-ver{
    background:#b22b27;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    margin-right:5px;
    cursor:pointer;
}

.btn-editar{
    background:#d98f00;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    margin-right:5px;
    cursor:pointer;
}

.btn-pdf{
    background:#555;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    cursor:pointer;
}

.btn-ver:hover,
.btn-editar:hover,
.btn-pdf:hover{
    opacity:0.8;
}

/* ETIQUETAS DE ESTADO */
.badge{
    padding:5px 12px;
    border-radius:15px;
    font-size:13px;
    font-weight:600;
    color:#222;
}

.estado-en-revisión{
    background:#ffcc66;
}

.estado-pendiente{
    background:#ffe08a;
}

.estado-en-proceso{
    background:#66b3ff;
    color:white;
}

.estado-pre-aprobado{
    background:#a3d977;
}

.estado-aprobado{
    background:#4caf50;
    color:white;
}

.estado-finalizado{
    background:#9e66ff;
    color:white;
}
</style>

{{-- ===================== SCRIPTS ===================== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectFiltro = document.getElementById('filtroTipo');
    const filas = document.querySelectorAll('.fila-pedido');

    selectFiltro.addEventListener('change', function () {
        const filtro = this.value;  // 'todos' | 'normal' | 'especial'

        filas.forEach(tr => {
            const tipo = tr.dataset.tipo || 'normal';

            if (filtro === 'todos' || filtro === tipo) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        });
    });
});
</script>

@endsection
