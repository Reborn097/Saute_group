<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role;

        // Opciones por rol usando RUTAS REALES de tu proyecto
        $menus = [

            // ADMIN (tu panel principal actual)
            'admin' => [
                [
                    'titulo' => 'Menú Admin',
                    'icono'  => 'images/icons/iconos/menu.png',
                    'route'  => 'dashboard.admin',
                    'desc'   => 'Panel principal',
                ],
                [
                    'titulo' => 'Usuarios',
                    'icono'  => 'images/icons/iconos/agregar_usuario.png',
                    'route'  => 'usuarios.index',
                    'desc'   => 'Alta, edición y control',
                ],
                [
                    'titulo' => 'Productos',
                    'icono'  => 'images/icons/iconos/productos.png',
                    'route'  => 'dashboard.productos',
                    'desc'   => 'Catálogo',
                ],
                [
                    'titulo' => 'Proveedores',
                    'icono'  => 'images/icons/iconos/proveedor.png',
                    'route'  => 'dashboard.proveedores',
                    'desc'   => 'Gestión de proveedores',
                ],
                [
                    'titulo' => 'Precios',
                    'icono'  => 'images/icons/iconos/precios.png',
                    'route'  => 'dashboard.precios.comparativa',
                    'desc'   => 'Comparativa actual vs anterior',
                ],
                [
                    'titulo' => 'Pedidos (admin)',
                    'icono'  => 'images/icons/iconos/pedidos.png',
                    'route'  => 'dashboard.pedidos.admin',
                    'desc'   => 'Administrar pedidos',
                ],
                [
                    'titulo' => 'Unidades operativas',
                    'icono'  => 'images/icons/iconos/unidades.png',
                    'route'  => 'unidades.index',
                    'desc'   => 'Gestionar unidades',
                ],
                [
                    'titulo' => 'Corte de caja',
                    'icono'  => 'images/icons/iconos/caja.png',
                    'route'  => 'dashboard.corte-caja',
                    'desc'   => 'Registrar y consultar',
                ],
            ],

            // CEO (con lo que ya tienes hoy)
            'ceo' => [
                [
                    'titulo' => 'Comparativa de precios',
                    'icono'  => 'images/icons/iconos/precios.png',
                    'route'  => 'dashboard.precios.comparativa',
                    'desc'   => 'Cambios de costo',
                ],
                [
                    'titulo' => 'Pedidos (consulta)',
                    'icono'  => 'images/icons/iconos/pedidos.png',
                    'route'  => 'dashboard.pedidos.consultar',
                    'desc'   => 'Ver pedidos',
                ],
                [
                    'titulo' => 'Corte de caja',
                    'icono'  => 'images/icons/iconos/caja.png',
                    'route'  => 'dashboard.corte-caja',
                    'desc'   => 'Revisar información',
                ],
            ],

            // Encargados (cocina / cafetería)
            'encargado_cocina' => [
                [
                    'titulo' => 'Solicitar pedido',
                    'icono'  => 'images/icons/iconos/pedidos.png',
                    'route'  => 'dashboard.pedidos.solicitar',
                    'desc'   => 'Crear pedido',
                ],
                [
                    'titulo' => 'Mis pedidos',
                    'icono'  => 'images/icons/iconos/consultar.png',
                    'route'  => 'dashboard.pedidos.consultar',
                    'desc'   => 'Consultar pedidos',
                ],
                [
                    'titulo' => 'Pedido especial',
                    'icono'  => 'images/icons/iconos/especial.png',
                    'route'  => 'dashboard.pedidos.especial.crear',
                    'desc'   => 'Crear pedido especial',
                ],
            ],

            'encargado_cafeteria' => [
                [
                    'titulo' => 'Solicitar pedido',
                    'icono'  => 'images/icons/iconos/pedidos.png',
                    'route'  => 'dashboard.pedidos.solicitar',
                    'desc'   => 'Crear pedido',
                ],
                [
                    'titulo' => 'Mis pedidos',
                    'icono'  => 'images/icons/iconos/consultar.png',
                    'route'  => 'dashboard.pedidos.consultar',
                    'desc'   => 'Consultar pedidos',
                ],
            ],

            'responsable_de_unidades' => [
                [
                    'titulo' => 'Solicitar pedido',
                    'icono'  => 'images/icons/iconos/pedidos.png',
                    'route'  => 'dashboard.pedidos.solicitar',
                    'desc'   => 'Crear pedido',
                ],
                [
                    'titulo' => 'Mis pedidos',
                    'icono'  => 'images/icons/iconos/consultar.png',
                    'route'  => 'dashboard.pedidos.consultar',
                    'desc'   => 'Consultar pedidos',
                ],
            ],

            // Almacenista (con lo que existe hoy)
            'almacenista' => [
                [
                    'titulo' => 'Pedidos (consulta)',
                    'icono'  => 'images/icons/iconos/pedidos.png',
                    'route'  => 'dashboard.pedidos.consultar',
                    'desc'   => 'Ver pedidos',
                ],
                [
                    'titulo' => 'Corte de caja',
                    'icono'  => 'images/icons/iconos/caja.png',
                    'route'  => 'dashboard.corte-caja',
                    'desc'   => 'Registrar/consultar',
                ],
            ],

            // Proveedor (si todavía no tienes rutas específicas, lo dejamos en consulta)
            'proveedor' => [
                [
                    'titulo' => 'Pedidos (consulta)',
                    'icono'  => 'images/icons/iconos/pedidos.png',
                    'route'  => 'dashboard.pedidos.consultar',
                    'desc'   => 'Ver pedidos',
                ],
                [
                    'titulo' => 'Precios',
                    'icono'  => 'images/icons/iconos/precios.png',
                    'route'  => 'dashboard.precios',
                    'desc'   => 'Ver precios',
                ],
            ],
        ];

        $opciones = $menus[$role] ?? [];

        return view('dashboard.home', compact('opciones', 'user'));
    }
}
