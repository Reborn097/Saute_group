<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\PrecioController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\AdminPedidoController;

/*
|--------------------------------------------------------------------------
| Web Routes
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


// =====================================================================================
// 🔹 RUTAS PROTEGIDAS (solo para usuarios autenticados)
// =====================================================================================
Route::middleware('auth')->group(function () {

    /* =====================================================================
     * PERFIL DE USUARIO
     * ===================================================================== */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    /* =====================================================================
     * CATEGORÍAS
     * ===================================================================== */
    Route::get('/dashboard/categorias/crear', [CategoriaController::class, 'crear'])
        ->name('dashboard.categorias.crear');

    Route::post('/dashboard/categorias/guardar', [CategoriaController::class, 'guardar'])
        ->name('dashboard.categorias.guardar');


    /* =====================================================================
     * PRODUCTOS
     * ===================================================================== */
    Route::get('/dashboard/productos',            [ProductoController::class, 'index'])->name('dashboard.productos');
    Route::get('/dashboard/productos/crear',      [ProductoController::class, 'crearProducto'])->name('dashboard.productos.crear');
    Route::post('/dashboard/productos/guardar',   [ProductoController::class, 'guardar'])->name('dashboard.productos.guardar');
    Route::get('/dashboard/productos/{id}/editar',[ProductoController::class, 'editar'])->name('dashboard.productos.editar');
    Route::put('/dashboard/productos/{id}/actualizar', [ProductoController::class, 'actualizar'])->name('dashboard.productos.actualizar');


    /* =====================================================================
     * PROVEEDORES
     * ===================================================================== */
    Route::get('/dashboard/proveedores',                [ProveedorController::class, 'index'])->name('dashboard.proveedores');
    Route::get('/dashboard/proveedores/crear',          [ProveedorController::class, 'crearProveedor'])->name('dashboard.proveedores.crear');
    Route::post('/dashboard/proveedores/guardar',       [ProveedorController::class, 'guardarProveedor'])->name('dashboard.proveedores.guardar');
    Route::get('/dashboard/proveedores/{id}/editar',    [ProveedorController::class, 'editarProveedor'])->name('dashboard.proveedores.editar');
    Route::put('/dashboard/proveedores/{id}/actualizar',[ProveedorController::class, 'actualizarProveedor'])->name('dashboard.proveedores.actualizar');
    Route::delete('/dashboard/proveedores/{id}',        [ProveedorController::class, 'destroy'])->name('dashboard.proveedores.eliminar');


    /* =====================================================================
     * PRECIOS
     * ===================================================================== */
    Route::get('/dashboard/precios', [PrecioController::class, 'index'])->name('dashboard.precios');

    Route::get('/dashboard/precios/{id}/editar', [PrecioController::class, 'editar'])
        ->name('producto_proveedor.editar_precio');

    Route::put('/dashboard/precios/{id}/actualizar', [PrecioController::class, 'actualizar'])
        ->name('producto_proveedor.actualizar_precio');


    /* =====================================================================
     * PEDIDOS (FLUJO NORMAL)
     * ===================================================================== */

    // Crear pedido
    Route::get('/dashboard/pedidos/crear',             [PedidoController::class, 'crear'])->name('dashboard.pedidos.solicitar');

    // Previsualizar
    Route::get('/dashboard/pedidos/previsualizar',     [PedidoController::class, 'previsualizar'])->name('dashboard.pedidos.previsualizar');

    // Guardar pedido
    Route::post('/dashboard/pedidos/guardar',          [PedidoController::class, 'guardar'])->name('dashboard.pedidos.guardar');

    // Consultar pedidos (solo visualización)
    Route::get('/dashboard/pedidos/consultar',         [PedidoController::class, 'consultar'])->name('dashboard.pedidos.consultar');

    // Ver detalle por ID
    Route::get('/dashboard/pedidos/visualizar/{id}',   [PedidoController::class, 'visualizar'])->name('dashboard.pedidos.visualizar');

    // Ver detalle por código
    Route::get('/dashboard/pedidos/detalle/{codigo}',  [PedidoController::class, 'detalle'])->name('dashboard.pedidos.detalle');



    /* =====================================================================
     * 🔥 ADMINISTRAR PEDIDOS (NUEVO, SEPARADO DEL FLUJO NORMAL)
     * ===================================================================== */

    Route::prefix('dashboard/pedidos/admin')->group(function () {

        // Listado administrativo
        Route::get('/', [AdminPedidoController::class, 'index'])
            ->name('dashboard.pedidos.admin');

        // Ver detalle administrable
        Route::get('/{codigo}', [AdminPedidoController::class, 'detalle'])
            ->name('dashboard.pedidos.admin.detalle');

        // Cambiar estado
        Route::post('/{codigo}/estado', [AdminPedidoController::class, 'cambiarEstado'])
            ->name('dashboard.pedidos.admin.estado');

        // Editar pedido completo
        Route::get('/{codigo}/editar', [AdminPedidoController::class, 'editar'])
            ->name('dashboard.pedidos.admin.editar');

        // Guardar cambios de edición
        Route::post('/{codigo}/actualizar', [AdminPedidoController::class, 'actualizar'])
            ->name('dashboard.pedidos.admin.actualizar');

        // Generar PDF
        Route::get('/{codigo}/pdf', [AdminPedidoController::class, 'generarPDF'])
            ->name('dashboard.pedidos.admin.pdf');
    });

});


// 🔹 Rutas de autenticación
require __DIR__ . '/auth.php';
