<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\PrecioController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProveedorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Aquí se registran todas las rutas web de tu aplicación.
| Estas rutas son cargadas por RouteServiceProvider dentro del grupo
| que contiene el middleware "web".
|--------------------------------------------------------------------------
*/

// 🔹 Página inicial → redirige al login
Route::get('/', function () {
    return redirect()->route('login');
});

// 🔹 Dashboard general (usuarios verificados)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 🔹 Panel principal del administrador
Route::get('/dashboard/admin', [AdminController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard.admin');

// 🔹 Grupo de rutas protegidas por autenticación
Route::middleware('auth')->group(function () {

    /* ================================
     * PERFIL DE USUARIO
     * ================================ */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* ================================
     * CATEGORÍAS
     * ================================ */
    Route::get('/dashboard/categorias', [CategoriaController::class, 'index'])
        ->name('dashboard.categorias');
    Route::get('/dashboard/categorias/crear', [CategoriaController::class, 'crear'])
        ->name('dashboard.categorias.crear');
    Route::post('/dashboard/categorias/guardar', [CategoriaController::class, 'guardar'])
        ->name('dashboard.categorias.guardar');

    /* ================================
     * PRODUCTOS
     * ================================ */
    Route::get('/dashboard/productos', [ProductoController::class, 'index'])
        ->name('dashboard.productos');
    Route::get('/dashboard/productos/crear', [ProductoController::class, 'crearProducto'])
        ->name('dashboard.productos.crear');
    Route::post('/dashboard/productos/guardar', [ProductoController::class, 'guardar'])
        ->name('dashboard.productos.guardar');
    Route::get('/dashboard/productos/{id}/editar', [ProductoController::class, 'editar'])
        ->name('dashboard.productos.editar');
    Route::put('/dashboard/productos/{id}/actualizar', [ProductoController::class, 'actualizar'])
        ->name('dashboard.productos.actualizar');

    /* ================================
     * PROVEEDORES
     * ================================ */
    Route::get('/dashboard/proveedores', [ProveedorController::class, 'index'])
        ->name('dashboard.proveedores');
    Route::get('/dashboard/proveedores/crear', [ProveedorController::class, 'crearProveedor'])
        ->name('dashboard.proveedores.crear');
    Route::post('/dashboard/proveedores/guardar', [ProveedorController::class, 'guardarProveedor'])
        ->name('dashboard.proveedores.guardar');
    Route::get('/dashboard/proveedores/{id}/editar', [ProveedorController::class, 'editarProveedor'])
        ->name('dashboard.proveedores.editar');
    Route::put('/dashboard/proveedores/{id}/actualizar', [ProveedorController::class, 'actualizarProveedor'])
        ->name('dashboard.proveedores.actualizar');
    Route::delete('/dashboard/proveedores/{id}', [ProveedorController::class, 'destroy'])
        ->name('dashboard.proveedores.eliminar');

    /* ================================
     * PRECIOS
     * ================================ */
    Route::get('/dashboard/precios', [PrecioController::class, 'index'])
        ->name('dashboard.precios');
    Route::get('/dashboard/precios/{id}/editar', [PrecioController::class, 'editar'])
        ->name('producto_proveedor.editar_precio');
    Route::put('/dashboard/precios/{id}/actualizar', [PrecioController::class, 'actualizar'])
        ->name('producto_proveedor.actualizar_precio');

    /* ================================
     * PEDIDOS
     * ================================ */
    // Crear pedido
    Route::get('/dashboard/pedidos/crear', [PedidoController::class, 'crear'])
        ->name('dashboard.pedidos.solicitar');

    // Previsualización antes de confirmar
    Route::get('/dashboard/pedidos/previsualizar', [PedidoController::class, 'previsualizar'])
        ->name('dashboard.pedidos.previsualizar');

    // Guardar pedido confirmado (desde previsualizar_pedido)
    Route::post('/dashboard/pedidos/guardar', [PedidoController::class, 'guardar'])
        ->name('dashboard.pedidos.guardar');

    // Consultar todos los pedidos
    Route::get('/dashboard/pedidos/consultar', [PedidoController::class, 'consultar'])
        ->name('dashboard.pedidos.consultar');

    // Visualizar un pedido específico
    Route::get('/dashboard/pedidos/visualizar/{id}', [PedidoController::class, 'visualizar'])
        ->name('dashboard.pedidos.visualizar');

    // Ver detalle completo por código (con productos, proveedor y categoría)
    Route::get('/dashboard/pedidos/detalle/{codigo}', [PedidoController::class, 'detalle'])
        ->name('dashboard.pedidos.detalle');
});

// 🔹 Rutas de autenticación (login, logout, registro, etc.)
require __DIR__ . '/auth.php';
