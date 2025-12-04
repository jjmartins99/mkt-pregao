<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

// Rota de teste pública
Route::get('/test', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'API PREGÃO Marketplace funcionando!',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// Login simulado (sem banco de dados)
Route::post('/login', function () {
    $validLogins = [
        'admin@pregao.ao' => [
            'password' => 'admin123',
            'user' => [
                'id' => 1,
                'name' => 'Administrador',
                'email' => 'admin@pregao.ao',
                'type' => 'admin',
                'phone' => '+244 999 999 999',
                'nif' => '9999999999'
            ]
        ],
        'vendedor@pregao.ao' => [
            'password' => 'vendedor123',
            'user' => [
                'id' => 2,
                'name' => 'Vendedor Teste',
                'email' => 'vendedor@pregao.ao',
                'type' => 'seller',
                'phone' => '+244 922 222 222',
                'nif' => '8888888888'
            ]
        ],
        'cliente@pregao.ao' => [
            'password' => 'cliente123',
            'user' => [
                'id' => 3,
                'name' => 'Cliente Teste',
                'email' => 'cliente@pregao.ao',
                'type' => 'customer',
                'phone' => '+244 933 333 333',
                'nif' => '7777777777'
            ]
        ]
    ];

    $email = request('email');
    $password = request('password');

    if (!isset($validLogins[$email]) || $validLogins[$email]['password'] !== $password) {
        return response()->json(['error' => 'Credenciais inválidas'], 401);
    }

    // Gera um token simulado
    $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.' . 
             base64_encode(json_encode(['user_id' => $validLogins[$email]['user']['id'], 'exp' => time() + 3600])) .
             '.fake_signature';

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => 3600,
        'user' => $validLogins[$email]['user']
    ]);
});

// Rotas públicas
Route::get('/products', function () {
    return response()->json([
        'data' => [
            ['id' => 1, 'name' => 'iPhone 15 Pro', 'price' => 350000, 'store_id' => 1, 'category' => 'Smartphones'],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 280000, 'store_id' => 1, 'category' => 'Smartphones'],
            ['id' => 3, 'name' => 'Dell XPS 13', 'price' => 750000, 'store_id' => 2, 'category' => 'Notebooks']
        ],
        'meta' => ['total' => 3, 'page' => 1]
    ]);
});

Route::get('/products/{id}', function ($id) {
    return response()->json([
        'id' => $id,
        'name' => 'Produto ' . $id,
        'description' => 'Descrição do produto ' . $id,
        'price' => $id * 1000,
        'store_id' => 1,
        'category' => 'Eletrônicos',
        'stock' => 50
    ]);
});

Route::get('/stores', function () {
    return response()->json([
        ['id' => 1, 'name' => 'Tech Store Angola', 'slug' => 'tech-store', 'rating' => 4.8],
        ['id' => 2, 'name' => 'Electro Market', 'slug' => 'electro-market', 'rating' => 4.5]
    ]);
});

// Middleware de autenticação simulado
Route::middleware('auth:sanctum')->group(function () {
    
    // Verificação simples de token
    $tokenVerifier = function ($request, $next) {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Token não fornecido'], 401);
        }
        
        $token = substr($authHeader, 7);
        
        // Verificação simples do token
        if (!str_starts_with($token, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.')) {
            return response()->json(['error' => 'Token inválido'], 401);
        }
        
        return $next($request);
    };
    
    // Aplica o middleware a todas as rotas dentro deste grupo
    Route::group(['middleware' => $tokenVerifier], function () {
        
        Route::get('/user', function () {
            // Simula usuário baseado no token
            return response()->json([
                'user' => [
                    'id' => 1,
                    'name' => 'Administrador',
                    'email' => 'admin@pregao.ao',
                    'type' => 'admin'
                ]
            ]);
        });
        
        Route::post('/logout', function () {
            return response()->json(['message' => 'Logout realizado com sucesso']);
        });
        
        // 🛒 CARRINHO & PEDIDOS
        Route::post('/cart/items', function () {
            return response()->json([
                'message' => 'Produto adicionado ao carrinho',
                'item' => [
                    'id' => rand(100, 999),
                    'product_id' => request('product_id'),
                    'quantity' => request('quantity'),
                    'store_id' => request('store_id')
                ]
            ]);
        });
        
        Route::get('/cart', function () {
            return response()->json([
                'items' => [
                    ['id' => 101, 'product_id' => 1, 'quantity' => 2, 'price' => 25000]
                ],
                'total' => 50000
            ]);
        });
        
        Route::post('/orders', function () {
            return response()->json([
                'message' => 'Pedido criado com sucesso',
                'order' => [
                    'id' => rand(1000, 9999),
                    'status' => 'pending',
                    'total' => 51000
                ]
            ]);
        });
        
        Route::get('/orders', function () {
            return response()->json([
                ['id' => 1, 'status' => 'pending', 'total' => 51000],
                ['id' => 2, 'status' => 'processing', 'total' => 35000]
            ]);
        });
        
        // 👨‍💼 ADMIN
        Route::get('/admin/dashboard-stats', function () {
            return response()->json([
                'total_users' => 156,
                'total_products' => 478,
                'total_orders' => 92,
                'total_revenue' => 12850000
            ]);
        });
        
        Route::get('/users', function () {
            return response()->json([
                ['id' => 1, 'name' => 'Admin', 'email' => 'admin@pregao.ao', 'type' => 'admin'],
                ['id' => 2, 'name' => 'Vendedor', 'email' => 'vendedor@pregao.ao', 'type' => 'seller']
            ]);
        });
        
        // 🏢 GESTÃO (Seller)
        Route::post('/products', function () {
            return response()->json([
                'message' => 'Produto criado com sucesso',
                'product' => array_merge(request()->all(), [
                    'id' => rand(100, 999),
                    'created_at' => now()->toDateTimeString()
                ])
            ]);
        });
        
        Route::get('/my-products', function () {
            return response()->json([
                ['id' => 1, 'name' => 'Meu Produto 1', 'price' => 15000],
                ['id' => 2, 'name' => 'Meu Produto 2', 'price' => 28000]
            ]);
        });
    });
});

// Registro de utilizador
Route::post('/register', function () {
    return response()->json([
        'message' => 'Utilizador registrado com sucesso',
        'user' => [
            'id' => rand(1000, 9999),
            'name' => request('name'),
            'email' => request('email'),
            'type' => request('type', 'customer')
        ]
    ]);
});
