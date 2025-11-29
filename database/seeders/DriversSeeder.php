<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\User;

class DriversSeeder extends Seeder
{
    public function run()
    {
        $driverUser = User::where('email', 'motorista@pregao.ao')->first();
        $company = \App\Models\Company::first();

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'company_id' => $company->id,
            'driving_license' => '123456789AO',
            'license_photo' => 'drivers/license/sample.jpg',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
            'rating' => 4.5,
            'total_ratings' => 10,
            'total_deliveries' => 45,
            'total_earnings' => 125000.00,
        ]);

        Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'color' => 'Branco',
            'plate_number' => 'LD-01-23-AB',
            'type' => 'car',
            'capacity_kg' => 200.00,
            'capacity_volume' => 2.5,
            'insurance_number' => 'INS123456',
            'insurance_expiry' => now()->addYear(),
            'is_active' => true,
        ]);

        $this->command->info('Perfil de motorista criado com sucesso.');
    }
}