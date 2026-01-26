@extends('layouts.dashboard')

@section('titulo', 'Inicio')

@section('contenido')

@php
    $role = auth()->user()->role ?? '';

    // =========================
    // FLAGS útiles
    // =========================
    $esAdmin = ($role === 'admin');

    // Registro operativo: SOLO si realmente tiene algo que mostrar
    $puedeVerComensales = in_array($role, ['admin','encargado_cocina'], true);
    $puedeVerCorteCaja  = in_array($role, ['admin','encargado_cafeteria'], true);
    $mostrarRegistroOperativo = ($puedeVerComensales || $puedeVerCorteCaja);

    // Reportes: admin y CEO
    $mostrarReportes = in_array($role, ['admin','ceo'], true);

    // Pedidos: TODOS menos proveedor
    $mostrarPedidos = in_array($role, [
        'admin','ceo','encargado_cocina','encargado_cafeteria','almacenista','encargado_pedidos'
    ], true);

    // Inventarios: admin + encargados
    $mostrarInventarios = in_array($role, ['admin','encargado_cocina','encargado_cafeteria'], true);

    // Proveedores/Precios: admin y ceo
    $mostrarProveedoresPrecios = in_array($role, ['admin','ceo'], true);

    // Mis precios: SOLO proveedor
    $mostrarMisPreciosProveedor = ($role === 'proveedor');
@endphp

