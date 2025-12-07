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
use App\Http\Controllers\UnidadOperativaController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CorteCajaController;
use App\Http\Controllers\PedidoEspecialController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Página inicial → login
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Panel admin
Route::get('/dashboard/admin', [AdminController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard.admin');


// ============================================================================
// 🔐 RUTAS PROTEGIDAS (REQUIEREN LOGIN)
// ============================================================================
Route::middleware('auth')->group(function () {

    /* =============================================
     * PERFIL
     * ============================================= */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    /* =============================================
     * CATEGORÍAS
     * ============================================= */
    Route::get('/dashboard/categorias/crear', [CategoriaController::class, 'crear'])
        ->name('dashboard.categorias.crear');

    Route::post('/dashboard/categorias/guardar', [CategoriaController::class, 'guardar'])
        ->name('dashboard.categorias.guardar');


    /* =============================================
     * PRODUCTOS
     * ============================================= */
    Route::get('/dashboard/productos', [ProductoController::class, 'index'])->name('dashboard.productos');
    Route::get('/dashboard/productos/crear', [ProductoController::class, 'crearProducto'])->name('dashboard.productos.crear');
    Route::post('/dashboard/productos/guardar', [ProductoController::class, 'guardar'])->name('dashboard.productos.guardar');
    Route::get('/dashboard/productos/{id}/editar', [ProductoController::class, 'editar'])->name('dashboard.productos.editar');
    Route::put('/dashboard/productos/{id}/actualizar', [ProductoController::class, 'actualizar'])->name('dashboard.productos.actualizar');


    /* =============================================
     * PROVEEDORES
     * ============================================= */
    Route::get('/dashboard/proveedores', [ProveedorController::class, 'index'])->name('dashboard.proveedores');
    Route::get('/dashboard/proveedores/crear', [ProveedorController::class, 'crearProveedor'])->name('dashboard.proveedores.crear');
    Route::post('/dashboard/proveedores/guardar', [ProveedorController::class, 'guardarProveedor'])->name('dashboard.proveedores.guardar');
    Route::get('/dashboard/proveedores/{id}/editar', [ProveedorController::class, 'editarProveedor'])->name('dashboard.proveedores.editar');
    Route::put('/dashboard/proveedores/{id}/actualizar', [ProveedorController::class, 'actualizarProveedor'])->name('dashboard.proveedores.actualizar');
    Route::delete('/dashboard/proveedores/{id}', [ProveedorController::class, 'destroy'])->name('dashboard.proveedores.eliminar');


    /* =============================================
     * PRECIOS
     * ============================================= */
    Route::get('/dashboard/precios', [PrecioController::class, 'index'])->name('dashboard.precios');
    Route::get('/dashboard/precios/{id}/editar', [PrecioController::class, 'editar'])->name('producto_proveedor.editar_precio');
    Route::put('/dashboard/precios/{id}/actualizar', [PrecioController::class, 'actualizar'])->name('producto_proveedor.actualizar_precio');
    Route::get('/dashboard/precios/comparativa', [PrecioController::class, 'comparativaPrecios'])->name('dashboard.precios.comparativa');


    /* =============================================
     * PEDIDOS (FLUJO NORMAL)
     * ============================================= */

    Route::get('/dashboard/pedidos/crear', [PedidoController::class, 'crear'])->name('dashboard.pedidos.solicitar');
    Route::get('/dashboard/pedidos/previsualizar', [PedidoController::class, 'previsualizar'])->name('dashboard.pedidos.previsualizar');
    Route::post('/dashboard/pedidos/guardar', [PedidoController::class, 'guardar'])->name('dashboard.pedidos.guardar');
    Route::get('/dashboard/pedidos/consultar', [PedidoController::class, 'consultar'])->name('dashboard.pedidos.consultar');
    Route::get('/dashboard/pedidos/visualizar/{id}', [PedidoController::class, 'visualizar'])->name('dashboard.pedidos.visualizar');
    Route::get('/dashboard/pedidos/detalle/{codigo}', [PedidoController::class, 'detalle'])->name('dashboard.pedidos.detalle');


    /* =============================================
     * ADMINISTRAR PEDIDOS
     * ============================================= */
    Route::prefix('dashboard/pedidos/admin')->group(function () {

        Route::get('/', [AdminPedidoController::class, 'index'])->name('dashboard.pedidos.admin');

        Route::get('/{codigo}', [AdminPedidoController::class, 'detalle'])
            ->name('dashboard.pedidos.admin.detalle');

        Route::post('/{codigo}/estado', [AdminPedidoController::class, 'cambiarEstado'])
            ->name('dashboard.pedidos.admin.estado');

        Route::get('/{codigo}/editar', [AdminPedidoController::class, 'editar'])
            ->name('dashboard.pedidos.admin.editar');

        Route::post('/{codigo}/actualizar', [AdminPedidoController::class, 'actualizar'])
            ->name('dashboard.pedidos.admin.actualizar');

        Route::get('/{codigo}/pdf', [AdminPedidoController::class, 'generarPDF'])
            ->name('dashboard.pedidos.admin.pdf');
    });


    /* =============================================
     * PEDIDOS ESPECIALES
     * ============================================= */
    Route::prefix('dashboard/pedidos/especial')->group(function () {

        Route::get('/crear', [PedidoEspecialController::class, 'crear'])
            ->name('dashboard.pedidos.especial.crear');

        Route::get('/previsualizar', [PedidoEspecialController::class, 'previsualizar'])
            ->name('dashboard.pedidos.especial.previsualizar');

        Route::post('/guardar', [PedidoEspecialController::class, 'guardar'])
            ->name('dashboard.pedidos.especial.guardar');
    });


    /* =============================================
     * CORTE DE CAJA (NUEVO MÓDULO)
     * ============================================= */
    Route::prefix('dashboard/corte-caja')->group(function () {

        // Vista principal
        Route::get('/', [CorteCajaController::class, 'index'])
            ->name('dashboard.corte-caja');

        // Registrar o actualizar un día
        Route::post('/guardar', [CorteCajaController::class, 'guardar'])
            ->name('dashboard.corte-caja.guardar');

        // Obtener los datos del mes (para auto-llenar tabla)
        Route::get('/datos/{anio}/{mes}/{local}', [CorteCajaController::class, 'obtenerDatos'])
            ->name('dashboard.corte-caja.datos');
    });

});



/* =============================================
 * UNIDADES OPERATIVAS
 * ============================================= */
Route::prefix('dashboard/unidades')->group(function () {

    Route::get('/', [UnidadOperativaController::class, 'index'])->name('unidades.index');
    Route::get('/crear', [UnidadOperativaController::class, 'create'])->name('unidades.create');
    Route::post('/guardar', [UnidadOperativaController::class, 'store'])->name('unidades.store');

    Route::get('/{id}/editar', [UnidadOperativaController::class, 'edit'])->name('unidades.edit');
    Route::put('/{id}/actualizar', [UnidadOperativaController::class, 'update'])->name('unidades.update');

    Route::delete('/{id}/eliminar', [UnidadOperativaController::class, 'destroy'])->name('unidades.destroy');
});


/* =============================================
 * ALMACENES
 * ============================================= */
Route::prefix('dashboard/unidades/{unidad_id}/almacenes')->group(function () {

    Route::get('/', [AlmacenController::class, 'index'])->name('almacenes.index');
    Route::get('/crear', [AlmacenController::class, 'create'])->name('almacenes.create');
    Route::post('/crear', [AlmacenController::class, 'store'])->name('almacenes.store');
    Route::get('/{almacen_id}/editar', [AlmacenController::class, 'edit'])->name('almacenes.edit');
    Route::put('/{almacen_id}/actualizar', [AlmacenController::class, 'update'])->name('almacenes.update');
    Route::delete('/{almacen_id}/eliminar', [AlmacenController::class, 'destroy'])->name('almacenes.destroy');
});


// AUTH
require __DIR__ . '/auth.php';
