<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\User;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        // Criar permissões padrão
        Permission::createDefaultPermissions();

        // Atribuir todas as permissões ao admin
        $admin = User::where('email', 'admin@pregao.ao')->first();
        $permissions = Permission::all();

        $admin->permissions()->sync($permissions->pluck('id'));

        $this->command->info('Permissões criadas e atribuídas ao administrador.');
    }
}