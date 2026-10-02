<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Ingreso;
use App\Models\Material;
use App\Support\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Roles y permisos del panel: el middleware VerificarPermiso exige el
 * permiso de config/permisos.php en cada ruta.
 */
class RolesPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
    }

    private function empleado(?string $rol = null, array $permisos = []): Employee
    {
        $empleado = Employee::create([
            'name' => 'Usuario ' . uniqid(),
            'email' => uniqid() . '@cycproenerg.com',
            'password' => Hash::make('secreto123'),
        ]);

        if ($rol) {
            $role = Role::firstOrCreate(['name' => $rol, 'guard_name' => Permisos::GUARD]);
            $role->syncPermissions($permisos);
            $empleado->assignRole($role);
        }

        return $empleado;
    }

    public function test_todas_las_rutas_del_panel_tienen_permiso_salvo_mi_perfil(): void
    {
        $libres = collect(app('router')->getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter(fn ($n) => $n && str_starts_with($n, 'employee.'))
            ->reject(fn ($n) => Permisos::deRuta($n) !== null)
            ->values()->all();

        $this->assertEqualsCanonicalizing(['employee.', 'employee.home'], $libres);
    }

    public function test_administrador_entra_a_todo(): void
    {
        $admin = $this->empleado(config('permisos.rol_administrador'));

        $this->actingAs($admin, 'employee')->get(route('employee.materiales.ingresos.index'))->assertOk();
        $this->actingAs($admin, 'employee')->get(route('employee.auditoria.index'))->assertOk();
        $this->actingAs($admin, 'employee')->get(route('employee.roles.index'))->assertOk();
    }

    public function test_sin_permiso_recibe_403_y_con_permiso_entra(): void
    {
        $almacen = $this->empleado('Almacén', ['ingresos.ver']);

        $this->actingAs($almacen, 'employee')->get(route('employee.materiales.ingresos.index'))->assertOk();
        $this->actingAs($almacen, 'employee')->get(route('employee.auditoria.index'))->assertForbidden();
        $this->actingAs($almacen, 'employee')->get(route('employee.usuarios.index'))->assertForbidden();
    }

    public function test_guardar_en_modal_distingue_crear_de_editar(): void
    {
        $material = Material::create([
            'codigo' => 'MAT-1', 'serie' => Material::SERIE, 'correlativo' => '00000001', 'descripcion' => 'CODO',
            'unidad' => 'UNID', 'precio_base' => 10, 'stock_inicial' => 0, 'stock_minimo' => 0, 'factor_metros_por_unidad' => 1,
        ]);
        $soloEditar = $this->empleado('Editor', ['catalogo.ver', 'catalogo.editar']);

        // Crear (sin id): no tiene catalogo.crear.
        $this->actingAs($soloEditar, 'employee')
            ->postJson(route('employee.materiales.items.store'), ['codigo' => 'MAT-2'])
            ->assertForbidden();

        // Editar (con id): sí puede (falla solo la validación, no el permiso).
        $this->actingAs($soloEditar, 'employee')
            ->postJson(route('employee.materiales.items.store'), ['id' => $material->id, 'codigo' => 'MAT-1'])
            ->assertStatus(422);
    }

    public function test_el_menu_oculta_lo_que_no_puede_abrir(): void
    {
        $almacen = $this->empleado('Almacén', ['ingresos.ver']);

        $this->actingAs($almacen, 'employee')->get(route('employee.home'))
            ->assertOk()
            ->assertSee(route('employee.materiales.ingresos.index'))
            ->assertDontSee(route('employee.auditoria.index'))
            ->assertDontSee(route('employee.usuarios.index'));
    }

    public function test_los_botones_sin_permiso_no_se_muestran(): void
    {
        $consulta = $this->empleado('Consulta', ['ingresos.ver']);

        $this->actingAs($consulta, 'employee')->get(route('employee.materiales.ingresos.index'))
            ->assertOk()
            ->assertDontSee('openCreate()', false);
    }

    public function test_no_se_puede_quitar_el_rol_al_unico_administrador(): void
    {
        $admin = $this->empleado(config('permisos.rol_administrador'));
        Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => Permisos::GUARD]);

        $this->actingAs($admin, 'employee')
            ->postJson(route('employee.usuarios.store'), [
                'id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email, 'rol' => 'Consulta',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rol');

        $this->assertTrue($admin->fresh()->esAdministrador());
    }

    public function test_usuario_creado_con_mayusculas_en_el_correo_puede_iniciar_sesion(): void
    {
        $admin = $this->empleado(config('permisos.rol_administrador'));
        Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => Permisos::GUARD]);

        $this->actingAs($admin, 'employee')
            ->postJson(route('employee.usuarios.store'), [
                'name' => 'José Lucero', 'email' => 'JLucero@Gmail.com', 'password' => 'clave-segura', 'rol' => 'Consulta',
            ])
            ->assertOk();

        $nuevo = Employee::where('name', 'José Lucero')->first();
        $this->assertSame('jlucero@gmail.com', $nuevo->email);

        // El login (Fortify) busca el correo en minúsculas.
        auth('employee')->logout();
        $this->assertTrue(auth('employee')->attempt(['email' => 'jlucero@gmail.com', 'password' => 'clave-segura']));
    }

    public function test_crear_rol_con_permisos_desde_la_pantalla(): void
    {
        $admin = $this->empleado(config('permisos.rol_administrador'));

        $this->actingAs($admin, 'employee')
            ->post(route('employee.roles.store'), ['name' => 'Almacén', 'permisos' => ['ingresos.crear']])
            ->assertRedirect(route('employee.roles.index'));

        $rol = Role::findByName('Almacén', Permisos::GUARD);
        // "ver" se agrega solo al marcar otra acción del módulo.
        $this->assertEqualsCanonicalizing(['ingresos.crear', 'ingresos.ver'], $rol->permissions->pluck('name')->all());
    }
}