<style>
    .acordeon { width: 90%; margin: 0 auto; max-width: 1100px; }
    .acordeon-item { background: #f4d7b8; border-radius: 12px; margin-bottom: 15px; overflow: hidden; box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
    .acordeon-titulo { padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem; font-weight: bold; color: #a13c2f; font-family: 'Poppins'; }
    .acordeon-titulo:hover { background: #eec7a3; }
    .acordeon-contenido { display: none; padding: 15px 10px 25px; background: #fde7d2; }
    .grupo-opciones { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }

    .tarjeta{
        background-color:#f9e3cc;
        width:160px;
        height:160px;
        text-align:center;
        border-radius:14px;
        box-shadow:0 4px 8px rgba(0,0,0,0.15);
        transition:transform 0.2s, box-shadow 0.3s;
        cursor:pointer;
        padding:12px;
    }
    .tarjeta img{ width:68px; height:68px; margin-top:5px; object-fit:contain; }
    .tarjeta:hover{ transform:translateY(-4px); box-shadow:0 6px 16px rgba(0,0,0,0.25); }
    .tarjeta p{ font-size:0.9em; color:#333; margin-top:8px; font-family:'Poppins'; }

    .icono{ font-size:1.4rem; transition:0.3s; }
</style>

<div class="acordeon">

    {{-- =========================
        PEDIDOS (🚫 proveedor NO)
    ========================= --}}
    @if($mostrarPedidos)
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
            Pedidos
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">

                @php
                    // Admin y encargado_pedidos -> administrar
                    // CEO -> bandeja admin (si tienes bandeja CEO, cámbiala aquí)
                    // demás -> consultar (mis pedidos)
                    if (in_array($role, ['admin','encargado_pedidos'], true)) {
                        $rutaPedidos = route('dashboard.pedidos.admin');
                    } elseif ($role === 'ceo') {
                        $rutaPedidos = route('dashboard.pedidos.admin'); // si tienes bandeja CEO: cámbiala aquí
                    } else {
                        $rutaPedidos = route('dashboard.pedidos.consultar');
                    }
                @endphp

                {{-- Encargados: administrar pedidos --}}
                @if(in_array($role, ['encargado_cocina','encargado_cafeteria','ceo'], true))
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.admin') }}'">
                    <img src="{{ asset('images/icons/iconos/administrar_pedidos.png') }}">
                    <p><b>Administrar pedidos</b></p>
                </div>
                @endif

                {{-- Solicitar pedido (encargados + admin) --}}
                @if(in_array($role, ['encargado_cocina','encargado_cafeteria','admin'], true))
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.solicitar') }}'">
                    <img src="{{ asset('images/icons/iconos/solicitar_pedido.png') }}">
                    <p><b>Solicitar pedido</b></p>
                </div>
                @endif

                {{-- Solicitar pedido diario (encargados + admin) --}}
                @if(in_array($role, ['encargado_cocina','admin'], true))
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.tortilla.create') }}'">
                    <img src="{{ asset('images/icons/iconos/tortilla.png') }}">
                    <p><b>Solicitar pedido Pan/Tortilla</b></p>
                </div>
                @endif

                {{-- Pedido especial (solo cocina + admin) --}}
                @if(in_array($role, ['encargado_cocina','admin'], true))
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.especial.crear') }}'">
                    <img src="{{ asset('images/icons/iconos/pedido_especial.png') }}">
                    <p><b>Solicitar Pedido especial</b></p>
                </div>
                @endif

                {{-- Admin: administrar pedidos --}}
                @if($role === 'admin')
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.admin') }}'">
                    <img src="{{ asset('images/icons/iconos/administrar_pedidos.png') }}">
                    <p><b>Administrar pedidos</b></p>
                </div>
                @endif

            </div>
        </div>
    </div>
    @endif


    {{-- =========================
        REPORTES (✅ Admin + CEO)
        (lo dejamos aquí arriba para que el CEO lo vea fácil)
    ========================= --}}
    @if($mostrarReportes)
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
            Reportes
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.reportes') }}'">
                    <img src="{{ asset('images/icons/iconos/Reportes.png') }}">
                    <p><b>Reportes</b></p>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- =========================
        INVENTARIOS (Admin + Encargados)
    ========================= --}}
    @if($mostrarInventarios)
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
            Inventarios
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">
                <div class="tarjeta" onclick="window.location.href='{{ route('inventarios.index') }}'">
                    <img src="{{ asset('images/icons/iconos/administrar_inventario.png') }}">
                    <p><b>Inventario</b></p>
                </div>
            </div>
        </div>
    </div>
    @endif


    {{-- =========================
        MIS PRECIOS (PROVEEDOR)
        ✅ SOLO 2 botones (sin historial/comparativa)
        ✅ SOLO existe UNA vez (evita duplicados)
    ========================= --}}
    @if($mostrarMisPreciosProveedor)
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
            Mis precios
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.precios') }}'">
                    <img src="{{ asset('images/icons/iconos/comparativa_precios.png') }}">
                    <p><b>Administrar precios</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('precios.form_excel') }}'">
                    <img src="{{ asset('images/icons/iconos/excel.png') }}">
                    <p><b>Actualizar por Excel</b></p>
                </div>

            </div>
        </div>
    </div>
    @endif


    {{-- =========================
        PROVEEDORES Y PRECIOS (admin y ceo)
        ✅ proveedor NO entra aquí
    ========================= --}}
    @if($mostrarProveedoresPrecios)
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
            Proveedores y Precios
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">

                @if($role === 'admin')
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.proveedores') }}'">
                    <img src="{{ asset('images/icons/iconos/administrar_proveedor.png') }}">
                    <p><b>Proveedores</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.proveedores.crear') }}'">
                    <img src="{{ asset('images/icons/iconos/registro_proveedor.png') }}">
                    <p><b>Registrar proveedor</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.precios') }}'">
                    <img src="{{ asset('images/icons/iconos/comparativa_precios.png') }}">
                    <p><b>Administrar precios</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('precios.form_excel') }}'">
                    <img src="{{ asset('images/icons/iconos/excel.png') }}">
                    <p><b>Actualizar Excel</b></p>
                </div>
                @endif

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.precios.comparativa') }}'">
                    <img src="{{ asset('images/icons/iconos/comparativa_precios.png') }}">
                    <p><b>Comparativa precios</b></p>
                </div>

            </div>
        </div>
    </div>
    @endif


    {{-- =========================
        REGISTRO OPERATIVO
        ✅ Solo si tiene opciones (evita acordeón vacío)
        ✅ CEO ya NO lo ve vacío
    ========================= --}}
    @if($mostrarRegistroOperativo)
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
            Registro operativo
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">

                @if($puedeVerComensales)
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.comensales') }}'">
                    <img src="{{ asset('images/icons/iconos/registro_comensales.png') }}">
                    <p><b>Registrar comensales</b></p>
                </div>
                @endif

                @if($puedeVerCorteCaja)
                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.corte-caja') }}'">
                    <img src="{{ asset('images/icons/iconos/corte_caja.png') }}">
                    <p><b>Corte de caja</b></p>
                </div>
                @endif

            </div>
        </div>
    </div>
    @endif


    {{-- =========================
        ADMINISTRACIÓN DEL SISTEMA (solo admin)
    ========================= --}}
    @if($role === 'admin')
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
            Administración del sistema
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">
                <div class="tarjeta" onclick="window.location.href='{{ route('usuarios.index') }}'">
                    <img src="{{ asset('images/icons/iconos/agregar_usuario.png') }}">
                    <p><b>Usuarios</b></p>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
function toggleAcordeon(titulo) {
    const contenido = titulo.nextElementSibling;
    const icono = titulo.querySelector('.icono');

    if (contenido.style.display === "block") {
        contenido.style.display = "none";
        icono.textContent = "＋";
    } else {
        contenido.style.display = "block";
        icono.textContent = "－";
    }
}
</script>

@endsection
