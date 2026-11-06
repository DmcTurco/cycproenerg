<?php

namespace Tests\Feature\ControlGeneral;

use App\Models\Employee;
use App\Support\Permisos;
use Database\Seeders\RolInternasAlmacenSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolInternasAlmacenSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_el_rol_con_permisos_que_existen_y_se_puede_repetir(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $this->seed(RolInternasAlmacenSeeder::class);
        $this->seed(RolInternasAlmacenSeeder::class);

        $rol = Role::where('name', RolInternasAlmacenSeeder::ROL)->where('guard_name', Permisos::GUARD)->sole();
        $this->assertEqualsCanonicalizing(RolInternasAlmacenSeeder::PERMISOS, $rol->permissions->pluck('name')->all());
        $this->assertEmpty(array_diff(RolInternasAlmacenSeeder::PERMISOS, Permisos::todos()), 'Hay permisos en el rol que no existen en config/permisos.php');
    }

    public function test_con_el_rol_entra_a_sus_areas_y_no_a_lo_demas(): void
    {
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);
        $this->seed(RolInternasAlmacenSeeder::class);
        $usuaria = Employee::create(['name' => 'Usuaria', 'email' => 'usuaria@cyc.com', 'password' => Hash::make('x')]);
        $usuaria->syncRoles([RolInternasAlmacenSeeder::ROL]);

        foreach (['employee.control-general.index', 'employee.control-general.resumen', 'employee.client.excel', 'employee.materiales.index', 'employee.materiales.ingresos.index'] as $ruta) {
            $this->actingAs($usuaria, 'employee')->get(route($ruta))->assertOk();
        }
        foreach (['employee.control-general.parametros.edit', 'employee.materiales.cierres.index', 'employee.usuarios.index', 'employee.auditoria.index'] as $ruta) {
            $this->actingAs($usuaria, 'employee')->get(route($ruta))->assertForbidden();
        }
    }
}
