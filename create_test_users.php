<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "👤 CRIANDO USUÁRIOS DE TESTE...\n\n";

$testUsers = [
    [
        'name' => 'Administrador do Sistema',
        'email' => 'admin@pregao.ao',
        'password' => 'admin123',
        'type' => 'admin',
        'phone' => '+244 999 999 999',
        'nif' => '9999999999'
    ],
    [
        'name' => 'Vendedor Principal',
        'email' => 'vendedor@pregao.ao', 
        'password' => 'vendedor123',
        'type' => 'seller',
        'phone' => '+244 922 222 222',
        'nif' => '8888888888'
    ],
    [
        'name' => 'Cliente Exemplo',
        'email' => 'cliente@pregao.ao',
        'password' => 'cliente123',
        'type' => 'customer',
        'phone' => '+244 933 333 333',
        'nif' => '7777777777'
    ]
];

foreach ($testUsers as $userData) {
    if (!User::where('email', $userData['email'])->exists()) {
        User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'type' => $userData['type'],
            'phone' => $userData['phone'],
            'nif' => $userData['nif'],
            'email_verified_at' => now()
        ]);
        echo "✅ " . $userData['name'] . " criado\n";
    } else {
        echo "ℹ️ " . $userData['name'] . " já existe\n";
    }
}

echo "\n📈 TOTAL DE USUÁRIOS: " . User::count() . "\n";
