<?php

use Illuminate\Support\Facades\Route;

// Rota pública de teste
Route::get('/test', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'API PREGÃO Marketplace funcionando!',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Login simulado
Route::post('/login', function () {
    $email = request('email');
    $password = request('password');
    
    $users = [
        'admin@pregao.ao' => ['password' => 'admin123', 'type' => 'admin'],
        'vendedor@pregao.ao' => ['password' => 'vendedor123', 'type' => 'seller'],
        'cliente@pregao.ao' => ['password' => 'cliente123', 'type' => 'customer']
    ];
    
    if (!isset($users[$email]) || $users[$email]['password'] !== $password) {
        return response()->json(['error' => 'Credenciais inválidas'], 401);
    }
    
    return response()->json([
        'access_token' => 'test-token-' . uniqid(),
        'token_type' => 'bearer',
        'expires_in' => 3600,
        'user' => [
            'id' => 1,
            'name' => 'Test User',
            'email' => $email,
            'type' => $users[$email]['type']
        ]
    ]);
});

// Rotas básicas para teste
Route::get('/products', function () {
    return response()->json([
        ['id' => 1, 'name' => 'Produto 1', 'price' => 1000],
        ['id' => 2, 'name' => 'Produto 2', 'price' => 2000]
    ]);
});

Route::get('/stores', function () {
    return response()->json([
        ['id' => 1, 'name' => 'Loja 1'],
        ['id' => 2, 'name' => 'Loja 2']
    ]);
});

// Simulação de rota protegida
Route::middleware('auth:sanctum')->get('/user', function () {
    return response()->json([
        'user' => [
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@pregao.ao'
        ]
    ]);
});

// Registro
Route::post('/register', function () {
    return response()->json([
        'message' => 'Usuário registrado com sucesso',
        'user' => [
            'id' => rand(100, 999),
            'name' => request('name'),
            'email' => request('email')
        ]
    ]);
});
