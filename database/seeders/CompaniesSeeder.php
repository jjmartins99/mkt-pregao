<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\User;

class CompaniesSeeder extends Seeder
{
    public function run()
    {
        // Empresa individual
        $company1 = Company::create([
            'name' => 'Supermercado Preço Baixo',
            'nif' => '500000001',
            'email' => 'geral@precobaixo.ao',
            'phone' => '+244 222 000 001',
            'address' => 'Rua da Independência, 123, Maianga',
            'city' => 'Luanda',
            'postal_code' => '0000-001',
            'type' => 'individual',
            'is_active' => true,
        ]);

        // Empresa coletiva
        $company2 = Company::create([
            'name' => 'Grupo Comercial Angola',
            'nif' => '500000002',
            'email' => 'contacto@grupocomercial.ao',
            'phone' => '+244 222 000 002',
            'address' => 'Avenida 4 de Fevereiro, 456',
            'city' => 'Luanda',
            'postal_code' => '0000-002',
            'type' => 'collective',
            'is_active' => true,
        ]);

        // Associar utilizadores às empresas
        $seller = User::where('email', 'vendedor@pregao.ao')->first();
        $driver = User::where('email', 'motorista@pregao.ao')->first();

        $company1->users()->attach($seller->id, [
            'role' => 'owner',
            'is_active' => true,
        ]);

        $company2->users()->attach($seller->id, [
            'role' => 'manager',
            'is_active' => true,
        ]);

        $company2->users()->attach($driver->id, [
            'role' => 'employee',
            'is_active' => true,
        ]);

        // Criar filiais
        $branch1 = Branch::create([
            'company_id' => $company1->id,
            'name' => 'Loja Principal - Maianga',
            'code' => 'PRC001',
            'phone' => '+244 222 111 001',
            'email' => 'maianga@precobaixo.ao',
            'address' => 'Rua da Independência, 123, Maianga',
            'city' => 'Luanda',
            'postal_code' => '0000-001',
            'is_main' => true,
            'is_active' => true,
        ]);

        $branch2 = Branch::create([
            'company_id' => $company1->id,
            'name' => 'Loja - Kilamba',
            'code' => 'PRC002',
            'phone' => '+244 222 111 002',
            'email' => 'kilamba@precobaixo.ao',
            'address' => 'Avenida Pedro de Castro, Kilamba',
            'city' => 'Luanda',
            'postal_code' => '0000-003',
            'is_main' => false,
            'is_active' => true,
        ]);

        // Criar armazéns
        Warehouse::create([
            'company_id' => $company1->id,
            'branch_id' => $branch1->id,
            'name' => 'Armazém Central',
            'code' => 'W001',
            'address' => 'Zona Industrial, Viana',
            'city' => 'Luanda',
            'postal_code' => '0000-100',
            'contact_person' => 'João Silva',
            'contact_phone' => '+244 922 000 001',
            'area' => 500.00,
            'type' => 'main',
            'is_active' => true,
        ]);

        Warehouse::create([
            'company_id' => $company1->id,
            'branch_id' => $branch2->id,
            'name' => 'Armazém Kilamba',
            'code' => 'W002',
            'address' => 'Centro Distribuição Kilamba',
            'city' => 'Luanda',
            'postal_code' => '0000-101',
            'contact_person' => 'Maria Santos',
            'contact_phone' => '+244 922 000 002',
            'area' => 200.00,
            'type' => 'secondary',
            'is_active' => true,
        ]);

        $this->command->info('Empresas, filiais e armazéns criados com sucesso.');
    }
}