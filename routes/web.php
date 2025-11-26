<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing');
});

Route::get('/register', function () {
    return Inertia::render('Register');
});

Route::get('/login', function () {
    return Inertia::render('Login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
});

Route::get('/dashboard/usuarios', function () {
    return Inertia::render('DashboardUsuarios');
});

Route::get('/dashboard/productos', function () {
    return Inertia::render('DashboardProductos');
});

Route::get('/dashboard/categorias', function () {
    return Inertia::render('DashboardCategorias');
});

Route::get('/dashboard/proveedores', function () {
    return Inertia::render('DashboardProveedores');
});

Route::get('/dashboard/transacciones', function () {
    return Inertia::render('DashboardTransacciones');
});

Route::get('/dashboard/ventas-credito', function () {
    return Inertia::render('DashboardVentasCredito');
});

Route::get('/dashboard/cotizaciones', function () {
    return Inertia::render('DashboardCotizaciones');
});

Route::get('/dashboard/devoluciones', function () {
    return Inertia::render('DashboardDevoluciones');
});

Route::get('/dashboard/devoluciones-proveedor', function () {
    return Inertia::render('DashboardDevolucionesProveedor');
});

Route::get('/home', function () {
    return Inertia::render('Home');
});

Route::get('/catalogo', function () {
    return Inertia::render('Catalogo');
});

Route::get('/catalogo/{id}', function ($id) {
    return Inertia::render('ProductoDetalle', ['productoId' => $id]);
});

Route::get('/cart', function () {
    return Inertia::render('Cart');
});

Route::get('/mis-cotizaciones', function () {
    return Inertia::render('MisCotizaciones');
});

Route::get('/cotizacion/{id}', function ($id) {
    return Inertia::render('DetalleCotizacion', ['cotizacionId' => $id]);
});

Route::get('/pago-qr', function () {
    return Inertia::render('PagoQR');
});

Route::get('/mis-compras', function () {
    return Inertia::render('MisCompras');
});

Route::get('/mis-devoluciones', function () {
    return Inertia::render('MisDevoluciones');
});
