<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandsSeeder extends Seeder
{
    public function run()
    {
        $brands = [
            // Marcas de tecnologia
            ['name' => 'Samsung', 'description' => 'Tecnologia e eletrónicos', 'is_featured' => true],
            ['name' => 'Apple', 'description' => 'Tecnologia e dispositivos móveis', 'is_featured' => true],
            ['name' => 'Huawei', 'description' => 'Tecnologia e telecomunicações', 'is_featured' => true],
            ['name' => 'Xiaomi', 'description' => 'Tecnologia e dispositivos inteligentes', 'is_featured' => false],
            ['name' => 'Sony', 'description' => 'Eletrónicos e entretenimento', 'is_featured' => false],
            ['name' => 'LG', 'description' => 'Eletrónicos e electrodomésticos', 'is_featured' => false],
            
            // Marcas de alimentação
            ['name' => 'Nestlé', 'description' => 'Alimentação e nutrição', 'is_featured' => true],
            ['name' => 'Coca-Cola', 'description' => 'Bebidas e refrigerantes', 'is_featured' => true],
            ['name' => 'Pepsi', 'description' => 'Bebidas e snacks', 'is_featured' => false],
            
            // Marcas de moda
            ['name' => 'Zara', 'description' => 'Moda e vestuário', 'is_featured' => true],
            ['name' => 'H&M', 'description' => 'Moda acessível', 'is_featured' => false],
            ['name' => 'Nike', 'description' => 'Desporto e vestuário', 'is_featured' => true],
            ['name' => 'Adidas', 'description' => 'Desporto e calçado', 'is_featured' => true],
            
            // Marcas locais angolanas
            ['name' => 'Cuca', 'description' => 'Cerveja angolana', 'is_featured' => true],
            ['name' => 'Nocal', 'description' => 'Produtos alimentares', 'is_featured' => false],
            ['name' => 'Refriango', 'description' => 'Bebidas e refrigerantes', 'is_featured' => false],
            ['name' => 'Maboque', 'description' => 'Produtos de limpeza', 'is_featured' => false],
            
            // Marcas de automóvel
            ['name' => 'Toyota', 'description' => 'Automóveis e peças', 'is_featured' => true],
            ['name' => 'Mercedes-Benz', 'description' => 'Automóveis de luxo', 'is_featured' => false],
            ['name' => 'BMW', 'description' => 'Automóveis e motociclos', 'is_featured' => false],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }

        $this->command->info(count($brands) . ' marcas criadas.');
    }
}