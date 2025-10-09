@extends('layouts.app')

@section('content')
<style>
    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        background-color: #2c2c2c;
        font-family: 'Figtree', sans-serif;
        color: #000;
    }

    .admin-container {
        background-color: #FAEBDD;
        width: 100%;
        height: 100vh;
        border-radius: 0;
        border: none;
        display: flex;
        flex-direction: column;
    }

    .admin-header {
        background-color: #a72920;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 40px;
        flex-shrink: 0;
    }

    .admin-header img {
        height: 60px;
    }

    .admin-header h1 {
        font-size: 28px;
        text-align: center;
        flex: 1;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-icon {
        font-size: 42px;
        border: 3px solid #fff;
        border-radius: 50%;
        padding: 5px 10px;
        background-color: #22313f;
    }

    .logout-btn {
        background-color: #fff;
        color: #8B0000;
        border: none;
        padding: 10px 18px;
        border-radius: 10px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }

    .logout-btn:hover {
        background-color: #f2f2f2;
        transform: scale(1.05);
    }

    .menu-grid {
        flex-grow: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 30px;
        padding: 40px;
        justify-items: center;
        align-items: center;
        background-color: #FAEBDD;
    }

    .menu-item {
        background-color: #f7e3b4;
        border-radius: 8px;
        text-align: center;
        padding: 20px;
        transition: all 0.3s;
        cursor: pointer;
        width: 140px;
    }

    .menu-item:hover {
        background-color: #ffe5c2;
        transform: scale(1.05);
    }

    .menu-item img {
        width: 70px;
        height: 70px;
    }

    .menu-item p {
        margin-top: 10px;
        font-size: 15px;
        font-weight: bold;
    }
</style>

<div class="admin-container">
    <div class="admin-header">
        <img src="{{ asset('images/icons/logoSaute2.png') }}" alt="Sauté Group Logo">
        <h1>Menú principal</h1>
        <div class="header-right">
            <div class="user-icon">👤</div>
            <!-- Botón para cerrar sesión -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    <div class="menu-grid">
        <div class="menu-item">
            <img src="{{ asset('images/icons/pedidos.png') }}" alt="Pedidos">
            <p>Consultar pedidos</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/admin_pedidos.png') }}" alt="Admin pedidos">
            <p>Administrar pedidos</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/pedido_especial.png') }}" alt="Pedido especial">
            <p>Solicitar pedido especial</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/pedido.png') }}" alt="Pedido">
            <p>Solicitar pedido</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/comensales.png') }}" alt="Comensales">
            <p>Registrar comensales</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/corte_caja.png') }}" alt="Corte caja">
            <p>Registrar corte de caja</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/inventario.png') }}" alt="Inventario">
            <p>Administrar inventario</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/producto.png') }}" alt="Producto">
            <p>Administrar producto</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/proveedor.png') }}" alt="Proveedor">
            <p>Registrar proveedor</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/admin_proveedor.png') }}" alt="Admin proveedor">
            <p>Administrar proveedor</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/comparativa.png') }}" alt="Comparativa">
            <p>Comparativa de precios</p>
        </div>

        <div class="menu-item">
            <img src="{{ asset('images/icons/reportes.png') }}" alt="Reportes">
            <p>Reportes</p>
        </div>
    </div>
</div>
@endsection
