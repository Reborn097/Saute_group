@extends('layouts.dashboard')

@section('titulo', 'Menú principal')

@section('contenido')

<style>
    .acordeon{
        width:100%;
        margin:0 auto;
        max-width:1100px;
    }

    .acordeon-item{
        background:#f4d7b8;
        border-radius:12px;
        margin-bottom:15px;
        overflow:hidden;
        box-shadow:0 3px 8px rgba(0,0,0,0.15);
    }

    .acordeon-titulo{
        padding:14px 16px;
        cursor:pointer;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        font-size:1.1rem;
        font-weight:700;
        color:#a13c2f;
        font-family:'Poppins';
        user-select:none;
    }

    .acordeon-titulo:hover{
        background:#eec7a3;
    }

    /* cerrado por defecto */
    .acordeon-contenido{
        display:none;
        padding:14px 12px 20px;
        background:#fde7d2;
    }

    /* abierto */
    .acordeon-item.abierto .acordeon-contenido{
        display:block;
    }

    /* ✅ GRID RESPONSIVE */
    .grupo-opciones{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));
        gap:14px;
        align-items:stretch;
        justify-items:center;
    }

    .tarjeta{
        background-color:#f9e3cc;
        width:100%;
        max-width:190px;
        min-height:150px;
        text-align:center;
        border-radius:14px;
        box-shadow:0 4px 8px rgba(0,0,0,0.15);
        transition:transform 0.2s, box-shadow 0.3s;
        cursor:pointer;
        padding:12px;

        display:flex;
        flex-direction:column;
        justify-content:center;
    }

    .tarjeta img{
        width:64px;
        height:64px;
        margin:4px auto 0;
        object-fit:contain;
    }

    .tarjeta:hover{
        transform:translateY(-3px);
        box-shadow:0 6px 16px rgba(0,0,0,0.25);
    }

    .tarjeta p{
        font-size:0.9rem;
        color:#333;
        margin:10px 0 0;
        font-family:'Poppins';
        line-height:1.15;
    }

    .icono{
        font-size:1.4rem;
        transition:0.2s;
        flex-shrink:0;
    }

    /* ===========================
       RESPONSIVE
       =========================== */

    /* Tablet */
    @media (max-width:1024px){
        .grupo-opciones{
            grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));
        }
        .tarjeta{ max-width:220px; }
    }

    /* Celular */
    @media (max-width:600px){
        .acordeon-titulo{
            font-size:1rem;
            padding:12px 14px;
        }

        .acordeon-contenido{
            padding:12px 10px 16px;
        }

        /* ✅ 2 columnas en móvil */
        .grupo-opciones{
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:12px;
        }

        .tarjeta{
            max-width:none;
            min-height:140px;
            padding:10px;
        }

        .tarjeta img{
            width:58px;
            height:58px;
        }

        .tarjeta p{
            font-size:0.86rem;
        }
    }

    /* muy pequeño: 1 columna */
    @media (max-width:380px){
        .grupo-opciones{
            grid-template-columns:1fr;
        }
    }
</style>



<div class="acordeon">

    {{-- ===================================================== --}}
    {{-- 1️⃣ INVENTARIOS Y PRODUCTOS --}}
    {{-- ===================================================== --}}
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
             Inventarios y Productos
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">
                
                <div class="tarjeta" onclick="window.location.href='{{ route('inventarios.index') }}'">
                    <img src="{{ asset('images/icons/iconos/administrar_inventario.png') }}">
                    <p><b>Inventario</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.productos') }}'">
                    <img src="{{ asset('images/icons/iconos/administrar_producto.png') }}">
                    <p><b>Productos</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('unidades.index') }}'">
                    <img src="{{ asset('images/icons/iconos/unidades.png') }}">
                    <p><b>Unidades operativas</b></p>
                </div>

            </div>
        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- 2️⃣ PROVEEDORES Y PRECIOS --}}
    {{-- ===================================================== --}}
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
             Proveedores y Precios
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">

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

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.precios.comparativa') }}'">
                    <img src="{{ asset('images/icons/iconos/comparativa_precios.png') }}">
                    <p><b>Comparativa precios</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('precios.form_excel') }}'">
                    <img src="{{ asset('images/icons/iconos/excel.png') }}">
                    <p><b>Actualizar por Excel</b></p>
                </div>

            </div>
        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- 3️⃣ PEDIDOS --}}
    {{-- ===================================================== --}}
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
             Pedidos
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.admin') }}'">
                    <img src="{{ asset('images/icons/iconos/administrar_pedidos.png') }}">
                    <p><b>Administrar pedidos</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.consultar') }}'">
                    <img src="{{ asset('images/icons/iconos/consultar_pedidos.png') }}">
                    <p><b>Consultar pedidos</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.solicitar') }}'">
                    <img src="{{ asset('images/icons/iconos/solicitar_pedido.png') }}">
                    <p><b>Solicitar pedido</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos.especial.crear') }}'">
                    <img src="{{ asset('images/icons/iconos/pedido_especial.png') }}">
                    <p><b>Solicitar Pedido especial</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.tortilla.create') }}'">
                    <img src="{{ asset('images/icons/iconos/tortilla.png') }}">
                    <p><b>Solicitar Pedido Pan/Tortilla</b></p>
                </div>

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.pedidos_diarios.index') }}'">
                    <img src="{{ asset('images/icons/iconos/tortilla_ad.png') }}">
                    <p><b>Administrar pedidos Pan/tortilla</b></p>
                </div>

            </div>
        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- 4️⃣ COMENSALES Y CAJA --}}
    {{-- ===================================================== --}}
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
             Comensales y Caja
            <span class="icono">＋</span>
        </div>

        <div class="acordeon-contenido">
            <div class="grupo-opciones">

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.comensales') }}'">
                    <img src="{{ asset('images/icons/iconos/registro_comensales.png') }}">
                    <p><b>Registrar comensales</b></p>
                </div>


                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.corte-caja') }}'">
                    <img src="{{ asset('images/icons/iconos/corte_caja.png') }}">
                    <p><b>Corte de caja</b></p>
                </div>

            </div>
        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- 5️⃣ ADMINISTRACIÓN DEL SISTEMA --}}
    {{-- ===================================================== --}}
    <div class="acordeon-item">
        <div class="acordeon-titulo" onclick="toggleAcordeon(this)">
             Administración del sistema
            <span class="icono">＋</span>
        </div>


        <div class="acordeon-contenido">
            <div class="grupo-opciones">

                <div class="tarjeta" onclick="window.location.href='{{ route('dashboard.reportes') }}'">
                    <img src="{{ asset('images/icons/iconos/Reportes.png') }}">
                    <p><b>Reportes</b></p>
                </div>


                <div class="tarjeta" onclick="window.location.href='{{ route('usuarios.index') }}'">
                    <img src="{{ asset('images/icons/iconos/agregar_usuario.png') }}">
                    <p><b>Administrar usuarios</b></p>
                </div>

            </div>
        
        </div>
    </div>

</div>



{{-- ============================================================== --}}
{{-- SCRIPT DE ACORDEÓN --}}
{{-- ============================================================== --}}
<script>
function toggleAcordeon(titulo) {
    const item = titulo.closest('.acordeon-item');
    const icono = titulo.querySelector('.icono');

    const abierto = item.classList.toggle('abierto');
    icono.textContent = abierto ? '－' : '＋';
}
</script>


@endsection
