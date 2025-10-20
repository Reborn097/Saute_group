@extends('layouts.dashboard')

@section('titulo', 'Administrar proveedor')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú</button>
        <a href="{{ route('dashboard.agregar.proveedor') }}" class="btn-agregar">Agregar proveedor</a>
        
    </div>
    

    <table class="tabla">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>RFC</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proveedores as $proveedor)
                <tr>
                    <td>{{ $proveedor->id }}</td>
                    <td>{{ $proveedor->nombre }}</td>
                    <td>{{ $proveedor->telefono }}</td>
                    <td>{{ $proveedor->calle }} {{ $proveedor->num_direccion }}, {{ $proveedor->colonia }} (CP {{ $proveedor->codigo_postal }})</td>
                    <td>{{ $proveedor->rfc }}</td>
                    <td class="acciones">
                        <form action="{{ route('dashboard.proveedores.eliminar', $proveedor->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este proveedor?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-eliminar">🗑️</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay proveedores registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
.contenedor {
    background-color: #fceede;
    padding: 25px;
    border-radius: 12px;
    max-width: 1000px;
    margin: auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 {
    color: #ffffffff;
    font-weight: bold;
}
.acciones-superior {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.btn-agregar {
    background-color: #941c1c;
    color: #fff;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}
.btn-agregar:hover {
    background-color: #b82929;
}
.tabla {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
}
.tabla th {
    background-color: #b22b27;
    color: white;
    padding: 10px;
    text-align: center;
}
.tabla td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}
.tabla tr:hover {
    background-color: #f8dcdc;
}
.btn-eliminar {
    background-color: transparent;
    border: none;
    cursor: pointer;
    font-size: 18px;
}
.btn-eliminar:hover {
    color: #b22b27;
}
.text-center {
    text-align: center;
}

.btn-menu {
    background-color: #999;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 1em;
    font-family: 'Poppins';
    cursor: pointer;
    transition: background-color 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-menu:hover {
    background-color: #8f1f1c;
}

</style>
@endsection
