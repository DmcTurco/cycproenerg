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
            'name' => 'My Company',
            'email' => 'company@gmail.com',
            'password' => Hash::make('0000'),
        ]);

        Company::create([
            'name' => 'Company 2',
            'email' => 'company2@example.com',
            'password' => Hash::make('0000'),
        ]);

        Company::create([
            'name' => 'Company 3',
            'email' => 'company3@example.com',
            'password' => Hash::make('0000'),
        ]);
    
    }
}
