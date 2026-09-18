<?php

use App\Http\Controllers\Cuenta\ComprobantesController as CuentaComprobantesController;
use App\Http\Controllers\Clientes\ClientesAdminController;
use App\Http\Controllers\Clientes\VendedoresAdminController;
use App\Http\Controllers\Auth\IngresoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalogo\ProductosAdminController;
use App\Http\Controllers\Contacto\ContactManager;
use App\Http\Controllers\Metadata\MetadataCrud;
use App\Http\Controllers\Newsletter\NewsletterAdminController;
use App\Http\Controllers\Pagos\ComprobantesController;
use App\Http\Controllers\Pagos\CuentasBancariasController;
use App\Http\Controllers\Precios\ListasPreciosController;
use App\Http\Controllers\Usuarios\UsuariosController;
use App\Livewire\Vistas\Carrito\CarritoPage;
use App\Livewire\Vistas\Cuenta\EstadoCuentaPage;
use App\Livewire\Vistas\Margenes\MargenesPage;
use App\Livewire\Vistas\Pagos\InfoPagosPage;
use App\Livewire\Vistas\Pedidos\MisPedidosPage;
use App\Livewire\Vistas\Precios\ListaPreciosPage;
use App\Livewire\Vistas\Productos\ProductoDetallePage;
use App\Livewire\Vistas\Productos\ProductosPage;
use App\Livewire\Vistas\Vehiculos\BusquedaVehiculoPage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitio público
|--------------------------------------------------------------------------
| Por ahora sólo la vista de productos (datos hardcodeados hasta conectar Odoo).
*/

Route::get('/', fn () => redirect()->route('productos'))->name('home');
Route::get('/productos', ProductosPage::class)->name('productos');
Route::get('/productos/{codigo}', ProductoDetallePage::class)->name('producto');
Route::get('/busqueda-por-vehiculo', BusquedaVehiculoPage::class)->name('vehiculos');
Route::get('/carrito', CarritoPage::class)->name('carrito');
Route::get('/mis-pedidos', MisPedidosPage::class)->name('pedidos');
Route::get('/estado-de-cuenta', EstadoCuentaPage::class)->name('cuenta');
Route::get('/estado-de-cuenta/comprobante/{move}', [CuentaComprobantesController::class, 'show'])
    ->whereNumber('move')->name('cuenta.comprobante');
Route::get('/info-de-pagos', InfoPagosPage::class)->name('pagos');
Route::get('/margenes', MargenesPage::class)->name('margenes');
Route::get('/lista-de-precios', ListaPreciosPage::class)->name('precios');
Route::get('/lista-de-precios/{lista}/descargar', [ListasPreciosController::class, 'download'])->name('precios.descargar');
Route::get('/lista-de-precios/{lista}/ver', [ListasPreciosController::class, 'show'])->name('precios.ver');

/*
|--------------------------------------------------------------------------
| Autenticación del panel
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Ingreso de clientes y vendedores (con su usuario de Odoo)
|--------------------------------------------------------------------------
*/

Route::get('/ingresar', [IngresoController::class, 'formulario'])->name('ingresar');
Route::post('/ingresar', [IngresoController::class, 'ingresar'])->name('ingresar.post');
Route::post('/salir', [IngresoController::class, 'salir'])->middleware('auth:sitio')->name('salir');

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Panel administrativo (contenido que NO viene de Odoo)
|--------------------------------------------------------------------------
*/

Route::middleware(['admin', 'viewer.readonly'])->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.contacto'))->name('admin');

    Route::get('/productos', [ProductosAdminController::class, 'index'])->name('admin.catalogo.index');
    Route::patch('/productos/{producto}/ocultar', [ProductosAdminController::class, 'ocultar'])->name('admin.catalogo.ocultar');
    Route::patch('/productos/{producto}/destacar', [ProductosAdminController::class, 'destacar'])->name('admin.catalogo.destacar');
    Route::post('/productos/destacados/limpiar', [ProductosAdminController::class, 'limpiarDestacados'])->name('admin.catalogo.destacados.limpiar');

    Route::get('/contacto', [ContactManager::class, 'index'])->name('admin.contacto');
    Route::post('/contacto', [ContactManager::class, 'save'])->name('admin.contacto.save');

    Route::get('/newsletter', [NewsletterAdminController::class, 'index'])->name('admin.newsletter.index');
    Route::post('/newsletter/enviar', [NewsletterAdminController::class, 'send'])->name('admin.newsletter.send');
    Route::patch('/newsletter/{subscriber}/estado', [NewsletterAdminController::class, 'toggle'])->name('admin.newsletter.toggle');
    Route::delete('/newsletter/{subscriber}', [NewsletterAdminController::class, 'destroy'])->name('admin.newsletter.destroy');

    Route::get('/cuentas-bancarias', [CuentasBancariasController::class, 'index'])->name('admin.pagos.cuentas');
    Route::post('/cuentas-bancarias', [CuentasBancariasController::class, 'save'])->name('admin.pagos.cuentas.save');

    Route::get('/comprobantes', [ComprobantesController::class, 'index'])->name('admin.pagos.comprobantes.index');

    Route::get('/clientes', [ClientesAdminController::class, 'index'])->name('admin.clientes.index');
    Route::get('/clientes/{cliente}', [ClientesAdminController::class, 'show'])->name('admin.clientes.show');
    Route::get('/vendedores', [VendedoresAdminController::class, 'index'])->name('admin.vendedores.index');
    Route::get('/vendedores/{vendedor}', [VendedoresAdminController::class, 'show'])->name('admin.vendedores.show');
    Route::get('/comprobantes/{comprobante}/descargar', [ComprobantesController::class, 'download'])->name('admin.pagos.comprobantes.download');
    Route::patch('/comprobantes/{comprobante}/estado', [ComprobantesController::class, 'estado'])->name('admin.pagos.comprobantes.estado');
    Route::delete('/comprobantes/{comprobante}', [ComprobantesController::class, 'destroy'])->name('admin.pagos.comprobantes.destroy');

    Route::get('/listas-de-precios', [ListasPreciosController::class, 'index'])->name('admin.precios.index');
    Route::post('/listas-de-precios', [ListasPreciosController::class, 'store'])->name('admin.precios.store');
    Route::post('/listas-de-precios/regenerar', [ListasPreciosController::class, 'regenerar'])->name('admin.precios.regenerar');
    Route::patch('/listas-de-precios/{lista}/estado', [ListasPreciosController::class, 'toggle'])->name('admin.precios.toggle');
    Route::delete('/listas-de-precios/{lista}', [ListasPreciosController::class, 'destroy'])->name('admin.precios.destroy');

    Route::get('/metadata', [MetadataCrud::class, 'index'])->name('admin.metadata');
    Route::post('/metadata', [MetadataCrud::class, 'save'])->name('admin.metadata.save');
    Route::delete('/metadata/{metadata}', [MetadataCrud::class, 'delete'])->name('admin.metadata.delete');

    Route::resource('usuarios', UsuariosController::class)->except('show');
});
