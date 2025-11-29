<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserTypesSeeder extends Seeder
{
    public function run()
    {
        // Este seeder é principalmente para documentação
        // Os tipos são definidos no model User
        $this->command->info('Tipos de utilizador disponíveis: admin, seller, customer, driver');
    }
}