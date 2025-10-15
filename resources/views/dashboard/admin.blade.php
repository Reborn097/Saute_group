@extends('layouts.dashboard')

@section('titulo', 'Menú principal')

@section('contenido')


<div class="contenedor" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 25px;">
    {{-- Bloques de opciones --}}
    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/pedido.png') }}" alt="Pedidos">
        <p><b>Consultar pedidos</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/admin_pedidos.png') }}" alt="Admin pedidos">
        <p><b>Administrar pedidos</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/especial.png') }}" alt="Pedido especial">
        <p><b>Solicitar pedido especial</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/comensal.png') }}" alt="Comensal">
        <p><b>Registrar comensales</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/caja.png') }}" alt="Corte caja">
        <p><b>Registrar corte de caja</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/inventario.png') }}" alt="Inventario">
        <p><b>Administrar inventario</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.productos') }}'">
        <img src="{{ asset('images/icons/producto.png') }}" alt="Producto">
        <p><b>Administrar productos</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/proveedor.png') }}" alt="Proveedor">
        <p><b>Registrar proveedor</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/admin_proveedor.png') }}" alt="Admin proveedor">
        <p><b>Administrar proveedor</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/comparativa.png') }}" alt="Comparativa">
        <p><b>Comparativa de precios</b></p>
    </div>

    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/reportes.png') }}" alt="Reportes">
        <p><b>Reportes</b></p>
    </div>

    {{-- 🔹 Nuevo botón solicitado --}}
    <div class="tarjeta" onclick="window.location.href='#'">
        <img src="{{ asset('images/icons/usuarios.png') }}" alt="Usuarios">
        <p><b>Agregar usuario</b></p>
    </div>
</div>

<style>
    .tarjeta {
        background-color: #f9e3cc;
        width: 140px;
        height: 140px;
        text-align: center;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transition: transform 0.2s, box-shadow 0.3s;
        cursor: pointer;
        padding: 10px;
    }

    .tarjeta img {
        width: 50px;
        height: 50px;
        object-fit: contain;
        margin-top: 10px;
    }

    .tarjeta:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.2);
    }

    .tarjeta p {
        font-size: 0.9em;
        color: #333;
        margin-top: 8px;
    }

    .tarjeta p b {
        color: #111;
    }
</style>
@endsection
