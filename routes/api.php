<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Produtos públicos
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Lojas públicas
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);

// Categorias públicas
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/tree', [CategoryController::class, 'tree']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Marcas públicas
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{id}', [BrandController::class, 'show']);

// Avaliações públicas
Route::get('/products/{id}/reviews', [ReviewController::class, 'getProductReviews']);
Route::get('/stores/{id}/reviews', [ReviewController::class, 'getStoreReviews']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    
    // Autenticação
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Utilizadores
    Route::get('/users', [UserController::class, 'index'])->middleware('check.user.type:admin');
    Route::get('/users/{id}', [UserController::class, 'show'])->middleware('check.user.type:admin');
    Route::post('/users', [UserController::class, 'store'])->middleware('check.user.type:admin');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('check.user.type:admin');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('check.user.type:admin');

    // Empresas
    Route::get('/companies', [CompanyController::class, 'index'])->middleware('check.user.type:admin,seller');
    Route::get('/companies/{id}', [CompanyController::class, 'show'])->middleware('check.company.access');
    Route::post('/companies', [CompanyController::class, 'store'])->middleware('check.user.type:admin,seller');
    Route::put('/companies/{id}', [CompanyController::class, 'update'])->middleware('check.company.access');
    Route::post('/companies/{id}/add-user', [CompanyController::class, 'addUser'])->middleware('check.company.access');
    Route::delete('/companies/{companyId}/users/{userId}', [CompanyController::class, 'removeUser'])->middleware('check.company.access');

    // Lojas
    Route::post('/stores', [StoreController::class, 'store'])->middleware('check.user.type:seller');
    Route::put('/stores/{id}', [StoreController::class, 'update'])->middleware('check.store.ownership');
    Route::get('/my-stores', [StoreController::class, 'myStores'])->middleware('check.user.type:seller');
    Route::get('/stores/{id}/stats', [StoreController::class, 'getStoreStats'])->middleware('check.store.ownership');
    Route::post('/stores/{id}/toggle-verification', [StoreController::class, 'toggleVerification'])->middleware('check.user.type:admin');

    // Produtos
    Route::post('/products', [ProductController::class, 'store'])->middleware('check.user.type:seller');
    Route::put('/products/{id}', [ProductController::class, 'update'])->middleware('check.store.ownership');
    Route::get('/my-products', [ProductController::class, 'myProducts'])->middleware('check.user.type:seller');
    Route::post('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->middleware('check.store.ownership');
    Route::post('/products/{productId}/images/{imageId}/primary', [ProductController::class, 'updatePrimaryImage'])->middleware('check.store.ownership');
    Route::delete('/products/{productId}/images/{imageId}', [ProductController::class, 'deleteImage'])->middleware('check.store.ownership');

    // Categorias (Admin only)
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('check.user.type:admin');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->middleware('check.user.type:admin');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware('check.user.type:admin');
    Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->middleware('check.user.type:admin');

    // Marcas (Admin only)
    Route::post('/brands', [BrandController::class, 'store'])->middleware('check.user.type:admin');
    Route::put('/brands/{id}', [BrandController::class, 'update'])->middleware('check.user.type:admin');
    Route::delete('/brands/{id}', [BrandController::class, 'destroy'])->middleware('check.user.type:admin');

    // Pedidos
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store'])->middleware('check.user.type:customer');
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus'])->middleware('check.user.type:admin,seller');
    Route::post('/orders/{id}/assign-driver', [OrderController::class, 'assignDriver'])->middleware('check.user.type:admin,seller');
    Route::get('/orders/stats', [OrderController::class, 'getOrderStats']);

    // Carrinho
    Route::get('/cart', [CartController::class, 'show'])->middleware('check.user.type:customer');
    Route::post('/cart/items', [CartController::class, 'addItem'])->middleware('check.user.type:customer');
    Route::put('/cart/items/{id}', [CartController::class, 'updateItem'])->middleware('check.user.type:customer');
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem'])->middleware('check.user.type:customer');
    Route::delete('/cart/clear', [CartController::class, 'clear'])->middleware('check.user.type:customer');
    Route::get('/cart/count', [CartController::class, 'getCartCount'])->middleware('check.user.type:customer');

    // Motoristas
    Route::post('/drivers/register', [DriverController::class, 'register'])->middleware('check.user.type:customer');
    Route::put('/drivers/profile', [DriverController::class, 'updateProfile'])->middleware('check.user.type:driver');
    Route::get('/drivers/orders', [DriverController::class, 'orders'])->middleware('check.user.type:driver');
    Route::post('/drivers/orders/{orderId}/status', [DriverController::class, 'updateOrderStatus'])->middleware('check.user.type:driver');
    Route::post('/drivers/location', [DriverController::class, 'updateLocation'])->middleware('check.user.type:driver');
    Route::get('/drivers/available-orders', [DriverController::class, 'getAvailableOrders'])->middleware('check.user.type:driver');
    Route::post('/drivers/orders/{orderId}/accept', [DriverController::class, 'acceptOrder'])->middleware('check.user.type:driver');
    Route::get('/drivers/stats', [DriverController::class, 'getDriverStats'])->middleware('check.user.type:driver');

    // Gestão de motoristas (Admin)
    Route::get('/drivers', [DriverController::class, 'index'])->middleware('check.user.type:admin');
    Route::get('/drivers/{id}', [DriverController::class, 'show'])->middleware('check.user.type:admin');
    Route::post('/drivers/{id}/toggle-verification', [DriverController::class, 'toggleVerification'])->middleware('check.user.type:admin');

    // Stock
    Route::get('/stock', [StockController::class, 'index'])->middleware('check.user.type:admin,seller');
    Route::post('/stock/add', [StockController::class, 'addStock'])->middleware('check.user.type:admin,seller');
    Route::post('/stock/{id}/adjust', [StockController::class, 'adjustStock'])->middleware('check.user.type:admin,seller');
    Route::post('/stock/transfer', [StockController::class, 'transferStock'])->middleware('check.user.type:admin,seller');
    Route::get('/stock/movements', [StockController::class, 'getStockMovements'])->middleware('check.user.type:admin,seller');
    Route::get('/stock/alerts', [StockController::class, 'getLowStockAlerts'])->middleware('check.user.type:admin,seller');
    Route::get('/stock/{productId}/{warehouseId}/history', [StockController::class, 'getStockHistory'])->middleware('check.user.type:admin,seller');
    Route::get('/stock/stats', [StockController::class, 'getStockStats'])->middleware('check.user.type:admin,seller');

    // Armazéns
    Route::get('/warehouses', [WarehouseController::class, 'index'])->middleware('check.user.type:admin,seller');
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show'])->middleware('check.user.type:admin,seller');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('check.user.type:admin,seller');
    Route::put('/warehouses/{id}', [WarehouseController::class, 'update'])->middleware('check.user.type:admin,seller');
    Route::get('/warehouses/{id}/stats', [WarehouseController::class, 'getWarehouseStats'])->middleware('check.user.type:admin,seller');
    Route::get('/warehouses/{id}/products', [WarehouseController::class, 'getWarehouseProducts'])->middleware('check.user.type:admin,seller');

    // Avaliações
    Route::post('/reviews', [ReviewController::class, 'store'])->middleware('check.user.type:customer');
    Route::post('/reviews/{id}/response', [ReviewController::class, 'addResponse']);
    Route::post('/reviews/{id}/approve', [ReviewController::class, 'approveReview'])->middleware('check.user.type:admin');
    Route::post('/reviews/{id}/reject', [ReviewController::class, 'rejectReview'])->middleware('check.user.type:admin');
    Route::get('/reviews/pending', [ReviewController::class, 'getPendingReviews'])->middleware('check.user.type:admin');

    // Notificações
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'clearAll']);

    // Admin
    Route::prefix('admin')->middleware('check.user.type:admin')->group(function () {
        Route::get('/dashboard-stats', [AdminController::class, 'dashboardStats']);
        Route::get('/recent-activities', [AdminController::class, 'recentActivities']);
        Route::get('/sales-report', [AdminController::class, 'salesReport']);
        Route::get('/user-management', [AdminController::class, 'userManagement']);
        Route::get('/store-management', [AdminController::class, 'storeManagement']);
        Route::get('/driver-management', [AdminController::class, 'driverManagement']);
    });
});