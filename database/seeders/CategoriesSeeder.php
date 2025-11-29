<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        // Categorias principais (pai)
        $mainCategories = [
            [
                'name' => 'Alimentação e Bebidas',
                'description' => 'Produtos alimentares e bebidas',
                'order' => 1,
                'is_featured' => true,
            ],
            [
                'name' => 'Electrónicos',
                'description' => 'Aparelhos electrónicos e tecnologia',
                'order' => 2,
                'is_featured' => true,
            ],
            [
                'name' => 'Moda e Vestuário',
                'description' => 'Roupas, calçado e acessórios',
                'order' => 3,
                'is_featured' => true,
            ],
            [
                'name' => 'Casa e Jardim',
                'description' => 'Produtos para casa e jardim',
                'order' => 4,
                'is_featured' => false,
            ],
            [
                'name' => 'Saúde e Beleza',
                'description' => 'Produtos de saúde, beleza e cuidados pessoais',
                'order' => 5,
                'is_featured' => false,
            ],
            [
                'name' => 'Desporto e Lazer',
                'description' => 'Artigos desportivos e de lazer',
                'order' => 6,
                'is_featured' => false,
            ],
            [
                'name' => 'Automóvel',
                'description' => 'Peças e acessórios para automóveis',
                'order' => 7,
                'is_featured' => false,
            ],
            [
                'name' => 'Serviços',
                'description' => 'Serviços diversos',
                'order' => 8,
                'is_featured' => false,
            ],
        ];

        $createdCategories = [];

        foreach ($mainCategories as $mainCategory) {
            $category = Category::create($mainCategory);
            $createdCategories[$mainCategory['name']] = $category;
        }

        // Subcategorias para Alimentação e Bebidas
        $foodSubcategories = [
            ['name' => 'Fruta e Legumes', 'order' => 1],
            ['name' => 'Lacticínios e Ovos', 'order' => 2],
            ['name' => 'Carne e Peixe', 'order' => 3],
            ['name' => 'Padaria e Pastelaria', 'order' => 4],
            ['name' => 'Bebidas Alcoólicas', 'order' => 5],
            ['name' => 'Bebidas Não Alcoólicas', 'order' => 6],
            ['name' => 'Congelados', 'order' => 7],
            ['name' => 'Produtos Secos', 'order' => 8],
        ];

        foreach ($foodSubcategories as $subcategory) {
            Category::create([
                'parent_id' => $createdCategories['Alimentação e Bebidas']->id,
                'name' => $subcategory['name'],
                'description' => $subcategory['name'],
                'order' => $subcategory['order'],
            ]);
        }

        // Subcategorias para Electrónicos
        $electronicsSubcategories = [
            ['name' => 'Telemóveis e Tablets', 'order' => 1],
            ['name' => 'Computadores e Portáteis', 'order' => 2],
            ['name' => 'Televisões e Áudio', 'order' => 3],
            ['name' => 'Electrodomésticos', 'order' => 4],
            ['name' => 'Videojogos', 'order' => 5],
            ['name' => 'Acessórios Electrónicos', 'order' => 6],
        ];

        foreach ($electronicsSubcategories as $subcategory) {
            Category::create([
                'parent_id' => $createdCategories['Electrónicos']->id,
                'name' => $subcategory['name'],
                'description' => $subcategory['name'],
                'order' => $subcategory['order'],
            ]);
        }

        // Subcategorias para Moda
        $fashionSubcategories = [
            ['name' => 'Roupa Masculina', 'order' => 1],
            ['name' => 'Roupa Feminina', 'order' => 2],
            ['name' => 'Roupa Infantil', 'order' => 3],
            ['name' => 'Calçado', 'order' => 4],
            ['name' => 'Bolsas e Acessórios', 'order' => 5],
            ['name' => 'Joalharia e Relógios', 'order' => 6],
        ];

        foreach ($fashionSubcategories as $subcategory) {
            Category::create([
                'parent_id' => $createdCategories['Moda e Vestuário']->id,
                'name' => $subcategory['name'],
                'description' => $subcategory['name'],
                'order' => $subcategory['order'],
            ]);
        }

        $this->command->info('Categorias e subcategorias criadas com sucesso.');
    }
}