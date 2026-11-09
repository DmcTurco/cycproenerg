<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CompanySeeder::class);
        $this->call(EmployeeSeeder::class);
        $this->call(RolesPermisosSeeder::class);
        $this->call(RolInternasAlmacenSeeder::class);
        $this->call(PersonaCampoSeeder::class);
        // Control de Materiales: datos reales del Excel rev 03
        // (reemplaza a los antiguos MaterialSeeder y HerramientaSeeder).
        $this->call(ControlMaterialesExcelSeeder::class);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
