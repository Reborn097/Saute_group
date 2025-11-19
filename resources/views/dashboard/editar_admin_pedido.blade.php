@extends('layouts.dashboard')

@section('titulo', 'Editar Pedido')

@section('contenido')

<div class="contenedor">

    <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.pedidos.admin') }}'">Regresar</button>

    <h2>Editar pedido #{{ $pedido->codigo }}</h2>

    <form method="POST" action="{{ route('dashboard.pedidos.admin.actualizar', $pedido->codigo) }}">
        @csrf

        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>
                @foreach($pedido->detalles as $d)
                <tr>
                    <td>{{ $d->productoProveedor->producto->nombre }}</td>

                    <td>
                        <input type="number" 
                               name="cantidad[{{ $d->id }}]" 
                               value="{{ $d->cantidad_solicitada }}" 
                               min="1">
                    </td>

                    <td>${{ number_format($d->precio_unitario, 2) }}</td>

                    <td>
                        ${{ number_format($d->precio_unitario * $d->cantidad_solicitada, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <button class="btn">Guardar cambios</button>
    </form>

</div>

<style>
.contenedor{ background:#fceede; padding:25px; border-radius:12px; max-width:1100px; margin:auto; }
.tabla{ width:100%; background:white; border-collapse:collapse; }
.tabla th{ background:#b22b27; color:white; padding:10px; }
.tabla td{ padding:10px; text-align:center; }
input{ width:70px; padding:5px; }
.btn{ background:#b22b27; color:white; padding:8px 12px; border-radius:8px; cursor:pointer; border:none; }
</style>

@endsection
