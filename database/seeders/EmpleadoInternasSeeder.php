<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Usuaria que trabaja Control General y Materiales (rol "Internas, TC,
 * Habilitación y Almacén": sube el Excel del portal, sin eliminar, sin
 * parámetros y sin cierre de mes).
 *
 * Idempotente: si el correo ya existe no lo duplica ni le cambia la clave.
 * La clave se genera al azar y se muestra UNA sola vez al crearla.
 *
 *   php artisan db:seed --class=EmpleadoInternasSeeder --force
 */
class EmpleadoInternasSeeder extends Seeder
{
    public function run(): void
    {
        // Garantiza que el rol exista (también crea sus permisos).
        $this->call(RolInternasAlmacenSeeder::class);

        $correo = 'maricielo@cycproenerg.com';

        $empleado = Employee::where('email', $correo)->first();

        if ($empleado) {
            $empleado->syncRoles(RolInternasAlmacenSeeder::ROL);
            $this->command?->info("El usuario {$correo} ya existía: se dejó con el rol (la clave no se tocó).");

            return;
        }

        $clave = Str::random(12);

        $empleado = Employee::create([
            'name' => 'Maricielo',
            'email' => $correo,
            'password' => Hash::make($clave),
        ]);
        $empleado->assignRole(RolInternasAlmacenSeeder::ROL);

        $this->command?->info("Usuario creado: {$correo}");
        $this->command?->warn("Clave: {$clave}  (cópiala ahora, no se vuelve a mostrar)");
    }
}
