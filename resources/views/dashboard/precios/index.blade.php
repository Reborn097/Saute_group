@extends('layouts.dashboard')

@section('titulo', 'Lista de Precios')

@section('contenido')
<div class="contenedor-form">

    <h2 class="titulo-seccion">Lista de Precios</h2>

    {{-- Barra de búsqueda --}}
    <form action="{{ url()->current() }}" method="GET" class="form-grupo buscador">
        <input 
            type="text" 
            name="q" 
            value="{{ request('q') }}" 
            placeholder="Buscar producto..." 
            class="input-buscar">
        
        <button type="submit" class="btn-guardar">Buscar</button>

    @if(request()->filled('q'))
        <a href="{{ url()->current() }}" class="btn-cancelar">Limpiar</a>
    @endif
</form>

    {{-- Tabla de precios --}}
    <table class="tabla">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Unidad</th>
                <th>Categoría</th>
                @if(auth()->user()->rol === 'admin')
                    <th>Proveedor</th>
                @endif
                <th>Precio</th>
                <th>Vigencia</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($relaciones as $rel)
                <tr>
                    <td>{{ $rel->id }}</td>
                    <td>{{ $rel->producto->nombre }}</td>
                    <td>{{ $rel->producto->valor_medida }} {{ $rel->producto->unidad_medida }}</td>
                    <td>{{ optional($rel->producto->categoria)->nombre ?? '—' }}</td>

                    @if(auth()->user()->rol === 'admin')
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
                        <a href="{{ route('producto_proveedor.editar_precio', $rel->id) }}" class="btn-guardar" style="padding:6px 12px;">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()->rol === 'admin' ? 9 : 8 }}" style="text-align:center; color:#555;">
                        No hay productos registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>

<style>
.tabla {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
}
.tabla th, .tabla td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
.tabla th {
    background-color: #b22b27;
    color: white;
    font-weight: 600;
}
.badge {
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 0.9rem;
}
.badge.activo {
    background-color: #d4edda;
    color: #155724;
}
.badge.inactivo {
    background-color: #f8d7da;
    color: #721c24;
}
/* ======== BUSCADOR ======== */
.buscador {
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: flex-start;
    margin-bottom: 25px;
}

.input-buscar {
    flex: 1;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    background-color: #fff;
}

.btn-guardar {
    background-color: #b22b27;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-guardar:hover {
    background-color: #8c1f1b;
}

</style>
@endsection
