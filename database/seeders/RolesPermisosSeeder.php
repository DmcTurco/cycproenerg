<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Support\Permisos;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea los permisos de config/permisos.php y el rol Administrador.
 * Idempotente: se puede volver a correr cada vez que se agregan permisos
 * nuevos (no borra roles ni quita permisos ya asignados).
 *
 * Los empleados que todavía no tienen ningún rol quedan como
 * Administrador, para que nadie pierda el acceso al activar los permisos.
 */
class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permisos::todos() as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => Permisos::GUARD]);
        }

        $admin = Role::firstOrCreate(['name' => config('permisos.rol_administrador'), 'guard_name' => Permisos::GUARD]);
        $admin->syncPermissions(Permission::where('guard_name', Permisos::GUARD)->get());

        Employee::doesntHave('roles')->get()->each(fn (Employee $e) => $e->assignRole($admin));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
