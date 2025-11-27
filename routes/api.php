<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\DevolucionProveedorController;
use App\Http\Controllers\TransaccionController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\ReporteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas (sin autenticación)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Catálogo público
Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/catalogo/{id}', [CatalogoController::class, 'show'])->name('catalogo.show');
Route::get('/catalogo/categoria/{categoria_id}', [CatalogoController::class, 'porCategoria'])->name('catalogo.categoria');
Route::get('/catalogo-categorias', [CatalogoController::class, 'categorias'])->name('catalogo.categorias');

// Pagos QR PagoFácil
Route::post('/generar-qr', [PagoController::class, 'generarQR']);
Route::post('/generar-qr-venta-contado', [PagoController::class, 'generarQRVentaContado']);
Route::post('/pago-callback', [PagoController::class, 'callback']);
Route::post('/pago-credito-callback', [PagoController::class, 'callbackCredito']);
Route::get('/pago-estado/{pago_id}', [PagoController::class, 'consultarEstado']);
Route::get('/verificar-pago/{pago_id}', [PagoController::class, 'consultarEstado']);

// Contador de visitas (público)
Route::post('/visitas/{pagina}', function($pagina) {
    try {
        // Verificar si ya existe un registro para esta página
        $visita = DB::select('SELECT * FROM visitas WHERE pagina = ?', [$pagina]);
        
        if (count($visita) > 0) {
            // Incrementar el contador
            DB::update('UPDATE visitas SET contador = contador + 1, updated_at = NOW() WHERE pagina = ?', [$pagina]);
            $resultado = DB::select('SELECT contador FROM visitas WHERE pagina = ?', [$pagina]);
            $contador = $resultado[0]->contador;
        } else {
            // Crear nuevo registro
            DB::insert('INSERT INTO visitas (pagina, contador, created_at, updated_at) VALUES (?, 1, NOW(), NOW())', [$pagina]);
            $contador = 1;
        }
        
        return response()->json([
            'success' => true,
            'visitas' => $contador
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar visita: ' . $e->getMessage()
        ], 500);
    }
});

