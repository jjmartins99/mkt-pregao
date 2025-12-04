<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api do início
if (strpos($path, '/api/') === 0) {
    $endpoint = substr($path, 4);
} else {
    $endpoint = $path;
}

// Leitura do body para POST
$input = [];
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
}

// Respostas pré-definidas
$responses = [
    'GET /api/test' => [
        'status' => 'online',
        'message' => 'API PREGÃO Marketplace funcionando',
        'timestamp' => date('Y-m-d H:i:s')
    ],
    
    'POST /api/login' => function() use ($input) {
        $users = [
            'admin@pregao.ao' => ['password' => 'admin123', 'type' => 'admin'],
            'vendedor@pregao.ao' => ['password' => 'vendedor123', 'type' => 'seller'],
            'cliente@pregao.ao' => ['password' => 'cliente123', 'type' => 'customer']
        ];
        
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (!isset($users[$email]) || $users[$email]['password'] !== $password) {
            http_response_code(401);
            return ['error' => 'Credenciais inválidas'];
        }
        
        return [
            'access_token' => 'test-' . bin2hex(random_bytes(16)),
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => 1,
                'name' => ucfirst($users[$email]['type']) . ' User',
                'email' => $email,
                'type' => $users[$email]['type']
            ]
        ];
    },
    
    'GET /api/user' => function() {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($auth, 'Bearer ')) {
            http_response_code(401);
            return ['error' => 'Token não fornecido'];
        }
        
        return [
            'user' => [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@pregao.ao',
                'type' => 'admin'
            ]
        ];
    },
    
    'POST /api/register' => function() use ($input) {
        return [
            'message' => 'Usuário registrado com sucesso',
            'user' => [
                'id' => rand(1000, 9999),
                'name' => $input['name'] ?? 'Novo Usuário',
                'email' => $input['email'] ?? 'user@example.com',
                'type' => $input['type'] ?? 'customer'
            ]
        ];
    },
    
    'GET /api/products' => [
        ['id' => 1, 'name' => 'Smartphone XYZ', 'price' => 25000, 'store_id' => 1],
        ['id' => 2, 'name' => 'Notebook ABC', 'price' => 75000, 'store_id' => 1],
        ['id' => 3, 'name' => 'Tablet DEF', 'price' => 15000, 'store_id' => 2]
    ],
    
    'GET /api/stores' => [
        ['id' => 1, 'name' => 'Tech Store Angola', 'slug' => 'tech-store'],
        ['id' => 2, 'name' => 'Electro Market', 'slug' => 'electro-market']
    ],
    
    'POST /api/cart/items' => function() use ($input) {
        return [
            'message' => 'Produto adicionado ao carrinho',
            'item' => [
                'id' => rand(100, 999),
                'product_id' => $input['product_id'] ?? 1,
                'quantity' => $input['quantity'] ?? 1,
                'store_id' => $input['store_id'] ?? 1
            ]
        ];
    },
    
    'GET /api/cart' => [
        'items' => [
            ['product_id' => 1, 'name' => 'Produto 1', 'quantity' => 2, 'price' => 1000]
        ],
        'total' => 2000
    ],
    
    'POST /api/orders' => function() use ($input) {
        return [
            'message' => 'Pedido criado com sucesso',
            'order' => [
                'id' => rand(10000, 99999),
                'status' => 'pending',
                'total' => 2000,
                'payment_method' => $input['payment_method'] ?? 'cash'
            ]
        ];
    },
    
    'GET /api/orders' => [
        ['id' => 1, 'status' => 'pending', 'total' => 2000],
        ['id' => 2, 'status' => 'processing', 'total' => 3500]
    ],
    
    'GET /api/admin/dashboard-stats' => [
        'total_users' => 150,
        'total_products' => 450,
        'total_orders' => 89,
        'total_revenue' => 1250000.50
    ],
    
    'GET /api/users' => [
        ['id' => 1, 'name' => 'Admin', 'email' => 'admin@pregao.ao', 'type' => 'admin'],
        ['id' => 2, 'name' => 'Seller', 'email' => 'vendedor@pregao.ao', 'type' => 'seller']
    ],
    
    'POST /api/products' => function() use ($input) {
        return [
            'message' => 'Produto criado com sucesso',
            'product' => array_merge($input, [
                'id' => rand(100, 999),
                'created_at' => date('Y-m-d H:i:s')
            ])
        ];
    },
    
    'GET /api/my-products' => [
        ['id' => 1, 'name' => 'Meu Produto 1', 'price' => 15000],
        ['id' => 2, 'name' => 'Meu Produto 2', 'price' => 28000]
    ]
];

// Encontra a resposta
$routeKey = "$method $endpoint";

if (isset($responses[$routeKey])) {
    $response = $responses[$routeKey];
    if (is_callable($response)) {
        $response = $response();
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode([
        'error' => 'Rota não encontrada',
        'method' => $method,
        'path' => $endpoint,
        'available_routes' => array_keys($responses)
    ]);
}
