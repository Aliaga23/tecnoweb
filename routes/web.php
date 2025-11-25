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
