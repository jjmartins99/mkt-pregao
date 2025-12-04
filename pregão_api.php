<?php
// Servidor API PREGÃO Marketplace - VERSÃO CORRIGIDA
// Todas as rotas da collection do Postman

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string se houver
$path = parse_url($uri, PHP_URL_PATH);

echo "<!-- Debug: method=$method, path=$path -->\n";

// Rotas disponíveis
$routes = [
    // 🔐 AUTENTICAÇÃO
    'POST /api/login' => function() {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $users = [
            'admin@pregao.ao' => ['pass' => 'admin123', 'type' => 'admin', 'name' => 'Administrador'],
            'vendedor@pregao.ao' => ['pass' => 'vendedor123', 'type' => 'seller', 'name' => 'Vendedor'],
            'cliente@pregao.ao' => ['pass' => 'cliente123', 'type' => 'customer', 'name' => 'Cliente']
        ];
        
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (!isset($users[$email]) || $users[$email]['pass'] !== $password) {
            http_response_code(401);
            return ['error' => 'Credenciais inválidas'];
        }
        
        $user = $users[$email];
        
        return [
            'access_token' => 'token-' . bin2hex(random_bytes(16)),
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => 1,
                'name' => $user['name'],
                'email' => $email,
                'type' => $user['type'],
                'phone' => '+244 999 999 999',
                'nif' => '9999999999'
            ]
        ];
    },
    
    'POST /api/register' => function() {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        return [
            'message' => 'Utilizador registrado com sucesso',
            'user' => [
                'id' => rand(1000, 9999),
                'name' => $input['name'] ?? 'Novo Utilizador',
                'email' => $input['email'] ?? 'user@example.com',
                'type' => $input['type'] ?? 'customer',
                'phone' => $input['phone'] ?? '+244 000 000 000',
                'nif' => $input['nif'] ?? '0000000000'
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
                'name' => 'Administrador',
                'email' => 'admin@pregao.ao',
                'type' => 'admin',
                'phone' => '+244 999 999 999',
                'nif' => '9999999999'
            ]
        ];
    },
    
    'POST /api/logout' => ['message' => 'Logout realizado com sucesso'],
    
    // 📦 PRODUTOS
    'GET /api/products' => [
        ['id' => 1, 'name' => 'Smartphone XYZ', 'price' => 25000, 'store_id' => 1],
        ['id' => 2, 'name' => 'Notebook ABC', 'price' => 75000, 'store_id' => 1],
        ['id' => 3, 'name' => 'Tablet DEF', 'price' => 15000, 'store_id' => 2]
    ],
    
    'GET /api/products/1' => [
        'id' => 1,
        'name' => 'Smartphone XYZ',
        'price' => 25000,
        'store_id' => 1,
        'description' => 'Smartphone de última geração'
    ],
    
    // 🏪 LOJAS
    'GET /api/stores' => [
        ['id' => 1, 'name' => 'Tech Store Angola', 'slug' => 'tech-store'],
        ['id' => 2, 'name' => 'Electro Market', 'slug' => 'electro-market']
    ],
    
    'GET /api/stores/1' => [
        'id' => 1,
        'name' => 'Tech Store Angola',
        'slug' => 'tech-store'
    ],
    
    // 🛒 CARRINHO & PEDIDOS
    'POST /api/cart/items' => function() {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
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
            ['id' => 101, 'product_id' => 1, 'quantity' => 2, 'price' => 25000]
        ],
        'total' => 50000
    ],
    
    'POST /api/orders' => function() {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        return [
            'message' => 'Pedido criado com sucesso',
            'order' => [
                'id' => rand(10000, 99999),
                'status' => 'pending',
                'total' => 51000,
                'payment_method' => $input['payment_method'] ?? 'cash'
            ]
        ];
    },
    
    'GET /api/orders' => [
        ['id' => 1001, 'status' => 'pending', 'total' => 51000],
        ['id' => 1002, 'status' => 'processing', 'total' => 35000]
    ],
    
    // 👨‍💼 ADMIN
    'GET /api/admin/dashboard-stats' => [
        'total_users' => 156,
        'total_products' => 478,
        'total_orders' => 92,
        'total_revenue' => 12850000
    ],
    
    'GET /api/users' => [
        ['id' => 1, 'name' => 'Admin', 'email' => 'admin@pregao.ao', 'type' => 'admin'],
        ['id' => 2, 'name' => 'Vendedor', 'email' => 'vendedor@pregao.ao', 'type' => 'seller']
    ],
    
    // 🏢 GESTÃO (Seller)
    'POST /api/products' => function() {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        return [
            'message' => 'Produto criado com sucesso',
            'product' => array_merge($input, [
                'id' => rand(100, 999),
                'created_at' => date('Y-m-d H:i:s')
            ])
        ];
    },
    
    'GET /api/my-products' => [
        ['id' => 10, 'name' => 'Meu Produto 1', 'price' => 15000],
        ['id' => 11, 'name' => 'Meu Produto 2', 'price' => 28000]
    ],
    
    // Teste - VERSÃO SIMPLIFICADA
    'GET /api/test' => [
        'status' => 'online',
        'message' => 'API PREGÃO Marketplace funcionando',
        'timestamp' => date('Y-m-d H:i:s')
    ],
    
    // Rota raiz
    'GET /' => [
        'name' => 'PREGÃO Marketplace API',
        'version' => '1.0.0',
        'status' => 'online',
        'message' => 'Use /api/ para acessar os endpoints'
    ]
];

// Verifica a rota
$routeKey = "$method $path";

if (isset($routes[$routeKey])) {
    $response = $routes[$routeKey];
    
    if (is_callable($response)) {
        $response = $response();
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Se não encontrou, verifica se é uma rota com parâmetros
if ($method === 'GET') {
    // Produtos com ID
    if (preg_match('#^/api/products/(\d+)$#', $path, $matches)) {
        $id = $matches[1];
        echo json_encode([
            'id' => $id,
            'name' => 'Produto ' . $id,
            'price' => $id * 1000,
            'description' => 'Descrição do produto ' . $id
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Lojas com ID
    if (preg_match('#^/api/stores/(\d+)$#', $path, $matches)) {
        $id = $matches[1];
        echo json_encode([
            'id' => $id,
            'name' => 'Loja ' . $id,
            'slug' => 'loja-' . $id
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

// Rota não encontrada
http_response_code(404);
echo json_encode([
    'error' => 'Rota não encontrada',
    'method' => $method,
    'path' => $path,
    'available_routes' => array_keys($routes)
], JSON_PRETTY_PRINT);
