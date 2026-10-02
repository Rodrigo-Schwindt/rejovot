<?php

use App\Http\Controllers\Cuenta\ComprobantesController as CuentaComprobantesController;
use App\Http\Controllers\Clientes\ClientesAdminController;
use App\Http\Controllers\Reclamos\FotoReclamoController;
use App\Http\Controllers\Reclamos\ReclamosAdminController;
use App\Http\Controllers\Seo\SitemapController;
use App\Livewire\Vistas\Reclamos\NuevoReclamoPage;
use App\Livewire\Vistas\Reclamos\ReclamoDetallePage;
use App\Livewire\Vistas\Reclamos\ReclamosPage;
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
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/productos', ProductosPage::class)->name('productos');
// Hay códigos con barra («6246/FF.0321»): el parámetro toma el resto de la URL.
Route::get('/productos/{codigo}', ProductoDetallePage::class)->where('codigo', '.+')->name('producto');
Route::get('/busqueda-por-vehiculo', BusquedaVehiculoPage::class)->name('vehiculos');
// Sólo con sesión del sitio: sin cliente o vendedor no hay nada que mostrar.
Route::middleware('auth:sitio')->group(function () {
    Route::get('/carrito', CarritoPage::class)->name('carrito');
    Route::get('/mis-pedidos', MisPedidosPage::class)->name('pedidos');
    Route::get('/estado-de-cuenta', EstadoCuentaPage::class)->name('cuenta');
    Route::get('/estado-de-cuenta/comprobante/{move}', [CuentaComprobantesController::class, 'show'])
        ->whereNumber('move')->name('cuenta.comprobante');
    Route::get('/info-de-pagos', InfoPagosPage::class)->name('pagos');

    Route::get('/reclamos', ReclamosPage::class)->name('reclamos');
    Route::get('/reclamos/nuevo', NuevoReclamoPage::class)->name('reclamos.nuevo');
    Route::get('/reclamos/{reclamo}', ReclamoDetallePage::class)->whereNumber('reclamo')->name('reclamos.ver');
    Route::get('/reclamos/{reclamo}/fotos/{foto}', [FotoReclamoController::class, 'sitio'])
        ->whereNumber(['reclamo', 'foto'])->name('reclamos.foto');
});
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
    Route::get('/productos/{producto}/seo', [ProductosAdminController::class, 'seo'])->name('admin.catalogo.seo');
    Route::patch('/productos/{producto}/seo', [ProductosAdminController::class, 'guardarSeo'])->name('admin.catalogo.seo.guardar');

    Route::get('/contacto', [ContactManager::class, 'index'])->name('admin.contacto');
    Route::post('/contacto', [ContactManager::class, 'save'])->name('admin.contacto.save');

    Route::get('/newsletter', [NewsletterAdminController::class, 'index'])->name('admin.newsletter.index');
    Route::post('/newsletter/enviar', [NewsletterAdminController::class, 'send'])->name('admin.newsletter.send');
    Route::patch('/newsletter/{subscriber}/estado', [NewsletterAdminController::class, 'toggle'])->name('admin.newsletter.toggle');
    Route::delete('/newsletter/{subscriber}', [NewsletterAdminController::class, 'destroy'])->name('admin.newsletter.destroy');

    Route::get('/cuentas-bancarias', [CuentasBancariasController::class, 'index'])->name('admin.pagos.cuentas');
    Route::post('/cuentas-bancarias', [CuentasBancariasController::class, 'save'])->name('admin.pagos.cuentas.save');

    Route::get('/comprobantes', [ComprobantesController::class, 'index'])->name('admin.pagos.comprobantes.index');
    Route::post('/comprobantes/mail', [ComprobantesController::class, 'guardarMail'])->name('admin.pagos.comprobantes.mail');

    Route::get('/clientes', [ClientesAdminController::class, 'index'])->name('admin.clientes.index');
    Route::get('/clientes/{cliente}', [ClientesAdminController::class, 'show'])->name('admin.clientes.show');
    Route::get('/vendedores', [VendedoresAdminController::class, 'index'])->name('admin.vendedores.index');
    Route::get('/vendedores/{vendedor}', [VendedoresAdminController::class, 'show'])->name('admin.vendedores.show');

    Route::get('/reclamos', [ReclamosAdminController::class, 'index'])->name('admin.reclamos.index');
    Route::post('/reclamos/mail', [ReclamosAdminController::class, 'guardarMail'])->name('admin.reclamos.mail');
    Route::get('/reclamos/{reclamo}', [ReclamosAdminController::class, 'show'])->name('admin.reclamos.show');
    Route::patch('/reclamos/{reclamo}', [ReclamosAdminController::class, 'update'])->name('admin.reclamos.update');
    Route::get('/reclamos/{reclamo}/fotos/{foto}', [FotoReclamoController::class, 'admin'])->name('admin.reclamos.foto');
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

    // Una por una y no con Route::resource: ese se registra recién al destruirse
    // el objeto, y en el PHP 8.5 de producción las rutas no quedaban cargadas.
    Route::get('/usuarios', [UsuariosController::class, 'index'])->name('admin.usuarios.index');
    Route::get('/usuarios/create', [UsuariosController::class, 'create'])->name('admin.usuarios.create');
    Route::post('/usuarios', [UsuariosController::class, 'store'])->name('admin.usuarios.store');
    Route::get('/usuarios/{usuario}/edit', [UsuariosController::class, 'edit'])->name('admin.usuarios.edit');
    Route::match(['put', 'patch'], '/usuarios/{usuario}', [UsuariosController::class, 'update'])->name('admin.usuarios.update');
    Route::delete('/usuarios/{usuario}', [UsuariosController::class, 'destroy'])->name('admin.usuarios.destroy');
});
