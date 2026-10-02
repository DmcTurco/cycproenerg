<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Employee;
use App\Models\Feriado;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Auditoría: cada acción queda registrada con quién la hizo y qué cambió.
 */
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function empleado(): Employee
    {
        return $this->comoAdministrador(Employee::create([
            'name' => 'Ana Auditora',
            'email' => 'ana@cycproenerg.com',
            'password' => Hash::make('secreto123'),
        ]));
    }

    private function material(array $datos = []): Material
    {
        return Material::create(array_merge([
            'codigo' => 'MAT-001', 'serie' => Material::SERIE, 'correlativo' => '00000001',
            'descripcion' => 'CODO 1/2', 'unidad' => 'UNID', 'precio_base' => 10,
            'stock_inicial' => 5, 'stock_minimo' => 1, 'factor_metros_por_unidad' => 1,
        ], $datos));
    }

    public function test_crear_editar_y_eliminar_quedan_registrados_con_el_usuario(): void
    {
        $this->actingAs($this->empleado(), 'employee');

        $feriado = Feriado::create(['fecha' => '2026-12-25', 'descripcion' => 'Navidad']);
        $feriado->update(['descripcion' => 'Navidad (feriado)']);
        $feriado->delete();

        $registros = Auditoria::where('modulo', 'Feriado')->orderBy('id')->get();
        $this->assertSame(['creado', 'actualizado', 'eliminado_definitivo'], $registros->pluck('accion')->all());
        $this->assertTrue($registros->every(fn ($a) => $a->usuario_nombre === 'Ana Auditora'));
        $this->assertSame('Feriado', $registros[0]->modulo);

        // Solo el campo que cambió, con antes y después.
        $this->assertSame(['descripcion' => 'Navidad'], $registros[1]->antes);
        $this->assertSame(['descripcion' => 'Navidad (feriado)'], $registros[1]->despues);
    }

    public function test_soft_delete_se_registra_como_eliminado(): void
    {
        $this->actingAs($this->empleado(), 'employee');
        $material = $this->material();

        $material->delete();

        $this->assertSame('eliminado', Auditoria::latest('id')->value('accion'));
    }

    public function test_campos_calculados_no_generan_registros(): void
    {
        $material = $this->material();
        $antes = Auditoria::count();

        // Los precios de venta los recalcula el sistema solo: no es una acción.
        $material->forceFill(['precio_venta_sin_igv' => 99])->save();

        $this->assertSame($antes, Auditoria::count());
    }

    public function test_sin_registrar_no_deja_entradas(): void
    {
        Auditoria::sinRegistrar(fn () => $this->material());

        $this->assertSame(0, Auditoria::count());
    }

    public function test_login_fallido_guarda_el_correo_pero_no_la_contrasena(): void
    {
        $this->empleado();

        // En los tests Fortify registra el login sin prefijo (el prefijo
        // /employee se arma con la URL real de la petición, ver
        // FortifyServiceProvider); el listener de Failed es el mismo.
        $this->post('/login', ['email' => 'ana@cycproenerg.com', 'password' => 'mala-clave']);

        $registro = Auditoria::where('accion', 'login_fallido')->first();
        $this->assertNotNull($registro);
        $this->assertStringContainsString('ana@cycproenerg.com', $registro->descripcion);
        $this->assertStringNotContainsString('mala-clave', json_encode($registro->toArray()));
    }

    public function test_pantalla_de_auditoria_lista_y_filtra(): void
    {
        $this->actingAs($this->empleado(), 'employee');
        Feriado::create(['fecha' => '2026-12-25', 'descripcion' => 'Navidad']);
        $this->material();

        $this->get(route('employee.auditoria.index', ['modulo' => 'Feriado']))
            ->assertOk()
            ->assertSee('Feriado Navidad')
            ->assertDontSee('Material MAT-001');
    }
}
