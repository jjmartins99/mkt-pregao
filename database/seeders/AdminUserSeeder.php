<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $admin = User::create([
            'name' => 'Administrador PREGÃO',
            'email' => 'admin@pregao.ao',
            'password' => Hash::make('admin123'),
            'type' => 'admin',
            'phone' => '+244 900 000 000',
            'nif' => '0000000000',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Criar alguns utilizadores de teste
        $seller = User::create([
            'name' => 'João Lojista',
            'email' => 'vendedor@pregao.ao',
            'password' => Hash::make('vendedor123'),
            'type' => 'seller',
            'phone' => '+244 911 111 111',
            'nif' => '1111111111',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $customer = User::create([
            'name' => 'Maria Cliente',
            'email' => 'cliente@pregao.ao',
            'password' => Hash::make('cliente123'),
            'type' => 'customer',
            'phone' => '+244 922 222 222',
            'nif' => '2222222222',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $driver = User::create([
            'name' => 'Carlos Motorista',
            'email' => 'motorista@pregao.ao',
            'password' => Hash::make('motorista123'),
            'type' => 'driver',
            'phone' => '+244 933 333 333',
            'nif' => '3333333333',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Utilizadores de teste criados:');
        $this->command->info('Admin: admin@pregao.ao / admin123');
        $this->command->info('Vendedor: vendedor@pregao.ao / vendedor123');
        $this->command->info('Cliente: cliente@pregao.ao / cliente123');
        $this->command->info('Motorista: motorista@pregao.ao / motorista123');
    }
}