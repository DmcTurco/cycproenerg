<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear datos de ejemplo para la tabla companies
        Company::create([
            'name' => 'CYCproenerg',
            'email' => 'admin@cycproenerg.com',
            'password' => Hash::make('*d1xJ:-[OB32'),
        ]);

        Company::create([
            'name' => 'CLB-Ingenieria',
            'email' => 'admin@clb-ingenieria.com',
            'password' => Hash::make('KQ3+DAu7<P3@'),
        ]);

        // Company::create([
        //     'name' => 'Company 3',
        //     'email' => 'company3@example.com',
        //     'password' => Hash::make('0000'),
        // ]);
    
    }
}
