<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\DriverController;
use Illuminate\Support\Facades\Route;

// Autenticação
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas públicas
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Autenticação
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Produtos
    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('check.user.type:seller');
    Route::put('/products/{id}', [ProductController::class, 'update'])
        ->middleware('check.user.type:seller');
    Route::get('/meus-produtos', [ProductController::class, 'myProducts'])
        ->middleware('check.user.type:seller');

    // Pedidos
    Route::get('/pedidos', [OrderController::class, 'index']);
    Route::post('/pedidos', [OrderController::class, 'store'])
        ->middleware('check.user.type:customer');
    Route::get('/pedidos/{id}', [OrderController::class, 'show']);
    Route::put('/pedidos/{id}/status', [OrderController::class, 'updateStatus']);

    // Carrinho
    Route::get('/carrinho', [CartController::class, 'show']);
    Route::post('/carrinho', [CartController::class, 'addItem'])
        ->middleware('check.user.type:customer');
    Route::put('/carrinho/{id}', [CartController::class, 'updateItem'])
        ->middleware('check.user.type:customer');
    Route::delete('/carrinho/{id}', [CartController::class, 'removeItem'])
        ->middleware('check.user.type:customer');

    // Lojas
    Route::post('/stores', [StoreController::class, 'store'])
        ->middleware('check.user.type:seller');
    Route::put('/stores/{id}', [StoreController::class, 'update'])
        ->middleware('check.user.type:seller');

    // Motoristas
    Route::get('/driver/orders', [DriverController::class, 'orders'])
        ->middleware('check.user.type:driver');
    Route::put('/driver/orders/{id}/status', [DriverController::class, 'updateOrderStatus'])
        ->middleware('check.user.type:driver');
});

// Admin routes
Route::middleware(['auth:sanctum', 'check.user.type:admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::put('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
    Route::get('/admin/orders', [AdminController::class, 'orders']);
    Route::get('/admin/stats', [AdminController::class, 'stats']);
});