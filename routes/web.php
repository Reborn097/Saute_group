<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Aquí se registran todas las rutas web de tu aplicación.
| Estas rutas son cargadas por RouteServiceProvider dentro del grupo
| que contiene el middleware "web".
|--------------------------------------------------------------------------
*/

// 🔹 Redirige automáticamente al login
Route::get('/', function () {
    return redirect()->route('login');
});

// 🔹 Dashboard general (usuarios comunes si los hubiera)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 🔹 Panel principal del administrador
Route::get('/dashboard/admin', [AdminController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard.admin');

// 🔹 Rutas protegidas del perfil de usuario
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Formularios
    Route::get('/dashboard/agregar-categoria', [ProductoController::class, 'crearCategoria'])->name('dashboard.agregar.categoria');
    Route::get('/dashboard/agregar-producto', [ProductoController::class, 'crearProducto'])->name('dashboard.agregar.producto');
    Route::get('/dashboard/productos', [ProductoController::class, 'index'])->name('dashboard.productos');

    // Guardar datos
    Route::post('/dashboard/agregar-categoria', [ProductoController::class, 'guardarCategoria'])->name('dashboard.categorias.guardar');
    Route::post('/dashboard/agregar-producto', [ProductoController::class, 'guardar'])->name('dashboard.productos.guardar');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard/agregar-proveedor', [App\Http\Controllers\ProveedorController::class, 'crearProveedor'])->name('dashboard.agregar.proveedor');
    Route::post('/dashboard/proveedores/guardar', [App\Http\Controllers\ProveedorController::class, 'guardarProveedor'])->name('dashboard.proveedores.guardar');

    // Mostrar la lista de proveedores
    Route::get('/dashboard/proveedores', [App\Http\Controllers\ProveedorController::class, 'index'])->name('dashboard.proveedores');

    // Eliminar un proveedor
    Route::delete('/dashboard/proveedores/{id}', [App\Http\Controllers\ProveedorController::class, 'destroy'])->name('dashboard.proveedores.eliminar');

    Route::get('/dashboard/productos/{id}/editar', [ProductoController::class, 'editar'])->name('dashboard.editar.producto');

    Route::put('/dashboard/productos/{id}/actualizar', [ProductoController::class, 'actualizar'])->name('dashboard.actualizar.producto');

});




// 🔹 Rutas de autenticación (login, logout, registro, etc.)
require __DIR__ . '/auth.php';
