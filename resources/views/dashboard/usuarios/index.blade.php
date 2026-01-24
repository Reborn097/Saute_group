@extends('layouts.dashboard')

@section('titulo', 'Administración de Usuarios')

@section('contenido')

<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        {{-- Crear usuario = gestión → negro --}}
        <button class="btn-secundario"
            onclick="window.location.href='{{ route('usuarios.create') }}'">
            + Nuevo usuario
        </button>
    </div>

    <h2>Administración de Usuarios</h2>

    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Unidad</th>
                    <th style="width:260px;">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($usuarios as $u)

                    @php
                        $color = match($u->role) {
                            'admin' => 'fila-rojo',
                            'encargado_cocina', 'encargado_cafeteria' => 'fila-verde',
                            default => 'fila-blanco',
                        };
                    @endphp

                    <tr class="{{ $color }}">
                        <td style="font-weight:800;">{{ $u->name }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ ucfirst(str_replace('_',' ', $u->role)) }}</td>
                        <td>{{ $u->unidad->nombre ?? 'N/A' }}</td>
                        <td>
                            <div class="acciones">
                                <button class="btn-mini btn-mini-editar" type="button"
                                    onclick="window.location.href='{{ route('usuarios.edit', $u) }}'">
                                    Editar
                                </button>

                                <form action="{{ route('usuarios.destroy', $u) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Eliminar usuario?')"
                                      style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="btn-mini btn-mini-eliminar">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="vacio">
                            No hay usuarios registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<style>
/* ===== CONTENEDOR ===== */
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
    justify-content:space-between;
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

.btn-secundario{
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-secundario:hover{
    background:#941c1c;
}


/* ===== TABLA ===== */
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

/* ===== FILAS POR ROL ===== */
.fila-rojo{ background:#ffe5e5; }
.fila-verde{ background:#e9f6ec; }
.fila-blanco{ background:#fff; }

/* ===== ACCIONES ===== */
.acciones{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}
.btn-mini{
    padding:7px 12px;
    border-radius:8px;
    font-weight:800;
    border:none;
    cursor:pointer;
    min-width:95px;
    text-align:center;
    white-space:nowrap;
}
.btn-mini-editar{
    background:#b22b27;
    color:white;
    border:none;
    padding:9px 14px;
    border-radius:8px;
    cursor:pointer;
}
.btn-mini-editar:hover{ filter:brightness(.95); }

.btn-mini-eliminar{
    background:#777;
    color:#fff;
}
.btn-mini-eliminar:hover{ filter:brightness(.95); }
</style>

@endsection
