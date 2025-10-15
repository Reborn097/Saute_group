@extends('layouts.app')

@section('content')
<style>
    body {
        background-color: #2c2c2c;
        font-family: 'Figtree', sans-serif;
        color: #000;
    }

    .productos-container {
        background-color: #FAEBDD;
        margin: 0;
        width: 100%;
        min-height: 100vh;
        border: 2px solid #a72920;
    }

    .productos-header {
        background-color: #a72920;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 40px;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
    }

    .productos-header img {
        height: 60px;
    }

    .productos-header h1 {
        font-size: 28px;
        text-align: center;
        flex: 1;
    }

    .productos-header .user-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 3px solid #fff;
        background-color: #17242B;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 35px;
        color: #fff;
    }

    .search-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 25px 50px;
        background-color: #FAEBDD;
    }

    .search-bar input[type="text"] {
        flex: 1;
        padding: 10px 15px;
        font-size: 16px;
        border: 2px solid #000;
        border-radius: 8px;
        margin-right: 20px;
    }

    .search-bar button {
        background-color: #a72920;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }

    .search-bar button:hover {
        background-color: #801e16;
    }

    table {
        width: 90%;
        margin: 30px auto;
        border-collapse: collapse;
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
    }

    th {
        background-color: #dcdcdc;
        padding: 12px;
        font-size: 16px;
        border-bottom: 2px solid #000;
    }

    td {
        padding: 10px;
        text-align: center;
        border-bottom: 1px solid #ccc;
    }

    .btn-admin {
        background-color: #a72920;
        color: #fff;
        padding: 8px 14px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-admin:hover {
        background-color: #801e16;
    }

    .menu-btn {
        background-color: #a72920;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        margin-right: 15px;
    }

    .menu-btn:hover {
        background-color: #801e16;
    }
</style>

<div class="productos-container">
    <div class="productos-header">
        <img src="{{ asset('images/icons/logoSaute2.png') }}" alt="Sauté Group Logo">
        <h1>Productos</h1>
        <div class="user-icon">👤</div>
    </div>

    <div class="search-bar">
        <button class="menu-btn" onclick="window.location='{{ url('/dashboard/admin') }}'">Menú</button>
        <input type="text" placeholder="Buscar producto">
        <div>
            <button>Agregar categoría</button>
            <button>Agregar producto</button>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha</th>
                <th>Unidad</th>
                <th>Usuario</th>
                <th>Estado</th>
                <th>Editar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6" style="text-align:center;">(Sin productos registrados)</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
