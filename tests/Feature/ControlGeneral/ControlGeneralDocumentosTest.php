<?php

namespace Tests\Feature\ControlGeneral;

use App\Models\ControlGeneral;
use App\Models\Employee;
use App\Models\Empresa;
use App\Models\EstadoPortal;
use App\Models\Instalacion;
use App\Models\PersonaCampo;
use App\Models\Proyecto;
use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\Ubicacion;
use App\Support\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

/**
 * Documentos de habilitación de una solicitud (acta, declaraciones juradas,
 * certificado y anexos): vista previa, datos manuales en JSON, ZIP y la
 * pantalla de Empresas (logo y sello).
 */
class ControlGeneralDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);
        Storage::fake('public');

        $this->empresa = Empresa::create([
            'codigo' => 'CLB', 'nombre' => 'CLB INGENIERIA Y PROYECTOS', 'numero_documento' => '20610320032',
            'registro_gas_natural' => '7265', 'categoria' => 'IG 3', 'direccion' => 'Av. Prolongación Chillón', 'distrito' => 'COMAS',
            'telefono' => '999 055 486', 'correo' => 'clb@mail.com', 'numero_poliza' => '15153416',
        ]);
    }

    private function empleado(array $permisos): Employee
    {
        $e = Employee::create(['name' => 'Usuaria', 'email' => uniqid() . '@cyc.com', 'password' => Hash::make('x')]);
        $rol = Role::firstOrCreate(['name' => 'Docs ' . uniqid(), 'guard_name' => Permisos::GUARD]);
        $rol->syncPermissions($permisos);
        $e->assignRole($rol);

        return $e;
    }

    private function habilitacion(string $categoria = 'Residencial', bool $conInstalador = true): ControlGeneral
    {
        $estado = EstadoPortal::firstOrCreate(['codigo' => '10'], ['nombre' => 'Estado 10', 'abreviatura' => 'E']);
        $cliente = Solicitante::create(['numero_documento' => '45781236', 'tipo_documento' => 1, 'nombre' => 'MARIA LOPEZ DIAZ', 'celular' => '987654321', 'correo_electronico' => 'maria@mail.com']);
        $s = Solicitud::create([
            'numero_solicitud' => '3800001', 'numero_suministro' => '6195509', 'empresa_id' => $this->empresa->id,
            'solicitante_id' => $cliente->id, 'estado_portal_id' => $estado->id, 'fecha_registro_portal' => '2026-10-05',
        ]);
        Ubicacion::create(['solicitud_id' => $s->id, 'direccion' => 'Av. Los Olivos 123 Piso 4 Int. A', 'distrito' => 'LOS OLIVOS', 'provincia' => 'LIMA', 'departamento' => 'LIMA']);
        Proyecto::create(['solicitud_id' => $s->id, 'categoria_proyecto' => $categoria]);
        Instalacion::create(['solicitud_id' => $s->id, 'tipo_instalacion' => 'Empotrada', 'numero_puntos_instalacion' => 2]);

        $datos = ['solicitud_id' => $s->id, 'etapa' => ControlGeneral::HABILITACION];
        if ($conInstalador) {
            $datos['tecnico_id'] = PersonaCampo::create([
                'nombre' => 'JIAN CARLO CASTRO LAIME', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'estado' => PersonaCampo::ESTADO_ACTIVO,
                'numero_documento' => '70000001', 'numero_registro' => '5051', 'categoria' => 'IG 2',
            ])->id;
        }

        return ControlGeneral::create($datos);
    }

    public function test_la_pantalla_lista_los_documentos_y_avisa_lo_que_falta(): void
    {
        $cg = $this->habilitacion('Residencial', false);

        $this->actingAs($this->empleado(['control_general.ver']), 'employee')
            ->get(route('employee.control-general.documentos', $cg))
            ->assertOk()
            // Los títulos viajan en el JSON de la pantalla (con las tildes escapadas),
            // por eso se busca solo la parte sin tildes.
            ->assertSee('F-GEA-007', false)
            ->assertSee('Certificado de garant', false)
            ->assertSee('Descargar todo (ZIP)')
            ->assertSee('No hay técnico elegido')
            ->assertSee('sello del representante legal');
    }

    public function test_cada_documento_sale_en_pdf(): void
    {
        $cg = $this->habilitacion();
        $usuaria = $this->empleado(['control_general.ver']);

        foreach (array_keys(config('documentos_habilitacion.documentos')) as $clave) {
            $r = $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.documentos.pdf', [$cg, $clave]));
            $r->assertOk();
            $this->assertSame('application/pdf', $r->headers->get('Content-Type'), $clave);
            $this->assertStringStartsWith('%PDF', $r->getContent(), $clave);
        }

        $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.documentos.pdf', [$cg, 'inventado']))->assertNotFound();
    }

    public function test_guardar_los_datos_manuales_en_json_y_se_ven_en_el_documento(): void
    {
        $cg = $this->habilitacion();
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($usuaria, 'employee')
            ->putJson(route('employee.control-general.documentos.guardar', $cg), [
                'n_instalacion' => '884455',
                'material' => 'COBRE',
                'items' => [['inst' => 'EMPOTRADO', 'tipo' => 'A', 'potencia' => '11KW'], ['inst' => '', 'tipo' => '', 'potencia' => '']],
                'puntos' => [['artefacto' => 'COCINA', 'estado' => 'Conectado', 'confinado' => 'NO']],
                'anexos' => ['residencial_b1'],
            ])
            ->assertOk()
            ->assertJsonPath('datos.n_instalacion', '884455');

        $json = $cg->fresh()->datos_documentos;
        $this->assertSame('COBRE', $json['material']);
        $this->assertCount(1, $json['items'], 'la fila vacía no se guarda');
        $this->assertSame(['residencial_b1'], $json['anexos']);

        // Una segunda guardada mezcla, no borra lo anterior.
        $this->actingAs($usuaria, 'employee')
            ->putJson(route('employee.control-general.documentos.guardar', $cg), ['lugar' => 'LIMA'])
            ->assertOk();
        $this->assertSame('884455', $cg->fresh()->datos_documentos['n_instalacion']);
        $this->assertSame('LIMA', $cg->fresh()->datos_documentos['lugar']);
    }

    public function test_no_acepta_valores_fuera_de_las_listas_ni_sin_permiso(): void
    {
        $cg = $this->habilitacion();
        $editora = $this->empleado(['control_general.ver', 'control_general.editar']);

        $this->actingAs($editora, 'employee')
            ->putJson(route('employee.control-general.documentos.guardar', $cg), ['material' => 'MADERA'])
            ->assertStatus(422);
        $this->actingAs($editora, 'employee')
            ->putJson(route('employee.control-general.documentos.guardar', $cg), ['anexos' => ['no_existe']])
            ->assertStatus(422);

        $this->actingAs($this->empleado(['control_general.ver']), 'employee')
            ->putJson(route('employee.control-general.documentos.guardar', $cg), ['lugar' => 'X'])
            ->assertForbidden();
    }

    public function test_el_zip_trae_todos_los_documentos_y_el_anexo_solo_si_se_eligio(): void
    {
        $cg = $this->habilitacion('Comercio');
        $usuaria = $this->empleado(['control_general.ver', 'control_general.editar']);

        $nombres = function () use ($cg, $usuaria) {
            $respuesta = $this->actingAs($usuaria, 'employee')->get(route('employee.control-general.documentos.zip', $cg));
            $respuesta->assertOk();
            $ruta = tempnam(sys_get_temp_dir(), 'zip');
            file_put_contents($ruta, $respuesta->baseResponse->getFile()->getContent());
            $zip = new ZipArchive();
            $zip->open($ruta);
            $lista = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $lista[] = $zip->getNameIndex($i);
            }
            $zip->close();
            @unlink($ruta);

            return $lista;
        };

        $this->assertCount(4, $nombres(), 'sin diseño elegido no va el anexo');

        $this->actingAs($usuaria, 'employee')
            ->putJson(route('employee.control-general.documentos.guardar', $cg), ['anexos' => ['comercio_1p', 'comercio_2p']])
            ->assertOk();
        $this->assertCount(5, $nombres());
    }

    public function test_empresas_se_editan_con_logo_y_sello(): void
    {
        $admin = $this->empleado(['empresas.ver', 'empresas.editar']);

        $this->actingAs($admin, 'employee')->get(route('employee.empresas.index'))->assertOk()->assertSee('CLB INGENIERIA');

        $this->actingAs($admin, 'employee')
            ->put(route('employee.empresas.update', $this->empresa), [
                'telefono' => '01 555 0000',
                'numero_poliza' => '777',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 100),
                'sello' => UploadedFile::fake()->image('sello.png', 200, 100),
            ])
            ->assertRedirect(route('employee.empresas.index'));

        $e = $this->empresa->fresh();
        $this->assertSame('01 555 0000', $e->telefono);
        Storage::disk('public')->assertExists($e->logo);
        Storage::disk('public')->assertExists($e->sello);
        $this->actingAs($admin, 'employee')->get(route('employee.empresas.imagen', [$e, 'logo']))->assertOk();

        // Quitar el sello borra el archivo.
        $sello = $e->sello;
        $this->actingAs($admin, 'employee')->put(route('employee.empresas.update', $e), ['quitar_sello' => '1'])->assertRedirect();
        $this->assertNull($e->fresh()->sello);
        Storage::disk('public')->assertMissing($sello);
    }

    public function test_solo_ver_no_puede_editar_empresas(): void
    {
        $consulta = $this->empleado(['empresas.ver']);

        $this->actingAs($consulta, 'employee')->get(route('employee.empresas.index'))->assertOk();
        $this->actingAs($consulta, 'employee')->put(route('employee.empresas.update', $this->empresa), ['telefono' => '1'])->assertForbidden();
    }
}
