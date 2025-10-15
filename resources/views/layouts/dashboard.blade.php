<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo') - Saute Group</title>
    <link rel="icon" href="{{ asset('images/icons/logoSaute3.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* ======= ESTILO GENERAL ======= */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #faebd7;
            margin: 0;
        }

        header {
            background-color: #b22b27;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 45px;
        }

        /* 🔹 Título dinámico dentro del header */
        .titulo {
            flex: 1;
            text-align: center;
            font-size: 1.5em;
            font-weight: 600;
            margin: 0;
        }

        .perfil-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .perfil {
            background-color: white;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .perfil img {
            width: 25px;
        }

        .btn-cerrar {
            background-color: white;
            color: #b22b27;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-cerrar:hover {
            background-color: #f1f1f1;
        }

        main {
            padding: 40px;
        }

        /* Contenedor de formularios, tablas o secciones */
        .contenedor {
            background-color: #f9e3cc;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            color: #b22b27;
            text-align: center;
            margin-bottom: 25px;
        }

        .btn {
            background-color: #b22b27;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background-color: #911f1d;
        }

        .cancelar {
            background-color: #aaa;
        }

        .cancelar:hover {
            background-color: #888;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #dcdcdc;
            padding: 10px;
            text-align: left;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Poppins';
            margin-bottom: 15px;
        }

        textarea {
            resize: none;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="{{ asset('images/icons/logoSaute2.png') }}" alt="Logo">
        </div>

        <h2 class="titulo">@yield('titulo')</h2>

        <div class="perfil-container">
            <div class="perfil">
                <img src="{{ asset('images/icons/user.png') }}" alt="Usuario">
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-cerrar">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <main>
        @yield('contenido')
    </main>
</body>
</html>
