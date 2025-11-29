<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\Company;
use App\Models\User;

class StoresSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        $seller = User::where('email', 'vendedor@pregao.ao')->first();

        $stores = [
            [
                'company_id' => $company->id,
                'owner_id' => $seller->id,
                'name' => 'Supermercado Preço Baixo - Online',
                'description' => 'O seu supermercado online com os melhores preços de Luanda. Entregamos em toda a cidade.',
                'phone' => '+244 222 000 001',
                'email' => 'online@precobaixo.ao',
                'address' => 'Rua da Independência, 123, Maianga',
                'city' => 'Luanda',
                'postal_code' => '0000-001',
                'is_verified' => true,
                'business_hours' => [
                    'monday' => ['open' => true, 'open_time' => '08:00', 'close_time' => '20:00'],
                    'tuesday' => ['open' => true, 'open_time' => '08:00', 'close_time' => '20:00'],
                    'wednesday' => ['open' => true, 'open_time' => '08:00', 'close_time' => '20:00'],
                    'thursday' => ['open' => true, 'open_time' => '08:00', 'close_time' => '20:00'],
                    'friday' => ['open' => true, 'open_time' => '08:00', 'close_time' => '20:00'],
                    'saturday' => ['open' => true, 'open_time' => '09:00', 'close_time' => '18:00'],
                    'sunday' => ['open' => false, 'open_time' => '09:00', 'close_time' => '14:00'],
                ],
            ],
            [
                'company_id' => $company->id,
                'owner_id' => $seller->id,
                'name' => 'ElectroTech Angola',
                'description' => 'Loja especializada em electrónicos e electrodomésticos. As melhores marcas com garantia.',
                'phone' => '+244 222 000 002',
                'email' => 'vendas@electrotech.ao',
                'address' => 'Avenida 4 de Fevereiro, 789',
                'city' => 'Luanda',
                'postal_code' => '0000-002',
                'is_verified' => true,
                'business_hours' => [
                    'monday' => ['open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'tuesday' => ['open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'wednesday' => ['open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'thursday' => ['open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'friday' => ['open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'saturday' => ['open' => true, 'open_time' => '10:00', 'close_time' => '17:00'],
                    'sunday' => ['open' => false, 'open_time' => '10:00', 'close_time' => '14:00'],
                ],
            ],
            [
                'company_id' => $company->id,
                'owner_id' => $seller->id,
                'name' => 'Moda Elegante',
                'description' => 'Loja de moda com as últimas tendências. Roupas, calçado e acessórios para toda a família.',
                'phone' => '+244 222 000 003',
                'email' => 'contato@modaelegante.ao',
                'address' => 'Belas Shopping, Loja 15',
                'city' => 'Luanda',
                'postal_code' => '0000-003',
                'is_verified' => false,
                'business_hours' => [
                    'monday' => ['open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                    'tuesday' => ['open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                    'wednesday' => ['open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                    'thursday' => ['open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                    'friday' => ['open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                    'saturday' => ['open' => true, 'open_time' => '10:00', 'close_time' => '22:00'],
                    'sunday' => ['open' => true, 'open_time' => '12:00', 'close_time' => '20:00'],
                ],
            ],
        ];

        foreach ($stores as $storeData) {
            $storeData['slug'] = \Str::slug($storeData['name']);
            Store::create($storeData);
        }

        $this->command->info(count($stores) . ' lojas criadas com sucesso.');
    }
}