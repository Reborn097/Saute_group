<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión - Sauté Group</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
    background-color: #A72920;
    font-family: 'Figtree', sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}

.login-container {
    background-color: #FAEBDD;
    width: 900px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 40px;
    border: 2px solid #2C2C2C;
}
        .login-form {
            background-color: #0F2235;
            padding: 30px;
            border-radius: 8px;
            color: white;
            width: 320px;
        }
        .login-form h2 {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            border: none;
            color: black;
        }
        button {
            width: 100%;
            background-color: #FAEBDD;
            color: #0F2235;
            font-weight: bold;
            padding: 10px;
            border-radius: 6px;
            margin-top: 20px;
            cursor: pointer;
        }
        button:hover {
            background-color: #f5d8b9;
        }
        .forgot {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #d7e0ec;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo e imagen -->
        <div class="logo-area text-center">
            <img src="{{ asset('images/icons/logoSaute2.png') }}" alt="Sauté Group" style="width:450px; margin:auto;">

        </div>

        <!-- Formulario -->
        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf
            <h2>Iniciar Sesión</h2>

            <div>
                <label for="email">Usuario</label>
                <input id="email" type="email" name="email" placeholder="Correo electrónico" required autofocus>
            </div>

            <div class="mt-4">
                <label for="password">Contraseña</label>
                <input id="password" type="password" name="password" placeholder="Contraseña" required>
            </div>

            <button type="submit">Iniciar Sesión</button>

            <a href="{{ route('password.request') }}" class="forgot">¿Olvidaste la contraseña?</a>
        </form>
    </div>
</body>
</html>