// Rutas protegidas (requieren autenticación JWT)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Upload de imágenes
    Route::post('/upload-imagen', [ImagenController::class, 'upload']);
    
    // CRUD de Usuarios (solo Propietario - rol_id = 1)
    Route::middleware('role:1')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
        Route::post('/usuarios', [UsuarioController::class, 'store']);
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
    });
    
    // CRUD de Productos (Propietario y Vendedor - rol_id = 1,2)
    Route::middleware('role:1,2')->group(function () {
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{id}', [ProductoController::class, 'update']);
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
        
        // CRUD de Categorías
        Route::post('/categorias', [CategoriaController::class, 'store']);
        Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
        Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);
        
        // CRUD de Proveedores
        Route::get('/proveedores', [ProveedorController::class, 'index']);
        Route::get('/proveedores/{id}', [ProveedorController::class, 'show']);
        Route::post('/proveedores', [ProveedorController::class, 'store']);
        Route::put('/proveedores/{id}', [ProveedorController::class, 'update']);
        Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy']);
        
        // Gestión de Pagos (solo lectura para Propietario y Vendedor)
        Route::get('/pagos', [PagoController::class, 'index']);
        Route::get('/pagos/{id}', [PagoController::class, 'show']);
        
        // Ventas al Crédito (Propietario y Vendedor)
        Route::get('/clientes/buscar/{ci}', [TransaccionController::class, 'buscarClientePorCi']);
        Route::post('/ventas/credito', [TransaccionController::class, 'crearVentaCredito']);
        Route::get('/ventas/credito', [TransaccionController::class, 'obtenerVentasCredito']);
        Route::post('/ventas/{id}/pagos', [TransaccionController::class, 'registrarPagoCredito']);
        
        // Ventas al Contado (Propietario y Vendedor)
        Route::post('/ventas/contado', [TransaccionController::class, 'crearVentaContado']);
        Route::get('/ventas/contado', [TransaccionController::class, 'obtenerVentasContado']);
        Route::post('/ventas/{id}/pago-contado', [TransaccionController::class, 'registrarPagoContado']);
        
        // Gestión de Ventas (solo lectura para Propietario y Vendedor)
        Route::get('/ventas', [CompraController::class, 'indexVentas']);
        Route::get('/ventas/{id}', [CompraController::class, 'showVenta']);
        
        // Gestión de Transacciones (unificado)
        Route::get('/transacciones', [TransaccionController::class, 'index']);
        Route::get('/transacciones/venta/{id}', [TransaccionController::class, 'detalleVenta']);
        Route::get('/transacciones/resumen', [TransaccionController::class, 'resumen']);
        
        // Dashboard administrativo - Devoluciones unificadas
        Route::get('/devoluciones', [DevolucionController::class, 'obtenerTodasDevoluciones']);
        Route::get('/devoluciones/{id}/detalle', [DevolucionController::class, 'obtenerDetalleDevolucion']);
        Route::get('/ventas/ci/{ci}', [DevolucionController::class, 'buscarVentasPorCarnet']);
        Route::get('/ventas/{id}/detalle', [DevolucionController::class, 'obtenerDetalleVenta']);
        Route::post('/devoluciones', [DevolucionController::class, 'crearDevolucion']);
        
        // Dashboard administrativo - Devoluciones a Proveedores
        Route::get('/devoluciones-proveedor', [DevolucionProveedorController::class, 'obtenerDevoluciones']);
        Route::get('/devoluciones-proveedor/{id}/detalle', [DevolucionProveedorController::class, 'obtenerDetalle']);
        Route::get('/proveedores', [DevolucionProveedorController::class, 'obtenerProveedores']);
        Route::get('/productos-disponibles', [DevolucionProveedorController::class, 'obtenerProductos']);
        Route::post('/devoluciones-proveedor', [DevolucionProveedorController::class, 'crearDevolucion']);
        
        // Dashboard administrativo - Gestión de Cotizaciones
        Route::get('/cotizaciones/{id}/detalle', [CotizacionController::class, 'obtenerDetalle']);
    });
    
    // Ver productos y categorías (todos los roles autenticados)
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/{id}', [CategoriaController::class, 'show']);
    
    // CRUD de Cotizaciones (todos los roles autenticados)
    Route::get('/cotizaciones', [CotizacionController::class, 'index']);
    Route::get('/cotizaciones/{id}', [CotizacionController::class, 'show']);
    Route::post('/cotizaciones', [CotizacionController::class, 'store']);
    Route::put('/cotizaciones/{id}', [CotizacionController::class, 'update']);
    Route::delete('/cotizaciones/{id}', [CotizacionController::class, 'destroy']);
    Route::get('/cotizaciones/usuario/{usuario_id}', [CotizacionController::class, 'porUsuario']);
    Route::get('/cotizaciones/{id}/pdf', [CotizacionController::class, 'generarPDF']);
    
    // Compras del usuario
    Route::get('/mis-compras', [CompraController::class, 'obtenerCompras']);
    Route::get('/compras/{id}', [CompraController::class, 'obtenerDetalle']);
    Route::post('/ventas', [CompraController::class, 'store']);
    
    // Devoluciones del usuario
    Route::get('/mis-devoluciones', [DevolucionController::class, 'obtenerDevoluciones']);
    Route::get('/devoluciones/{id}', [DevolucionController::class, 'obtenerDetalle']);
    
    // Ventas al Crédito del Cliente
    Route::get('/mis-ventas-credito', [TransaccionController::class, 'misVentasCredito']);
    Route::post('/ventas/{id}/pago-credito', [TransaccionController::class, 'registrarPagoCredito']);
    
    // Pago QR para crédito
    Route::post('/generar-qr-credito', [PagoController::class, 'generarQRCredito']);
});

// Rutas de reportes (requieren autenticación)
Route::middleware('auth:api')->group(function () {
    Route::get('/reportes/ventas-por-mes', [ReporteController::class, 'ventasPorMes']);
    Route::get('/reportes/productos-top-ventas', [ReporteController::class, 'productosTopVentas']);
    Route::get('/reportes/estado-ventas', [ReporteController::class, 'estadoVentas']);
    Route::get('/reportes/productos-stock-bajo', [ReporteController::class, 'productosStockBajo']);
});

