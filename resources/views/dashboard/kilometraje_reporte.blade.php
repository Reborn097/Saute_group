@extends('layouts.dashboard')

@section('titulo', 'Reporte de Kilometraje')

@section('contenido')

<style>
    .page-wrap{ width:90%; margin:0 auto; max-width:1200px; }
    .panel{ background:#fde7d2; border-radius:14px; padding:18px; box-shadow:0 3px 8px rgba(0,0,0,0.12); }
    .panel-top{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .btn-menu{
        background:#a13c2f; color:#fff; border:none; padding:8px 14px; border-radius:10px;
        font-weight:600; cursor:pointer;
    }
    .btn-menu:hover{ opacity:.9; }
    .filters{ display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin:10px 0 14px; }
    .field{ display:flex; flex-direction:column; gap:6px; min-width:180px; }
    .field label{ font-weight:600; color:#6b2a20; }
    .filters input[type="date"], .filters select{
        padding:8px 10px; border-radius:8px; border:1px solid #d9b699; background:#fff;
    }
    .btn-buscar, .btn{
        background:#b55446; color:#fff; border:none; padding:8px 14px; border-radius:10px;
        cursor:pointer; font-weight:600;
    }
    .btn:hover, .btn-buscar:hover{ opacity:.92; }
    .table-wrap{ background:#fff8f1; border:1px solid #e7cdb5; border-radius:12px; padding:10px; }
    .scroll-x{ overflow-x:auto; }
    .tabla{ width:100%; border-collapse:collapse; min-width:980px; }
    .tabla th{ background:#f4d7b8; color:#6b2a20; text-align:left; padding:8px; font-weight:700; }
    .tabla td{ border-top:1px solid #f0d7c0; padding:8px; vertical-align:top; }
    .tabla tr:nth-child(even) td{ background:#fffdf9; }
    .kpi{ display:flex; flex-wrap:wrap; gap:12px; margin:8px 0 12px; }
    .kpi .card{
        background:#fff; border:1px solid #e7cdb5; border-radius:12px; padding:10px 12px;
        min-width:180px;
    }
    .kpi .label{ color:#6b2a20; font-size:.85rem; }
    .kpi .value{ font-weight:700; font-size:1.05rem; }
    @media (max-width: 768px){
        .filters{ flex-direction:column; align-items:stretch; }
        .field{ min-width:auto; }
        .tabla{ min-width:880px; }
    }
</style>

<div class="page-wrap">
    <div class="panel">
        <div class="panel-top">
            <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.kilometraje') }}'">
                Volver a captura
            </button>
            <button class="btn" onclick="window.location.href='{{ route('dashboard.kilometraje.pdf', request()->query()) }}'">
                Exportar PDF
            </button>
        </div>

        <h2 style="margin:6px 0 10px;">Reporte de kilometraje</h2>

        <form class="filters" method="GET" action="{{ route('dashboard.kilometraje.reporte') }}">
            @if($esAdmin)
                <div class="field">
                    <label>Unidad operativa</label>
                    <select name="rep_unidad_id">
                        <option value="">-- Todas --</option>
                        @foreach($unidades as $u)
                            <option value="{{ $u->id }}" {{ (string)$repUnidad === (string)$u->id ? 'selected' : '' }}>
                                {{ $u->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="rep_unidad_id" value="{{ $repUnidad }}">
            @endif

            <div class="field">
                <label>Desde</label>
                <input type="date" name="rep_desde" value="{{ $repDesde }}" required>
            </div>
            <div class="field">
                <label>Hasta</label>
                <input type="date" name="rep_hasta" value="{{ $repHasta }}" required>
            </div>

            <button class="btn-buscar" type="submit">Ver reporte</button>
        </form>

        <div class="kpi">
            <div class="card">
                <div class="label">Días con registro</div>
                <div class="value">{{ $totales['dias'] }}</div>
            </div>
            <div class="card">
                <div class="label">Total km recorridos</div>
                <div class="value">{{ number_format((float)$totales['km_recorridos'], 0) }}</div>
            </div>
            <div class="card">
                <div class="label">Km inicio mínimo</div>
                <div class="value">{{ $totales['km_inicio_min'] ?? '-' }}</div>
            </div>
            <div class="card">
                <div class="label">Km final máximo</div>
                <div class="value">{{ $totales['km_final_max'] ?? '-' }}</div>
            </div>
            <div class="card">
                <div class="label">Promedio diésel inicio</div>
                <div class="value">{{ $totales['diesel_inicio_prom'] !== null ? number_format((float)$totales['diesel_inicio_prom'], 2) : '-' }}%</div>
            </div>
            <div class="card">
                <div class="label">Promedio diésel final</div>
                <div class="value">{{ $totales['diesel_final_prom'] !== null ? number_format((float)$totales['diesel_final_prom'], 2) : '-' }}%</div>
            </div>
        </div>

        <div class="table-wrap">
            <div class="scroll-x">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Unidad</th>
                            <th>Km inicio</th>
                            <th>Km final</th>
                            <th>Km recorridos</th>
                            <th>Diésel inicio (%)</th>
                            <th>Diésel final (%)</th>
                            <th>Lugares visitados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reporte as $r)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $r->unidad_nombre }}</td>
                                <td>{{ $r->km_inicio ?? '' }}</td>
                                <td>{{ $r->km_final ?? '' }}</td>
                                <td>{{ $r->km_recorridos ?? '' }}</td>
                                <td>{{ $r->diesel_inicio_pct ?? '' }}</td>
                                <td>{{ $r->diesel_final_pct ?? '' }}</td>
                                <td>{{ $r->lugares_visitados ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">Sin datos en el rango seleccionado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
