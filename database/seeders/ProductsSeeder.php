<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductPackaging;
use App\Models\Store;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Stock;

class ProductsSeeder extends Seeder
{
    public function run()
    {
        $store1 = Store::where('name', 'like', '%Preço Baixo%')->first();
        $store2 = Store::where('name', 'like', '%ElectroTech%')->first();
        $store3 = Store::where('name', 'like', '%Moda Elegante%')->first();

        $unitUn = Unit::where('symbol', 'UN')->first();
        $unitKg = Unit::where('symbol', 'KG')->first();
        $unitL = Unit::where('symbol', 'L')->first();

        // Categorias
        $catFrutas = Category::where('name', 'Fruta e Legumes')->first();
        $catBebidas = Category::where('name', 'Bebidas Não Alcoólicas')->first();
        $catTelemoveis = Category::where('name', 'Telemóveis e Tablets')->first();
        $catRoupaMasc = Category::where('name', 'Roupa Masculina')->first();
        $catCalcado = Category::where('name', 'Calçado')->first();

        // Marcas
        $brandSamsung = Brand::where('name', 'Samsung')->first();
        $brandApple = Brand::where('name', 'Apple')->first();
        $brandNike = Brand::where('name', 'Nike')->first();
        $brandCocaCola = Brand::where('name', 'Coca-Cola')->first();
        $brandCuca = Brand::where('name', 'Cuca')->first();

        $products = [
            // Produtos para supermercado
            [
                'store_id' => $store1->id,
                'name' => 'Maçãs Vermelhas',
                'description' => 'Maçãs vermelhas frescas e saborosas, importadas da África do Sul.',
                'sku' => 'PROD001',
                'barcode' => '1234567890123',
                'kind' => 'good',
                'category_id' => $catFrutas->id,
                'brand_id' => null,
                'unit_id' => $unitKg->id,
                'weight' => 1.0,
                'track_stock' => true,
                'requires_expiry' => true,
                'requires_batch' => false,
                'picking_policy' => 'fefo',
                'min_stock' => 10,
                'max_stock' => 100,
            ],
            [
                'store_id' => $store1->id,
                'name' => 'Coca-Cola 2L',
                'description' => 'Refrigerante Coca-Cola em garrafa de 2 litros.',
                'sku' => 'PROD002',
                'barcode' => '1234567890124',
                'kind' => 'good',
                'category_id' => $catBebidas->id,
                'brand_id' => $brandCocaCola->id,
                'unit_id' => $unitUn->id,
                'weight' => 2.1,
                'track_stock' => true,
                'requires_expiry' => true,
                'requires_batch' => true,
                'picking_policy' => 'fefo',
                'min_stock' => 24,
                'max_stock' => 200,
            ],
            [
                'store_id' => $store1->id,
                'name' => 'Cerveja Cuca 330ml',
                'description' => 'Cerveja Cuca em lata de 330ml, pack de 6 unidades.',
                'sku' => 'PROD003',
                'barcode' => '1234567890125',
                'kind' => 'good',
                'category_id' => $catBebidas->id,
                'brand_id' => $brandCuca->id,
                'unit_id' => $unitUn->id,
                'weight' => 2.5,
                'track_stock' => true,
                'requires_expiry' => true,
                'requires_batch' => true,
                'picking_policy' => 'fefo',
                'min_stock' => 12,
                'max_stock' => 100,
            ],

            // Produtos electrónicos
            [
                'store_id' => $store2->id,
                'name' => 'Samsung Galaxy S23',
                'description' => 'Smartphone Samsung Galaxy S23 com 256GB, 8GB RAM, câmara tripla.',
                'sku' => 'PROD004',
                'barcode' => '1234567890126',
                'kind' => 'good',
                'category_id' => $catTelemoveis->id,
                'brand_id' => $brandSamsung->id,
                'unit_id' => $unitUn->id,
                'weight' => 0.168,
                'track_stock' => true,
                'requires_expiry' => false,
                'requires_batch' => false,
                'picking_policy' => 'fifo',
                'min_stock' => 5,
                'max_stock' => 50,
            ],
            [
                'store_id' => $store2->id,
                'name' => 'iPhone 14 Pro',
                'description' => 'Apple iPhone 14 Pro 128GB, cor Deep Purple, com Face ID.',
                'sku' => 'PROD005',
                'barcode' => '1234567890127',
                'kind' => 'good',
                'category_id' => $catTelemoveis->id,
                'brand_id' => $brandApple->id,
                'unit_id' => $unitUn->id,
                'weight' => 0.206,
                'track_stock' => true,
                'requires_expiry' => false,
                'requires_batch' => false,
                'picking_policy' => 'fifo',
                'min_stock' => 3,
                'max_stock' => 30,
            ],

            // Produtos de moda
            [
                'store_id' => $store3->id,
                'name' => 'T-shirt Nike Dry-Fit',
                'description' => 'T-shirt desportiva Nike Dry-Fit, tecido tecnológico, várias cores disponíveis.',
                'sku' => 'PROD006',
                'barcode' => '1234567890128',
                'kind' => 'good',
                'category_id' => $catRoupaMasc->id,
                'brand_id' => $brandNike->id,
                'unit_id' => $unitUn->id,
                'weight' => 0.2,
                'track_stock' => true,
                'requires_expiry' => false,
                'requires_batch' => false,
                'picking_policy' => 'fifo',
                'min_stock' => 10,
                'max_stock' => 100,
            ],
            [
                'store_id' => $store3->id,
                'name' => 'Ténis Nike Air Max',
                'description' => 'Ténis desportivos Nike Air Max, amortecimento Air, várias numerações.',
                'sku' => 'PROD007',
                'barcode' => '1234567890129',
                'kind' => 'good',
                'category_id' => $catCalcado->id,
                'brand_id' => $brandNike->id,
                'unit_id' => $unitUn->id,
                'weight' => 0.8,
                'track_stock' => true,
                'requires_expiry' => false,
                'requires_batch' => false,
                'picking_policy' => 'fifo',
                'min_stock' => 5,
                'max_stock' => 50,
            ],

            // Serviços
            [
                'store_id' => $store2->id,
                'name' => 'Instalação de Software',
                'description' => 'Serviço de instalação e configuração de software em computadores e smartphones.',
                'sku' => 'SERV001',
                'barcode' => null,
                'kind' => 'service',
                'category_id' => $catTelemoveis->id,
                'brand_id' => null,
                'unit_id' => $unitUn->id,
                'weight' => null,
                'track_stock' => false,
                'requires_expiry' => false,
                'requires_batch' => false,
                'picking_policy' => 'fifo',
                'min_stock' => 0,
                'max_stock' => 0,
            ],
        ];

        $prices = [
            'PROD001' => ['price' => 450.00, 'compare_price' => 500.00], // Maçãs
            'PROD002' => ['price' => 350.00, 'compare_price' => null],    // Coca-Cola
            'PROD003' => ['price' => 1200.00, 'compare_price' => 1500.00], // Cuca
            'PROD004' => ['price' => 120000.00, 'compare_price' => 135000.00], // Samsung
            'PROD005' => ['price' => 150000.00, 'compare_price' => 165000.00], // iPhone
            'PROD006' => ['price' => 7500.00, 'compare_price' => 8500.00], // T-shirt
            'PROD007' => ['price' => 25000.00, 'compare_price' => 28000.00], // Ténis
            'SERV001' => ['price' => 2500.00, 'compare_price' => null],    // Instalação
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);
            
            // Adicionar preço
            if (isset($prices[$productData['sku']])) {
                ProductPrice::create([
                    'product_id' => $product->id,
                    'price' => $prices[$productData['sku']]['price'],
                    'compare_price' => $prices[$productData['sku']]['compare_price'],
                    'is_active' => true,
                ]);
            }

            // Adicionar embalagens para alguns produtos
            if ($productData['sku'] == 'PROD003') { // Cuca
                ProductPackaging::create([
                    'product_id' => $product->id,
                    'name' => 'Pack 6 unidades',
                    'barcode' => '1234567890130',
                    'conversion_factor' => 6,
                    'price' => 1200.00,
                    'min_quantity' => 1,
                    'is_active' => true,
                ]);
            }

            // Adicionar stock inicial para produtos físicos
            if ($product->isGood() && $product->track_stock) {
                $warehouse = $product->store->warehouses()->first();
                if ($warehouse) {
                    Stock::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => rand(20, 100),
                        'reserved_quantity' => 0,
                        'available_quantity' => rand(20, 100),
                        'min_stock' => $product->min_stock,
                        'max_stock' => $product->max_stock,
                    ]);
                }
            }
        }

        $this->command->info(count($products) . ' produtos criados com sucesso.');
    }
}