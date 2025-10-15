<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductoController;

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
    Route::get('/dashboard/productos', [ProductoController::class, 'index'])->name('dashboard.productos');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 🔹 Rutas de autenticación (login, logout, registro, etc.)
require __DIR__ . '/auth.php';
