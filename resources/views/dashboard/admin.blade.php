@extends('layouts.dashboard')

@section('titulo', 'Menú principal')

@section('contenido')

<div class="contenedor" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 25px;">
    {{-- 🔹 Filas de opciones con íconos locales --}}

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/consultar_pedidos.png') }}" alt="Pedidos">
        <p><b>Consultar pedidos</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/administrar_pedidos.png') }}" alt="Admin pedidos">
        <p><b>Administrar pedidos</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/pedido_especial.png') }}" alt="Pedido especial">
        <p><b>Solicitar pedido especial</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/solicitar_pedido.png') }}" alt="Solicitar pedido">
        <p><b>Solicitar pedido</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/registro_comensales.png') }}" alt="Comensal">
        <p><b>Registrar comensales</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/corte_caja.png') }}" alt="Corte caja">
        <p><b>Registrar corte de caja</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/administrar_inventario.png') }}" alt="Inventario">
        <p><b>Administrar inventario</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.productos') }}'">
        <img src="{{ asset('images/icons/iconos/administrar_producto.png') }}" alt="Producto">
        <p><b>Administrar productos</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.agregar.proveedor') }}'">
        <img src="{{ asset('images/icons/iconos/registro_proveedor.png') }}" alt="Proveedor">
        <p><b>Registrar proveedor</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.proveedores') }}'">
        <img src="{{ asset('images/icons/iconos/administrar_proveedor.png') }}" alt="Admin proveedor">
        <p><b>Administrar proveedor</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.precios') }}'">
        <img src="{{ asset('images/icons/iconos/comparativa_precios.png') }}" alt="Comparativa">
        <p><b>Administrar precios</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/Reportes.png') }}" alt="Reportes">
        <p><b>Reportes</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/iconos/agregar_usuario.png') }}" alt="Usuarios">
        <p><b>Agregar usuario</b></p>
    </div>
</div>

<style>
    .tarjeta {
        background-color: #f9e3cc;
        width: 150px;
        height: 150px;
        text-align: center;
        border-radius: 14px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transition: transform 0.2s, box-shadow 0.3s;
        cursor: pointer;
        padding: 12px;
    }

    .tarjeta img {
        width: 65px;
        height: 65px;
        object-fit: contain;
        margin-top: 8px;
    }

    .tarjeta:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.25);
    }

    .tarjeta p {
        font-size: 0.9em;
        color: #333;
        margin-top: 8px;
        font-family: 'Poppins', sans-serif;
    }

    .tarjeta p b {
        color: #111;
    }
</style>

@endsection
