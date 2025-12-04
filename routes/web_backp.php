<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'PREGÃO Marketplace API',
        'version' => '1.0.0',
        'status' => 'online',
        'endpoints' => [
            '/api/test' => 'Test endpoint',
            '/api/login' => 'Login endpoint',
            '/api/products' => 'List products',
            '/api/stores' => 'List stores'
        ]
    ]);
});

// Rota de saúde para verificar se a API está funcionando
Route::get('/up', function () {
    return response()->json(['status' => 'up']);
});
