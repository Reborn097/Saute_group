@extends('layouts.dashboard')

@section('titulo', 'Administración de Usuarios')

@section('contenido')
<div class="contenedor-form">

    <button type="button" class="btn"
        onclick="window.location.href='{{ route('dashboard.admin') }}'">
        Menú principal
    </button>

    <h2 style="margin-top:20px;">Administración de Usuarios</h2>

    <div style="margin-top:5px; margin-bottom:10px;">
        <a href="{{ route('usuarios.create') }}" 
            class="btn"
            style="padding:6px 12px; font-size:14px;">
            + Nuevo usuario
        </a>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Unidad</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            @forelse($usuarios as $u)

                @php
                $color = match($u->role) {
                    'admin' => 'fila-rojo',
                    'ceo' => 'fila-blanco',
                    'encargado_cocina' => 'fila-verde',
                    'encargado_cafeteria' => 'fila-verde',
                    'almacenista' => 'fila-blanco',
                    'proveedor' => 'fila-blanco',
                    default => 'fila-blanco',
                };
                @endphp
                <tr class="{{$color}}">
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->username }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ ucfirst(str_replace('_',' ', $u->role)) }}</td>
                    <td>{{ $u->unidad->nombre ?? 'N/A' }}</td>
                    <td style="display:flex; gap:8px;">
                        <a href="{{ route('usuarios.edit', $u) }}"
                           class="btn"
                           style="padding:6px 12px; font-size:14px;">
                            Editar
                        </a>

                        <form action="{{ route('usuarios.destroy', $u) }}"
                              method="POST"
                              onsubmit="return confirm('¿Eliminar usuario?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="btn"
                                style="padding:6px 12px; font-size:14px;">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">
                        No hay usuarios registrados
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
@endsection
