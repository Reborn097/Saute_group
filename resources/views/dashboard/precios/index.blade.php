@extends('layouts.dashboard')

@section('titulo', 'Administrar Precios')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button type="button" class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>
    </div>

    {{-- Barra de búsqueda --}}
    <form action="{{ url()->current() }}" method="GET" class="filtros">
        <div class="campo" style="flex:1; min-width:260px;">
            <label>Buscar producto</label>
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar producto..."
                class="input">
        </div>

        <div class="campo acciones-filtro">
            <label style="visibility:hidden;">Acción</label>
            <button type="submit" class="btn">Buscar</button>

            @if(request()->filled('q'))
                <a href="{{ url()->current() }}" class="btn-cancelar">Limpiar</a>
            @endif
        </div>
    </form>

    {{-- Tabla de precios --}}
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Unidad</th>
                    <th>Categoría</th>
                    @if(auth()->user()->role === 'admin')
                        <th>Proveedor</th>
                    @endif
                    <th>Precio</th>
                    <th>Vigencia</th>
                    <th>Estatus</th>
                    <th style="width:170px;">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($relaciones as $rel)
                    <tr>
                        <td style="font-weight:800;">{{ $rel->producto->nombre }}</td>
                        <td>{{ $rel->producto->valor_medida }} {{ $rel->producto->unidad_medida }}</td>
                        <td>{{ optional($rel->producto->categoria)->nombre ?? '—' }}</td>

                        @if(auth()->user()->role === 'admin')
                            <td>{{ optional($rel->proveedor)->nombre ?? '—' }}</td>
                        @endif

                        <td>${{ number_format($rel->precio, 2) }}</td>

                        <td>
                            {{ \Carbon\Carbon::parse($rel->fecha_vigencia_inicio)->format('d/m/Y') }}
                            →
                            {{ $rel->fecha_vigencia_final ? \Carbon\Carbon::parse($rel->fecha_vigencia_final)->format('d/m/Y') : 'Vigente' }}
                        </td>

                        <td>
                            @if($rel->estado == 1)
                                <span class="badge activo">Activo</span>
                            @else
                                <span class="badge inactivo">Inactivo</span>
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('producto_proveedor.editar_precio', $rel->id) }}"
                               class="btn-mini btn-mini-editar">
                                Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role === 'admin' ? 8 : 7 }}" class="vacio">
                            No hay productos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $relaciones->onEachSide(1)->links('vendor.pagination.dashboard') }}
    </div>

</div>

<style>
/* ===== CONTENEDOR TARJETA ===== */
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:1100px;
    margin:auto;
}

/* ===== TOP ACTIONS ===== */
.acciones-superior{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:flex-start;
    margin-bottom:12px;
}

h2{ margin:0 0 10px; }

/* ===== BOTONES ===== */
.btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
}
.btn:hover{ background:#941c1c; }

.btn-menu{
    background:#777;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-menu:hover{ filter:brightness(.95); }

.btn-cancelar{
    background:#777;
    color:white;
    padding:9px 14px;
    border-radius:8px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
}
.btn-cancelar:hover{ filter:brightness(.95); }

/* ===== FILTROS ===== */
.filtros{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:end;
    margin:15px 0;
}
.campo{ min-width:240px; }
label{ font-weight:700; display:block; margin-bottom:6px; }

.input{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    background:#fff;
}

.acciones-filtro{
    display:flex;
    gap:10px;
    min-width:auto;
    align-items:end;
}
.acciones-filtro .btn,
.acciones-filtro .btn-cancelar{
    height:40px;
}

@media(max-width:720px){
    .campo{ min-width:100%; }
    .acciones-filtro{ width:100%; justify-content:flex-start; }
}

/* ===== TABLA (estándar) ===== */
.tabla-wrap{ overflow:auto; border-radius:10px; }
.tabla{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border:1px solid rgba(0,0,0,.12);
}
.tabla thead th{
    background:#b22b27;
    color:#fff;
    text-align:left;
    padding:10px;
    font-weight:700;
}
.tabla td{
    padding:10px;
    border-top:1px solid rgba(0,0,0,.08);
    vertical-align:top;
}
.vacio{
    text-align:center;
    padding:18px;
    color:#666;
    background:#fff7f0;
}

/* ===== BADGES ===== */
.badge{
    padding:5px 10px;
    border-radius:12px;
    color:#fff;
    font-size:.85em;
    display:inline-block;
}
.activo{ background:#4caf50; }
.inactivo{ background:#777; }

/* ===== BTN MINI (acciones tabla) ===== */
.btn-mini{
    padding:7px 12px;
    border-radius:8px;
    font-weight:800;
    border:none;
    cursor:pointer;
    min-width:95px;
    text-align:center;
    white-space:nowrap;
    display:inline-flex;
    justify-content:center;
    align-items:center;
    text-decoration:none;
}
.btn-mini-editar{
    background:#b22b27;
    color:#fff;
}
.btn-mini-editar:hover{ background:#941c1c; }

/* paginación */
.paginacion-wrap{ margin-top:14px; }
</style>
@endsection
