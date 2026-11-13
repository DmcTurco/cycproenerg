<?php

namespace Tests\Feature\ControlGeneral;

use App\Models\Asesor;
use App\Models\ControlGeneral;
use App\Models\PersonaCampo;
use App\Models\Employee;
use App\Models\Empresa;
use App\Models\EstadoPortal;
use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\Ubicacion;
use App\Support\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ControlGeneralControllerTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $clb;
    private Empresa $cyc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);

        $this->clb = Empresa::create(['codigo' => 'CLB', 'nombre' => 'CLB INGENIERIA', 'numero_documento' => '20610320032']);
        $this->cyc = Empresa::create(['codigo' => 'CYC', 'nombre' => 'C & C PROYECTOS', 'numero_documento' => '20604329397']);
    }

    private function empleado(array $permisos): Employee
    {
        $e = Employee::create(['name' => 'Usuaria', 'email' => uniqid() . '@cyc.com', 'password' => Hash::make('x')]);
        $rol = Role::firstOrCreate(['name' => 'Internas ' . uniqid(), 'guard_name' => Permisos::GUARD]);
        $rol->syncPermissions($permisos);
        $e->assignRole($rol);

        return $e;
    }

    private function registro(string $etapa, Empresa $empresa, array $datos = [], string $estado = '02'): ControlGeneral
    {
        static $n = 0;
        $n++;
        $estadoPortal = EstadoPortal::firstOrCreate(['codigo' => $estado], ['nombre' => 'Estado ' . $estado, 'abreviatura' => 'E']);
        $cliente = Solicitante::create(['numero_documento' => (string) (40000000 + $n), 'tipo_documento' => 1, 'nombre' => "CLIENTE {$n}", 'celular' => '999888777']);
        $s = Solicitud::create([
            'numero_solicitud' => (string) (3800000 + $n),
            'numero_suministro' => (string) (6200000 + $n),
            'empresa_id' => $empresa->id,
            'solicitante_id' => $cliente->id,
            'estado_portal_id' => $estadoPortal->id,
        ]);
        Ubicacion::create(['solicitud_id' => $s->id, 'distrito' => 'ATE', 'provincia' => 'LIMA', 'ubicacion' => '-12.0,-77.0']);

        return ControlGeneral::create(array_merge(['solicitud_id' => $s->id, 'etapa' => $etapa], $datos));
    }

    public function test_lista_por_etapa_y_filtra_por_empresa(): void
    {
        $a = $this->registro(ControlGeneral::APROBADA, $this->clb, ['fecha_aprobacion' => now()->subDays(30)->toDateString()]);
        $b = $this->registro(ControlGeneral::APROBADA, $this->cyc, ['fecha_aprobacion' => now()->subDays(2)->toDateString()]);
        $this->registro(ControlGeneral::HABILITACION, $this->cyc);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.index'))
            ->assertOk()
            ->assertSee($a->solicitud->numero_solicitud)
            ->assertSee($b->solicitud->numero_solicitud)
            ->assertSee('EN DESFASE')
            ->assertSee('En plazo');

        $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.index', ['empresa' => 'CLB']))
            ->assertOk()
            ->assertSee($a->solicitud->numero_solicitud)
            ->assertDontSee($b->solicitud->numero_solicitud);

        $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.index', ['filtro' => ControlGeneral::PLAZO_DESFASE]))
            ->assertOk()
            ->assertSee($a->solicitud->numero_solicitud)
            ->assertDontSee($b->solicitud->numero_solicitud);
    }

    public function test_todas_las_pestanas_y_el_resumen_cargan(): void
    {
        foreach (array_keys(ControlGeneral::ETAPAS) as $etapa) {
            $this->registro($etapa, $this->cyc, ['accion' => $etapa === ControlGeneral::ANULACION ? 'Anular' : null]);
        }
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        foreach (array_keys(ControlGeneral::ETAPAS) as $etapa) {
            $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.index', ['etapa' => $etapa]))->assertOk();
        }

        $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.resumen'))
            ->assertOk()
            ->assertSee('Total aprobadas');
    }

    public function test_guardar_una_celda_y_mover_de_etapa(): void
    {
        $cg = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $ana = Asesor::create(['company_id' => 1, 'nombre' => 'ANA PÉREZ']);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'asesor_id', 'valor' => $ana->id])
            ->assertOk()
            ->assertJson(['movida' => false]);
        $this->assertSame('ANA PÉREZ', $cg->fresh()->asesor->nombre);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'construida', 'valor' => '1'])
            ->assertOk()
            ->assertJson(['movida' => true, 'etapa' => ControlGeneral::TC_CONSTRUIDA]);
    }

    public function test_no_se_puede_editar_un_campo_de_otra_etapa_ni_un_valor_invalido(): void
    {
        $cg = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'habilitado', 'valor' => '1'])
            ->assertStatus(422);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'accion', 'valor' => 'Borrar'])
            ->assertStatus(422);

        $this->assertSame(ControlGeneral::APROBADA, $cg->fresh()->etapa);
    }

    public function test_solo_ver_no_puede_editar(): void
    {
        $cg = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $consulta = $this->empleado(['control_general.ver']);

        $this->actingAs($consulta, 'employee')->get(route('employee.control-general.index'))->assertOk();
        $this->actingAs($consulta, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'asesor_id', 'valor' => 1])
            ->assertForbidden();
    }

    public function test_buscar_abre_la_pestana_donde_esta_la_solicitud(): void
    {
        $cg = $this->registro(ControlGeneral::HABILITACION, $this->cyc);
        $usuaria = $this->empleado(['control_general.ver']);

        $this->actingAs($usuaria, 'employee')
            ->get(route('employee.control-general.index', ['search' => $cg->solicitud->numero_solicitud]))
            ->assertOk()
            ->assertViewHas('etapa', ControlGeneral::HABILITACION)
            ->assertSee($cg->solicitud->numero_solicitud);
    }

    public function test_buscar_varios_suministros_o_solicitudes_a_la_vez(): void
    {
        $a = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $b = $this->registro(ControlGeneral::APROBADA, $this->cyc);
        $c = $this->registro(ControlGeneral::APROBADA, $this->cyc);
        $usuaria = $this->empleado(['control_general.ver']);

        // Pegado de una columna del Excel: suministro de A, N° solicitud de B y uno que no existe.
        $pegado = $a->solicitud->numero_suministro . "\n" . $b->solicitud->numero_solicitud . "\n9999999";

        $this->actingAs($usuaria, 'employee')
            ->get(route('employee.control-general.index', ['terminos' => [$pegado]]))
            ->assertOk()
            ->assertSee($a->solicitud->numero_solicitud)
            ->assertSee($b->solicitud->numero_solicitud)
            ->assertDontSee($c->solicitud->numero_solicitud)
            ->assertViewHas('noEncontrados', ['9999999'])
            ->assertViewHas('terminos', [$a->solicitud->numero_suministro, $b->solicitud->numero_solicitud, '9999999']);
    }

    public function test_buscar_por_distrito_con_espacios(): void
    {
        $a = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $b = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $a->solicitud->ubicacion->update(['distrito' => 'LA VICTORIA']);
        $usuaria = $this->empleado(['control_general.ver']);

        $this->actingAs($usuaria, 'employee')
            ->get(route('employee.control-general.index', ['distritos' => ['LA VICTORIA']]))
            ->assertOk()
            ->assertSee($a->solicitud->numero_solicitud)
            ->assertDontSee($b->solicitud->numero_solicitud)
            ->assertViewHas('distritosDisponibles', fn ($d) => in_array('LA VICTORIA', $d, true));

        // Escrito como texto libre también lo encuentra (busca en el distrito).
        $this->actingAs($usuaria, 'employee')
            ->get(route('employee.control-general.index', ['terminos' => ['victoria']]))
            ->assertSee($a->solicitud->numero_solicitud)
            ->assertDontSee($b->solicitud->numero_solicitud);
    }

    public function test_guardar_asesor_cuadrilla_y_observacion_juntos_desde_el_modal(): void
    {
        $cg = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $ana = Asesor::create(['company_id' => 1, 'nombre' => 'ANA PÉREZ']);
        $cuadrilla = PersonaCampo::create(['nombre' => 'CUADRILLA 1', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'estado' => PersonaCampo::ESTADO_ACTIVO]);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), [
                'campos' => ['asesor_id' => $ana->id, 'cuadrilla_id' => $cuadrilla->id, 'observacion' => 'Pendiente de visita'],
            ])
            ->assertOk()
            ->assertJson(['movida' => false]);

        $cg->refresh();
        $this->assertSame('ANA PÉREZ', $cg->asesor->nombre);
        $this->assertSame('CUADRILLA 1', $cg->cuadrilla->nombre);
        $this->assertSame('Pendiente de visita', $cg->observacion);
    }

    public function test_el_modal_no_deja_guardar_un_campo_que_no_es_editable_en_la_etapa(): void
    {
        $cg = $this->registro(ControlGeneral::ANULACION, $this->clb);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), [
                'campos' => ['asesor_id' => 1],
            ])
            ->assertStatus(422);
    }

    public function test_no_se_puede_guardar_un_id_que_no_existe_ni_un_contratista_que_no_lo_es(): void
    {
        $cg = $this->registro(ControlGeneral::HABILITACION, $this->clb);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);
        $directo = PersonaCampo::create(['nombre' => 'DIRECTO', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'estado' => PersonaCampo::ESTADO_ACTIVO]);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'tecnico_id', 'valor' => 99999])
            ->assertStatus(422);

        // Un técnico sí puede ser personal directo...
        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'tecnico_id', 'valor' => $directo->id])
            ->assertOk();
        $this->assertSame($directo->id, $cg->fresh()->tecnico_id);

        $this->assertNull($cg->fresh()->contratista_id);
    }

    public function test_el_log_guarda_el_nombre_de_quien_se_eligio(): void
    {
        $cg = $this->registro(ControlGeneral::APROBADA, $this->clb);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);
        $ana = Asesor::create(['company_id' => 1, 'nombre' => 'ANA PÉREZ']);

        $this->actingAs($usuaria, 'employee')
            ->patchJson(route('employee.control-general.update', $cg), ['campo' => 'asesor_id', 'valor' => $ana->id])
            ->assertOk();

        $log = \App\Models\Auditoria::where('auditable_type', $cg->getMorphClass())->where('auditable_id', $cg->id)->where('accion', 'actualizado')->latest('id')->first();
        $this->assertSame('ANA PÉREZ', $log->despues['asesor']);
        $this->assertArrayNotHasKey('asesor_id', $log->despues);
    }

    public function test_parametros_se_guardan_y_requieren_permiso(): void
    {
        $admin = $this->empleado(['control_general.ver', 'control_general.parametros']);
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.parametros.edit'))->assertForbidden();

        $this->actingAs($admin, 'employee')->get(route('employee.control-general.parametros.edit'))->assertOk();
        $this->actingAs($admin, 'employee')
            ->put(route('employee.control-general.parametros.update'), ['dias_limite_aprobacion' => 30, 'ambito_departamentos' => ['LIMA']])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('employee.control-general.parametros.edit'));

        $p = \App\Models\ParametroControlGeneral::actual();
        $this->assertSame(30, $p->dias_limite_aprobacion);
        $this->assertSame(['LIMA'], $p->ambito_departamentos);
    }

    public function test_sin_permiso_no_entra(): void
    {
        $otro = $this->empleado(['ingresos.ver']);

        $this->actingAs($otro, 'employee')->get(route('employee.control-general.index'))->assertForbidden();
    }
}
