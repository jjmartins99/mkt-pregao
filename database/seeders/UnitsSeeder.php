<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitsSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['name' => 'Unidade', 'symbol' => 'UN', 'description' => 'Unidade básica'],
            ['name' => 'Quilograma', 'symbol' => 'KG', 'description' => 'Peso em quilogramas'],
            ['name' => 'Grama', 'symbol' => 'GR', 'description' => 'Peso em gramas'],
            ['name' => 'Litro', 'symbol' => 'L', 'description' => 'Volume em litros'],
            ['name' => 'Mililitro', 'symbol' => 'ML', 'description' => 'Volume em mililitros'],
            ['name' => 'Metro', 'symbol' => 'M', 'description' => 'Comprimento em metros'],
            ['name' => 'Centímetro', 'symbol' => 'CM', 'description' => 'Comprimento em centímetros'],
            ['name' => 'Caixa', 'symbol' => 'CX', 'description' => 'Embalagem em caixa'],
            ['name' => 'Grade', 'symbol' => 'GRD', 'description' => 'Embalagem em grade'],
            ['name' => 'Fardo', 'symbol' => 'FAR', 'description' => 'Embalagem em fardo'],
            ['name' => 'Saco', 'symbol' => 'SACO', 'description' => 'Embalagem em saco'],
            ['name' => 'Pacote', 'symbol' => 'PC', 'description' => 'Embalagem em pacote'],
            ['name' => 'Dúzia', 'symbol' => 'DZ', 'description' => 'Doze unidades'],
            ['name' => 'Cento', 'symbol' => 'CT', 'description' => 'Cem unidades'],
            ['name' => 'Milheiro', 'symbol' => 'MIL', 'description' => 'Mil unidades'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

        $this->command->info(count($units) . ' unidades de medida criadas.');
    }
}