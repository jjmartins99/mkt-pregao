<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Boot do kernel console
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Teste de Configuração ===\n";
echo "Session driver (env): " . env('SESSION_DRIVER') . "\n";
echo "Session driver (config): " . config('session.driver') . "\n";
echo "App env: " . config('app.env') . "\n";
echo "Config cached: " . (file_exists(__DIR__.'/bootstrap/cache/config.php') ? 'YES' : 'NO') . "\n";

// Teste direto do container
$config = $app->make('config');
echo "Session via container: " . $config->get('session.driver') . "\n";
