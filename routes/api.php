<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================== ROTAS PÚBLICAS ====================
Route::get('/test', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'API PREGÃO Marketplace funcionando!',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Autenticação
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ==================== ROTAS PROTEGIDAS ====================
Route::middleware('auth:sanctum')->group(function () {
    
    // Perfil do usuário (do seu AuthController)
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);
    
    // Rotas de exemplo do Marketplace
    Route::get('/products', function () {
        return response()->json([
            ['id' => 1, 'name' => 'Arroz', 'price' => 1250, 'category' => 'Alimentos'],
            ['id' => 2, 'name' => 'Feijão', 'price' => 950, 'category' => 'Alimentos'],
            ['id' => 3, 'name' => 'Óleo', 'price' => 1750, 'category' => 'Alimentos'],
        ]);
    });
    
    Route::get('/stores', function () {
        return response()->json([
            ['id' => 1, 'name' => 'Supermercado Central', 'location' => 'Luanda'],
            ['id' => 2, 'name' => 'Mercado do Preço Baixo', 'location' => 'Huambo'],
            ['id' => 3, 'name' => 'Loja do Povo', 'location' => 'Benguela'],
        ]);
    });
    
    Route::get('/orders', function () {
        $user = auth()->user();
        return response()->json([
            'user_id' => $user->id,
            'orders' => [
                ['id' => 1001, 'total' => 3200, 'status' => 'entregue', 'date' => '2025-12-15'],
                ['id' => 1002, 'total' => 4500, 'status' => 'processando', 'date' => '2025-12-17'],
            ]
        ]);
    });
    
    // Dashboard do usuário
    Route::get('/dashboard', function () {
        $user = auth()->user();
        return response()->json([
            'welcome' => "Bem-vindo, {$user->name}!",
            'user_type' => $user->type,
            'stats' => [
                'orders_count' => 5,
                'total_spent' => 12500,
                'favorite_store' => 'Supermercado Central'
            ]
        ]);
    });
});

// ==================== ROTA DE DEBUG ====================
Route::get('/debug/auth', function () {
    $user = auth()->user();
    return response()->json([
        'authenticated' => $user ? 'SIM' : 'NÃO',
        'user_id' => $user ? $user->id : null,
        'token_present' => request()->bearerToken() ? 'SIM' : 'NÃO',
        'token_prefix' => request()->bearerToken() ? substr(request()->bearerToken(), 0, 20) . '...' : null
    ]);
})->middleware('auth:sanctum');
