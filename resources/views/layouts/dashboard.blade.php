<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo') - Saute Group</title>
    <link rel="icon" href="{{ asset('images/icons/logoSaute3.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">


    {{-- ✅ CSS GLOBAL DEL DASHBOARD --}}
    @php
        $cssPath = public_path('css/dashboard.css');
        $cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
    @endphp

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ $cssVer }}">

    {{-- ✅ Por si alguna vista necesita CSS adicional (opcional) --}}
    @stack('styles')
</head>
<body>
    <header>
        <div class="logo">
            <img src="{{ asset('images/icons/logoSaute3.png') }}" alt="Logo">
        </div>

        <h2 class="titulo">@yield('titulo')</h2>

        <div class="perfil-container">
            {{--<div class="perfil">
                <img src="{{ asset('images/icons/user.png') }}" alt="Usuario">
            </div>--}}
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-cerrar">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <main>
        @yield('contenido')
    </main>

    {{-- ✅ Scripts por vista (opcional) --}}
    @stack('scripts')
</body>
</html>
