@extends('layouts.dashboard')

@section('titulo', 'Pedidos Diarios')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pedidos-diarios.css') }}">
@endpush

@section('contenido')

<div class="contenedor">

    {{-- =========================
        ACCIONES SUPERIORES
    ========================= --}}
    <div class="pd-acciones-superior">

        <div class="pd-acciones-left">
            <button class="btn-menu" type="button" onclick="window.location.href='{{ route('dashboard.admin') }}'">
                Menú principal
            </button>
        </div>

        <div class="pd-acciones-right">
            @if(!empty($puedeCrear) && $puedeCrear)
                <button class="btn" type="button" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.pan.create') }}'">
                    Nuevo pedido PAN
                </button>

                <button class="btn" type="button" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.tortilla.create') }}'">
                    Nuevo pedido TORTILLA
                </button>
            @endif
        </div>

    </div>

    <div class="pd-titulo">Listado de pedidos diarios</div>

    {{-- =========================
        FILTROS
        - Auto aplica (debounce)
        - Restaura focus después de recargar
    ========================= --}}
    <div class="pd-filtros-wrap">
        <form method="GET" id="formFiltros" class="pd-filtros-grid" action="{{ route('dashboard.pedidos_diarios.index') }}">

            <div class="pd-filtro">
                <label class="pd-label">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" autocomplete="off">
            </div>

            <div class="pd-filtro">
                <label class="pd-label">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" autocomplete="off">
            </div>

            <div class="pd-filtro">
                <label class="pd-label">Código</label>
                <input
                    type="text"
                    name="codigo"
                    value="{{ request('codigo') }}"
                    placeholder="Ej: DENE26001"
                    autocomplete="off"
                    inputmode="text"
                >
            </div>

        </form>

        {{-- BOTÓN LIMPIAR (EN OTRA LÍNEA) --}}
        <div class="pd-limpiar-row">
            <a href="{{ route('dashboard.pedidos_diarios.index') }}" class="pd-btn-limpiar">
                Limpiar filtros
            </a>
        </div>
    </div>

    {{-- =========================
        TABLA
    ========================= --}}
    <div class="pd-tabla-contenedor">
        <table class="pd-tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Semana</th>
                    <th>Tipo</th>
                    <th>Unidad operativa</th>
                    <th>Total $</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
            @forelse($pedidos as $p)

                @php
                    $estadoRaw = trim((string)($p->estado ?? ''));
                    $estadoNorm = mb_strtolower($estadoRaw);

                    // clases badge
                    $badgeClass = 'pd-badge-revision';
                    if ($estadoNorm === 'pendiente') $badgeClass = 'pd-badge-pendiente';
                    elseif ($estadoNorm === 'visto') $badgeClass = 'pd-badge-visto';
                    elseif ($estadoNorm === 'preaprobado') $badgeClass = 'pd-badge-preaprobado';
                    elseif ($estadoNorm === 'aprobado') $badgeClass = 'pd-badge-aprobado';
                    elseif ($estadoNorm === 'rechazado') $badgeClass = 'pd-badge-rechazado';
                    elseif (in_array($estadoNorm, ['en revision','en revisión'], true)) $badgeClass = 'pd-badge-revision';
                @endphp

                <tr>
                    <td class="pd-col-codigo">{{ $p->codigo }}</td>

                    <td class="pd-semana">
                        {{ \Carbon\Carbon::parse($p->semana_inicio)->format('d/m/Y') }}
                        –
                        {{ \Carbon\Carbon::parse($p->semana_fin)->format('d/m/Y') }}
                    </td>

                    <td class="pd-tipo">{{ $p->tipo }}</td>

                    <td>{{ $p->unidadOperativa->nombre ?? '—' }}</td>

                    <td>${{ number_format($p->total, 2) }}</td>

                    <td>
                        <span class="pd-badge {{ $badgeClass }}">
                            {{ $estadoRaw ?: '—' }}
                        </span>
                    </td>

                    <td>
                        <div class="pd-acciones">
                            <a class="pd-btn-detalles"
                               href="{{ route('dashboard.pedidos_diarios.show', $p->id) }}">
                                Detalles
                            </a>
                            @if(!empty($p->puedeEditar) && $p->puedeEditar)
                                <a class="pd-btn-editar"
                                href="{{ route('dashboard.pedidos_diarios.edit', $p->id) }}">
                                    Editar
                                </a>
                            @else
                                <span class="pd-btn-editar pd-btn-disabled" title="No editable en este estado">
                                    Editar
                                </span>
                            @endif
                            <a class="pd-btn-pdf"
                               href="{{ route('dashboard.pedidos_diarios.pdf', $p->id) }}">
                                PDF
                            </a>
                        </div>
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:18px; font-style:italic;">
                        No hay pedidos diarios.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

</div>

<script>
(function(){
    const form = document.getElementById('formFiltros');
    if(!form) return;

    // --- restaura focus/posición del cursor tras recarga ---
    const KEY = 'pd_focus';
    window.addEventListener('load', () => {
        try{
            const data = JSON.parse(sessionStorage.getItem(KEY) || 'null');
            if(!data || !data.name) return;

            const el = form.querySelector(`[name="${CSS.escape(data.name)}"]`);
            if(!el) return;

            el.focus();
            const pos = typeof data.pos === 'number' ? data.pos : el.value.length;
            if (typeof el.setSelectionRange === 'function') {
                el.setSelectionRange(pos, pos);
            }

            sessionStorage.removeItem(KEY);
        }catch(e){}
    });

    // --- autosubmit con debounce (para que no recargue por cada tecla de inmediato) ---
    let t = null;

    function submitDebounced(delay){
        clearTimeout(t);
        t = setTimeout(() => {
            // guarda focus antes de navegar
            const active = document.activeElement;
            if(active && active.name){
                const pos = (typeof active.selectionStart === 'number') ? active.selectionStart : null;
                sessionStorage.setItem(KEY, JSON.stringify({ name: active.name, pos }));
            }
            form.submit();
        }, delay);
    }

    // fechas: casi inmediato
    form.querySelectorAll('input[type="date"]').forEach(inp => {
        inp.addEventListener('change', () => submitDebounced(50));
    });

    // código: debounce más largo para que no sea castroso
    const codigo = form.querySelector('input[name="codigo"]');
    if(codigo){
        codigo.addEventListener('input', () => submitDebounced(450));
    }
})();
</script>

@endsection
