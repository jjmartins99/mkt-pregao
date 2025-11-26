<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['name' => 'Unidade', 'symbol' => 'UN', 'is_active' => true],
            ['name' => 'Quilograma', 'symbol' => 'KG', 'is_active' => true],
            ['name' => 'Grama', 'symbol' => 'GR', 'is_active' => true],
            ['name' => 'Litro', 'symbol' => 'L', 'is_active' => true],
            ['name' => 'Metro', 'symbol' => 'M', 'is_active' => true],
            ['name' => 'Centímetro', 'symbol' => 'CM', 'is_active' => true],
            ['name' => 'Caixa', 'symbol' => 'CX', 'is_active' => true],
            ['name' => 'Grade', 'symbol' => 'GRD', 'is_active' => true],
            ['name' => 'Fardo', 'symbol' => 'FAR', 'is_active' => true],
            ['name' => 'Saco', 'symbol' => 'SACO', 'is_active' => true],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}