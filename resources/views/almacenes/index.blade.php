@extends('layouts.dashboard')

@section('titulo', 'Almacenes de ' . $unidad->nombre)

@section('contenido')

<div class="contenedor" style="max-width:900px; margin:0 auto;">

    <h2 style="text-align:center; margin-bottom:20px;">
        Almacenes de {{ $unidad->nombre }}
    </h2>

    <div style="margin-bottom:15px; text-align:center;">
        <button class="btn-guardar"
            onclick="window.location.href='{{ route('almacenes.create', $unidad->id) }}'">
            + Nuevo almacén
        </button>

        <button class="btn" onclick="window.location.href='{{ route('unidades.index') }}'">
            Volver
        </button>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Ubicación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>

            @forelse ($almacenes as $alm)
                <tr>
                    <td>{{ $alm->nombre }}</td>
                    <td>{{ ucfirst($alm->tipo) }}</td>
                    <td>{{ $alm->ubicacion ?? '—' }}</td>

                    <td>

                        <button class="btn-guardar" style="padding:6px 12px;"
                            onclick="window.location.href='{{ route('almacenes.edit', [$unidad->id, $alm->id]) }}'">
                            Editar
                        </button>

                        <form action="{{ route('almacenes.destroy', [$unidad->id, $alm->id]) }}"
                              method="POST" style="display:inline;">
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
                    <td colspan="4" style="text-align:center; color:#555">
                        No hay almacenes registrados.
                    </td>
                </tr>
            @endforelse

        </tbody>
    </table>

</div>

@endsection
