<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTE DE AUTENTICAÇÃO ===\n";

$user = \App\Models\User::where('email', 'admin@pregao.ao')->first();

if (!$user) {
    echo "❌ Usuário não encontrado\n";
    exit;
}

echo "1. Usuário encontrado: " . $user->email . "\n";

// Teste a senha
$passwordTest = 'Admin@123';
$isPasswordValid = password_verify($passwordTest, $user->password);

echo "2. password_verify('Admin@123', hash): " . ($isPasswordValid ? '✅ TRUE' : '❌ FALSE') . "\n";
echo "   Hash: " . substr($user->password, 0, 30) . "...\n";

// Tente autenticar com Auth::attempt
$credentials = [
    'email' => 'admin@pregao.ao',
    'password' => 'Admin@123'
];

echo "3. Tentando Auth::attempt()...\n";
$attemptResult = Auth::attempt($credentials);
echo "   Resultado: " . ($attemptResult ? '✅ TRUE' : '❌ FALSE') . "\n";

if ($attemptResult) {
    $authenticatedUser = Auth::user();
    echo "   ✅ Usuário autenticado: " . $authenticatedUser->name . "\n";
    
    echo "4. Verificando métodos disponíveis:\n";
    echo "   - createToken existe? " . (method_exists($authenticatedUser, 'createToken') ? '✅ SIM' : '❌ NÃO') . "\n";
    
    if (method_exists($authenticatedUser, 'createToken')) {
        try {
            $token = $authenticatedUser->createToken('api-login')->plainTextToken;
            echo "5. ✅ Token criado com sucesso!\n";
            echo "   Token: " . substr($token, 0, 40) . "...\n";
        } catch (Exception $e) {
            echo "5. ❌ Erro ao criar token: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "4. Por que Auth::attempt() falhou?\n";
    echo "   Auth driver padrão: " . config('auth.defaults.guard') . "\n";
    
    $testUser = \App\Models\User::where('email', $credentials['email'])->first();
    if ($testUser && password_verify($credentials['password'], $testUser->password)) {
        echo "   ⚠️  password_verify() retorna TRUE, mas Auth::attempt() retorna FALSE\n";
        echo "   Isso pode indicar problema com o provider ou guard\n";
    }
}
