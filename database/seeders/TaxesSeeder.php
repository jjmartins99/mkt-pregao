<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tax;
use App\Models\TaxRate;

class TaxesSeeder extends Seeder
{
    public function run()
    {
        // IVA padrão em Angola (14%)
        $iva = Tax::create([
            'name' => 'IVA',
            'rate' => 14.00,
            'is_active' => true,
            'is_default' => true,
            'metadata' => ['type' => 'vat', 'description' => 'Imposto sobre o Valor Acrescentado']
        ]);

        // Taxa para Luanda (poderia ter taxa diferente)
        TaxRate::create([
            'tax_id' => $iva->id,
            'country' => 'Angola',
            'state' => 'Luanda',
            'rate' => 14.00,
            'is_active' => true,
        ]);

        // Taxa padrão para todo o país
        TaxRate::create([
            'tax_id' => $iva->id,
            'country' => 'Angola',
            'rate' => 14.00,
            'is_active' => true,
        ]);

        // Imposto de consumo para produtos específicos (exemplo)
        $consumo = Tax::create([
            'name' => 'Imposto de Consumo',
            'rate' => 10.00,
            'is_active' => true,
            'is_default' => false,
            'metadata' => ['type' => 'consumption', 'description' => 'Imposto sobre produtos específicos']
        ]);

        $this->command->info('Impostos e taxas criados com sucesso.');
    }
}