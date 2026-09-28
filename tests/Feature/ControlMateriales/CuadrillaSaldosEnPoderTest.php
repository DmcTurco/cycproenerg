<?php

namespace Tests\Feature\ControlMateriales;

use App\Models\Cotizacion;
use App\Models\Cuadrilla;
use App\Models\Ejecutado;
use App\Models\Material;
use App\Models\ParametroControlMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CM-6: Cuadrilla::saldosEnPoder() — "EN SU PODER (por vales)" de
 * REGISTRO RAPIDO. Solo tiene sentido para PERSONAL DIRECTO.
 */
class CuadrillaSaldosEnPoderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ParametroControlMaterial::actual();
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
            'stock_inicial' => 1000.0,
            'stock_minimo' => 10.0,
            'factor_metros_por_unidad' => 100.0,
        ], $overrides));
    }

    public function test_saldo_convierte_el_vale_de_rollos_a_metros_con_el_factor(): void
    {
        $material = $this->material(['factor_metros_por_unidad' => 100.0]);
        $cuadrilla = Cuadrilla::create(['nombre' => 'Cuadrilla 1', 'tipo' => Cuadrilla::TIPO_PERSONAL_DIRECTO]);

        // Vale de 2 rollos = 200 metros en poder.
        Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 2.0]], '2026-09-01');

        $saldos = $cuadrilla->saldosEnPoder();
        $this->assertSame(200.0, $saldos[$material->id]);
    }

    public function test_salida_ejecutada_reduce_el_saldo_en_poder(): void
    {
        $material = $this->material(['factor_metros_por_unidad' => 100.0]);
        $cuadrilla = Cuadrilla::create(['nombre' => 'Cuadrilla 1', 'tipo' => Cuadrilla::TIPO_PERSONAL_DIRECTO]);

        Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 2.0]], '2026-09-01'); // 200 m en poder

        Ejecutado::create([
            'fecha' => '2026-09-05', 'cuadrilla_id' => $cuadrilla->id, 'material_id' => $material->id,
            'cantidad' => 150.0, 'movimiento' => Ejecutado::MOVIMIENTO_SALIDA,
        ]);

        $this->assertSame(50.0, $cuadrilla->saldosEnPoder()[$material->id]);
    }

    public function test_devolucion_ejecutada_TAMBIEN_reduce_el_saldo_en_poder(): void
    {
        // Contraintuitivo pero correcto: una DEVOLUCION significa que el
        // material salió de manos de la cuadrilla (volvió al almacén), así
        // que también deja de estar "en su poder" — igual que una SALIDA.
        $material = $this->material(['factor_metros_por_unidad' => 100.0]);
        $cuadrilla = Cuadrilla::create(['nombre' => 'Cuadrilla 1', 'tipo' => Cuadrilla::TIPO_PERSONAL_DIRECTO]);

        Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 2.0]], '2026-09-01'); // 200 m en poder

        Ejecutado::create([
            'fecha' => '2026-09-05', 'cuadrilla_id' => $cuadrilla->id, 'material_id' => $material->id,
            'cantidad' => 80.0, 'movimiento' => Ejecutado::MOVIMIENTO_DEVOLUCION,
        ]);

        $this->assertSame(120.0, $cuadrilla->saldosEnPoder()[$material->id]);
    }

    public function test_cotizacion_de_contratista_no_cuenta_como_vale_en_el_saldo(): void
    {
        $material = $this->material();
        $contratista = Cuadrilla::create(['nombre' => 'Contratista X', 'tipo' => Cuadrilla::TIPO_CONTRATISTA]);

        Cotizacion::emitir($contratista, [['material_id' => $material->id, 'cantidad' => 5.0]], '2026-09-01');

        $this->assertSame(0.0, $contratista->saldosEnPoder()[$material->id]);
    }

    public function test_numero_de_retiros_y_totales_de_registro_de_cuadrillas(): void
    {
        $material = $this->material(['precio_base' => 100.0, 'margen_pct' => 0.10]);
        $contratista = Cuadrilla::create(['nombre' => 'Contratista X', 'tipo' => Cuadrilla::TIPO_CONTRATISTA]);

        Cotizacion::emitir($contratista, [['material_id' => $material->id, 'cantidad' => 1.0]], '2026-09-01');
        Cotizacion::emitir($contratista, [['material_id' => $material->id, 'cantidad' => 1.0]], '2026-09-05');

        $this->assertSame(2, $contratista->numRetiros());
        $this->assertSame(220.0, $contratista->totalValorizado()); // 2 x 110 sin igv
        $this->assertSame(2 * 129.8, $contratista->pendienteDescuento()); // ambas siguen PENDIENTE
    }
}
