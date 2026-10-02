<?php

namespace Tests;

use App\Models\Employee;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Da al empleado el rol Administrador (acceso total): los tests que
     * prueban un módulo, no los permisos, entran con él.
     */
    protected function comoAdministrador(Employee $empleado): Employee
    {
        $rol = Role::firstOrCreate(['name' => config('permisos.rol_administrador'), 'guard_name' => Permisos::GUARD]);
        $empleado->assignRole($rol);

        return $empleado;
    }
}
