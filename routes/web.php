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
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ComensalesController;
use App\Http\Controllers\ReportesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Página inicial → login
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard shortcut
Route::get('/dashboard', function () {
    return redirect()->route('dashboard.home');
})->middleware(['auth', 'verified'])->name('dashboard');

// Panel admin
Route::get('/dashboard/admin', [AdminController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard.admin');


// ============================================================================
// 🔐 RUTAS PROTEGIDAS (REQUIEREN LOGIN)
// ============================================================================
Route::middleware('auth')->group(function () {

    Route::get('/dashboard/home', [HomeController::class, 'index'])
        ->name('dashboard.home');

    // PERFIL
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CATEGORÍAS
    Route::get('/dashboard/categorias/crear', [CategoriaController::class, 'crear'])
        ->name('dashboard.categorias.crear');
    Route::post('/dashboard/categorias/guardar', [CategoriaController::class, 'guardar'])
        ->name('dashboard.categorias.guardar');

    // PRODUCTOS
    Route::get('/dashboard/productos', [ProductoController::class, 'index'])->name('dashboard.productos');
    Route::get('/dashboard/productos/crear', [ProductoController::class, 'crearProducto'])->name('dashboard.productos.crear');
    Route::post('/dashboard/productos/guardar', [ProductoController::class, 'guardar'])->name('dashboard.productos.guardar');
    Route::get('/dashboard/productos/{id}/editar', [ProductoController::class, 'editar'])->name('dashboard.productos.editar');
    Route::put('/dashboard/productos/{id}/actualizar', [ProductoController::class, 'actualizar'])->name('dashboard.productos.actualizar');

    // =====================================================================
    // ✅ PROVEEDORES (LISTA + CRUD + CUENTA + TARJETAS)
    // =====================================================================

    // Lista
    Route::get('/dashboard/proveedores', [ProveedorController::class, 'index'])
        ->name('dashboard.proveedores');

    // Resto de acciones con prefijo + nombres
    Route::prefix('dashboard/proveedores')->name('dashboard.proveedores.')->group(function () {

        Route::get('/crear', [ProveedorController::class, 'crear'])->name('crear');
        Route::post('/guardar', [ProveedorController::class, 'guardar'])->name('guardar');

        Route::get('/{id}/editar', [ProveedorController::class, 'editar'])->name('editar');
        Route::put('/{id}/actualizar', [ProveedorController::class, 'actualizar'])->name('actualizar');

        Route::delete('/{id}', [ProveedorController::class, 'destroy'])->name('eliminar');

        // CUENTA / TARJETAS registradas
        Route::get('/{id}/cuenta', [ProveedorController::class, 'cuenta'])->name('cuenta');

        // TARJETAS (flujo temporal en sesión)
        Route::post('/tarjetas/crear', [ProveedorController::class, 'tarjetaCrear'])->name('tarjetas.crear');
        Route::post('/tarjetas/guardar', [ProveedorController::class, 'tarjetaGuardar'])->name('tarjetas.guardar');
        Route::post('/tarjetas/eliminar', [ProveedorController::class, 'tarjetaEliminar'])->name('tarjetas.eliminar');

        // ✅ TARJETAS REALES (BD) para EDITAR proveedor
        Route::post('/{id}/tarjetas', [ProveedorController::class, 'tarjetaStore'])->name('tarjetas.store');
        Route::put('/tarjetas/{tarjetaId}', [ProveedorController::class, 'tarjetaUpdate'])->name('tarjetas.update');
        Route::delete('/tarjetas/{tarjetaId}', [ProveedorController::class, 'tarjetaDestroy'])->name('tarjetas.destroy');
    });

    // PRECIOS
    Route::get('/dashboard/precios', [PrecioController::class, 'index'])->name('dashboard.precios');
    Route::get('/dashboard/precios/{id}/editar', [PrecioController::class, 'editar'])->name('producto_proveedor.editar_precio');
    Route::put('/dashboard/precios/{id}/actualizar', [PrecioController::class, 'actualizar'])->name('producto_proveedor.actualizar_precio');
    Route::get('/dashboard/precios/comparativa', [PrecioController::class, 'comparativaPrecios'])->name('dashboard.precios.comparativa');

    // Importación Excel (admin/general)
    Route::get('/dashboard/precios/importar-excel', [PrecioController::class, 'formImportarExcel'])->name('precios.form_excel');
    Route::post('/dashboard/precios/importar-excel', [PrecioController::class, 'importarExcel'])->name('precios.importar_excel');

    // PEDIDOS (FLUJO NORMAL)
    Route::get('/dashboard/pedidos/crear', [PedidoController::class, 'crear'])->name('dashboard.pedidos.solicitar');
    Route::get('/dashboard/pedidos/previsualizar', [PedidoController::class, 'previsualizar'])->name('dashboard.pedidos.previsualizar');
    Route::post('/dashboard/pedidos/guardar', [PedidoController::class, 'guardar'])->name('dashboard.pedidos.guardar');
    Route::get('/dashboard/pedidos/consultar', [PedidoController::class, 'consultar'])->name('dashboard.pedidos.consultar');
    Route::get('/dashboard/pedidos/visualizar/{id}', [PedidoController::class, 'visualizar'])->name('dashboard.pedidos.visualizar');
    Route::get('/dashboard/pedidos/detalle/{codigo}', [PedidoController::class, 'detalle'])->name('dashboard.pedidos.detalle');

    // ADMINISTRAR PEDIDOS
    Route::prefix('dashboard/pedidos/admin')->group(function () {
        Route::get('/', [AdminPedidoController::class, 'index'])->name('dashboard.pedidos.admin');
        Route::get('/{codigo}', [AdminPedidoController::class, 'detalle'])->name('dashboard.pedidos.admin.detalle');
        Route::post('/{codigo}/estado', [AdminPedidoController::class, 'cambiarEstado'])->name('dashboard.pedidos.admin.estado');
        Route::get('/{codigo}/editar', [AdminPedidoController::class, 'editar'])->name('dashboard.pedidos.admin.editar');
        Route::post('/{codigo}/actualizar', [AdminPedidoController::class, 'actualizar'])->name('dashboard.pedidos.admin.actualizar');
        Route::get('/{codigo}/pdf', [AdminPedidoController::class, 'generarPDF'])->name('dashboard.pedidos.admin.pdf');
    });

    // PEDIDOS ESPECIALES
    Route::prefix('dashboard/pedidos/especial')->group(function () {
        Route::get('/crear', [PedidoEspecialController::class, 'crear'])->name('dashboard.pedidos.especial.crear');
        Route::get('/previsualizar', [PedidoEspecialController::class, 'previsualizar'])->name('dashboard.pedidos.especial.previsualizar');
        Route::post('/guardar', [PedidoEspecialController::class, 'guardar'])->name('dashboard.pedidos.especial.guardar');
    });

    // CORTE DE CAJA
    Route::prefix('dashboard/corte-caja')->group(function () {
    Route::get('/', [CorteCajaController::class, 'index'])->name('dashboard.corte-caja');
    Route::get('/datos/{anio}/{mes}/{local}', [CorteCajaController::class, 'obtenerDatos'])->name('dashboard.corte-caja.datos');
    Route::post('/guardar-todo', [CorteCajaController::class, 'guardarTodo'])->name('dashboard.corte-caja.guardarTodo');
    });


    // ✅ COMENSALES (NUEVAS RUTAS)
    Route::prefix('dashboard/comensales')->group(function () {
        // Vista principal (selector de unidad + mes/año + tabla)
        Route::get('/', [ComensalesController::class, 'index'])->name('dashboard.comensales');

        // Guardar/actualizar registro del día (por unidad + fecha)
        Route::post('/guardar', [ComensalesController::class, 'guardar'])->name('dashboard.comensales.guardar');

        // Obtener datos por mes/año/unidad (para pintar la tabla con fetch/AJAX)
        Route::get('/datos/{anio}/{mes}/{unidad}', [ComensalesController::class, 'obtenerDatos'])->name('dashboard.comensales.datos');
        Route::post('/guardar-todo', [ComensalesController::class, 'guardarTodo'])->name('dashboard.comensales.guardarTodo');

    });

    // UNIDADES
    Route::prefix('dashboard/unidades')->group(function () {
        Route::get('/', [UnidadOperativaController::class, 'index'])->name('unidades.index');
        Route::get('/crear', [UnidadOperativaController::class, 'create'])->name('unidades.create');
        Route::post('/guardar', [UnidadOperativaController::class, 'store'])->name('unidades.store');
        Route::get('/{id}/editar', [UnidadOperativaController::class, 'edit'])->name('unidades.edit');
        Route::put('/{id}/actualizar', [UnidadOperativaController::class, 'update'])->name('unidades.update');
        Route::delete('/{id}/eliminar', [UnidadOperativaController::class, 'destroy'])->name('unidades.destroy');
    });

    // ALMACENES
    Route::prefix('dashboard/unidades/{unidad_id}/almacenes')->group(function () {
        Route::get('/', [AlmacenController::class, 'index'])->name('almacenes.index');
        Route::get('/crear', [AlmacenController::class, 'create'])->name('almacenes.create');
        Route::post('/crear', [AlmacenController::class, 'store'])->name('almacenes.store');
        Route::get('/{almacen_id}/editar', [AlmacenController::class, 'edit'])->name('almacenes.edit');
        Route::put('/{almacen_id}/actualizar', [AlmacenController::class, 'update'])->name('almacenes.update');
        Route::delete('/{almacen_id}/eliminar', [AlmacenController::class, 'destroy'])->name('almacenes.destroy');
    });

    // REPORTES
    Route::prefix('dashboard/reportes')->group(function () {
    Route::get('/', [ReportesController::class, 'index'])->name('dashboard.reportes');

    // Exportaciones
    Route::get('/export/excel', [ReportesController::class, 'exportExcel'])->name('dashboard.reportes.excel');
    Route::get('/export/pdf', [ReportesController::class, 'exportPDF'])->name('dashboard.reportes.pdf');
    });
});


// ============================================================================
// ✅ RUTAS CON ROLES
// ============================================================================

// Proveedor: precios propios
Route::middleware(['auth','role:proveedor'])->group(function () {
    Route::get('/dashboard/proveedor/precios', [PrecioController::class, 'misPrecios'])->name('proveedor.precios');
    Route::get('/dashboard/proveedor/precios/importar-excel', [PrecioController::class, 'formImportarExcelProveedor'])->name('proveedor.precios.form_excel');
    Route::post('/dashboard/proveedor/precios/importar-excel', [PrecioController::class, 'importarExcelProveedor'])->name('proveedor.precios.importar_excel');
});

// Usuarios admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('usuarios', UsuarioController::class);
});

// CEO pedidos
Route::middleware(['auth', 'role:ceo'])->prefix('dashboard/pedidos/ceo')->group(function () {
    Route::get('/', [AdminPedidoController::class, 'indexCeo'])->name('dashboard.pedidos.ceo');
    Route::get('/{codigo}', [AdminPedidoController::class, 'detalleCeo'])->name('dashboard.pedidos.ceo.detalle');
    Route::post('/{codigo}/estado', [AdminPedidoController::class, 'cambiarEstadoCeo'])->name('dashboard.pedidos.ceo.estado');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/inventarios', [InventarioController::class, 'index'])->name('inventarios.index');

    Route::get('/inventarios/movimiento', [InventarioController::class, 'movimientoForm'])->name('inventarios.movimiento.form');
    Route::post('/inventarios/movimiento', [InventarioController::class, 'movimientoStore'])->name('inventarios.movimiento.store');

    Route::get('/inventarios/kardex', [InventarioController::class, 'kardex'])->name('inventarios.kardex');
    Route::get('/inventarios/caducidades', [InventarioController::class, 'caducidades'])->name('inventarios.caducidades');

});

// AUTH
require __DIR__ . '/auth.php';
