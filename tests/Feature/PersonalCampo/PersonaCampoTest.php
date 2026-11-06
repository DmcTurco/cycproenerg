<?php

namespace Tests\Feature\PersonalCampo;

use App\Models\Employee;
use App\Models\PersonaCampo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Personal de campo: una sola tabla para lo que antes eran Técnicos y
 * Cuadrillas. Cubre la pantalla unificada (pestañas por tipo, alta con y
 * sin acceso a la app) y el login de la app con la tabla nueva.
 */
class PersonaCampoTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        return $this->comoAdministrador(Employee::create([
            'name' => 'Turco',
            'email' => 'turco@example.com',
            'password' => bcrypt('secret'),
        ]));
    }

    public function test_listado_separa_personal_directo_y_contratistas_por_pestana(): void
    {
        PersonaCampo::create(['nombre' => 'Julio Vargas', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO]);
        PersonaCampo::create(['nombre' => 'Gas Perú SAC', 'tipo' => PersonaCampo::TIPO_CONTRATISTA]);

        $this->actingAs($this->employee(), 'employee');

        $this->get(route('employee.technicals.index'))
            ->assertOk()
            ->assertSee('Julio Vargas')
            ->assertDontSee('Gas Perú SAC');

        $this->get(route('employee.technicals.index', ['tipo' => PersonaCampo::TIPO_CONTRATISTA]))
            ->assertOk()
            ->assertSee('Gas Perú SAC')
            ->assertDontSee('Julio Vargas');
    }

    public function test_registrar_sin_email_no_da_acceso_a_la_app(): void
    {
        $this->actingAs($this->employee(), 'employee')
            ->post(route('employee.technicals.store'), [
                'nombre' => 'Redes del Sur EIRL',
                'tipo' => PersonaCampo::TIPO_CONTRATISTA,
                'estado' => 'ACTIVO',
                'tipo_documento' => '2',
                'numero_documento' => '20601234562',
                'email' => '',
                'password' => '',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $persona = PersonaCampo::firstWhere('numero_documento', '20601234562');
        $this->assertFalse($persona->usaApp());
    }

    public function test_dar_acceso_a_la_app_exige_contrasena(): void
    {
        $this->actingAs($this->employee(), 'employee')
            ->post(route('employee.technicals.store'), [
                'nombre' => 'Carlos Rodríguez',
                'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
                'estado' => 'ACTIVO',
                'email' => 'carlos@example.com',
                'password' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_quitar_el_email_quita_el_acceso_y_cierra_sesiones(): void
    {
        $persona = PersonaCampo::create([
            'nombre' => 'Carlos Rodríguez',
            'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
            'email' => 'carlos@example.com',
            'password' => Hash::make('secreto123'),
        ]);
        $persona->createToken('auth_token');

        $this->actingAs($this->employee(), 'employee')
            ->post(route('employee.technicals.store'), [
                'id' => $persona->id,
                'nombre' => 'Carlos Rodríguez',
                'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
                'estado' => 'ACTIVO',
                'email' => '',
            ])
            ->assertOk();

        $persona->refresh();
        $this->assertNull($persona->password);
        $this->assertSame(0, $persona->tokens()->count());
    }

    public function test_contratista_con_acceso_puede_iniciar_sesion_en_la_app(): void
    {
        PersonaCampo::create([
            'nombre' => 'Gas Perú SAC',
            'tipo' => PersonaCampo::TIPO_CONTRATISTA,
            'email' => 'gasperu@example.com',
            'password' => Hash::make('secreto123'),
        ]);

        $this->postJson('/api/login', ['email' => 'gasperu@example.com', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonStructure(['tecnico' => ['id', 'nombre', 'tipo'], 'token']);
    }

    public function test_persona_inactiva_no_puede_iniciar_sesion_en_la_app(): void
    {
        PersonaCampo::create([
            'nombre' => 'Carlos Rodríguez',
            'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
            'estado' => PersonaCampo::ESTADO_INACTIVO,
            'email' => 'carlos@example.com',
            'password' => Hash::make('secreto123'),
        ]);

        $this->postJson('/api/login', ['email' => 'carlos@example.com', 'password' => 'secreto123'])
            ->assertStatus(403);
    }

    public function test_login_devuelve_usuario_con_tipo(): void
    {
        PersonaCampo::create([
            'nombre' => 'Juan Pérez',
            'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
            'email' => 'juan@example.com',
            'password' => Hash::make('secreto123'),
        ]);

        $this->postJson('/api/login', ['email' => 'juan@example.com', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('usuario.tipo', PersonaCampo::TIPO_PERSONAL_DIRECTO)
            ->assertJsonStructure(['usuario' => ['id', 'nombre', 'tipo', 'estado', 'empresas'], 'tecnico', 'token']);
    }

    public function test_persona_desactivada_pierde_la_sesion_abierta_en_la_app(): void
    {
        $persona = PersonaCampo::create([
            'nombre' => 'Ana Torres',
            'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
            'email' => 'ana@example.com',
            'password' => Hash::make('secreto123'),
        ]);
        $token = $persona->createToken('auth_token')->plainTextToken;

        $this->withToken($token)->getJson('/api/profile')->assertOk();

        // Se la pasa a INACTIVO directo en la BD (sin pasar por el panel):
        // el token sigue existiendo, pero el middleware lo frena y lo revoca.
        $persona->update(['estado' => PersonaCampo::ESTADO_INACTIVO]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/solicitudes')->assertStatus(401);
        $this->assertSame(0, $persona->tokens()->count());
    }

    public function test_desactivar_desde_el_panel_cierra_sus_sesiones(): void
    {
        $persona = PersonaCampo::create([
            'nombre' => 'Luis Gómez',
            'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
            'email' => 'luis@example.com',
            'password' => Hash::make('secreto123'),
        ]);
        $persona->createToken('auth_token');

        $this->actingAs($this->employee(), 'employee')
            ->post(route('employee.technicals.store'), [
                'id' => $persona->id,
                'nombre' => 'Luis Gómez',
                'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO,
                'estado' => PersonaCampo::ESTADO_INACTIVO,
                'email' => 'luis@example.com',
            ])
            ->assertOk();

        $this->assertSame(0, $persona->tokens()->count());
    }
}
