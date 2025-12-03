@extends('layouts.dashboard')

@section('titulo', 'Unidades Operativas')

@section('contenido')

<div class="contenedor">

    <h2 style="margin-bottom: 20px;">Unidades Operativas</h2>

    <div style="margin-bottom: 15px;">
        <button class="btn" onclick="window.location.href='{{ route('unidades.create') }}'">
            + Nueva Unidad Operativa
        </button>

        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú Principal
        </button>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Ubicación</th>
                <th>Responsable</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            @forelse($unidades as $u)
                <tr>
                    <td>{{ $u->nombre }}</td>
                    <td>{{ $u->tipo ?? '—' }}</td>
                    <td>{{ $u->ubicacion ?? '—' }}</td>
                    <td>{{ optional($u->responsable)->name ?? '—' }}</td>

                    <td>
                        <button class="btn-guardar" 
                            onclick="window.location.href='{{ route('unidades.edit', $u->id) }}'"
                            style="padding:6px 12px;">
                            Editar
                        </button>

                        <button class="btn" 
                            onclick="window.location.href='{{ route('almacenes.index', $u->id) }}'">
                            Almacenes
                        </button>


                        <form action="{{ route('unidades.destroy', $u->id) }}" 
                              method="POST" 
                              style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn-cancelar" style="padding:6px 12px;">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#555;">
                        No hay unidades registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
 


</style>
@endsection
