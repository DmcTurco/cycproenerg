<?php

namespace Tests\Feature\ControlMateriales;

use App\Models\Employee;
use App\Models\InventarioFisicoDetalle;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CM-10: InventarioFisicoController@store — código NUEVO de esta sesión.
 * Regla clave (igual que el Excel): solo se guarda una fila de detalle por
 * cada ítem que EFECTIVAMENTE se contó (conteo_fisico lleno); los que se
 * dejaron en blanco no generan fila.
 */
class InventarioFisicoStoreTest extends TestCase
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

    private function material(array $overrides = []): Material
    {
        return Material::create(array_merge([
            'codigo' => 'MAT-001',
            'serie' => Material::SERIE,
            'correlativo' => Material::siguienteCorrelativo(),
            'descripcion' => 'Tubo de polietileno',
            'unidad' => 'RLL',
            'precio_base' => 100.0,
            'stock_inicial' => 50.0,
            'stock_minimo' => 10.0,
            'factor_metros_por_unidad' => 1.0,
        ], $overrides));
    }

    public function test_solo_guarda_detalle_de_los_materiales_que_se_contaron(): void
    {
        $contado = $this->material(['codigo' => 'MAT-CONTADO']);
        $noContado = $this->material(['codigo' => 'MAT-NO-CONTADO']);

        $respuesta = $this->actingAs($this->employee(), 'employee')->post(route('employee.materiales.inventario-fisico.store'), [
            'fecha' => '2026-09-22',
            'realizado_por' => 'Almacenero Test',
            'materiales' => [
                ['material_id' => $contado->id, 'conteo_fisico' => 45.0, 'observacion_ubicacion' => 'OK'],
                ['material_id' => $noContado->id, 'conteo_fisico' => ''],
            ],
            'herramientas' => [],
        ]);

        $respuesta->assertRedirect();
        $this->assertDatabaseCount('inventario_fisico_detalles', 1);
        $detalle = InventarioFisicoDetalle::first();
        $this->assertSame($contado->id, $detalle->material_id);
        $this->assertSame(50.0, (float) $detalle->stock_sistema);
        $this->assertSame(45.0, (float) $detalle->conteo_fisico);
        $this->assertSame(-5.0, (float) $detalle->diferencia);
    }

    public function test_diferencia_es_conteo_fisico_menos_stock_del_sistema(): void
    {
        $material = $this->material(['stock_inicial' => 50.0]);

        $this->actingAs($this->employee(), 'employee')->post(route('employee.materiales.inventario-fisico.store'), [
            'fecha' => '2026-09-22',
            'realizado_por' => 'Almacenero Test',
            'materiales' => [
                ['material_id' => $material->id, 'conteo_fisico' => 55.0],
            ],
        ]);

        $this->assertSame(5.0, (float) InventarioFisicoDetalle::first()->diferencia);
    }
}
