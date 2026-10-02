<?php

namespace Tests\Feature\ControlMateriales;

use App\Models\Cotizacion;
use App\Models\PersonaCampo;
use App\Models\Material;
use App\Models\ParametroControlMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CM-4/CM-5: Cotizacion::emitir() — equivalente a la macro GenerarPDF.
 * Cubre la diferencia de precio/IGV entre COTIZACION (contratista) y VALE
 * (personal directo), la numeración correlativa, y el comportamiento de
 * "corregir" (reemplaza el detalle, no crea un documento nuevo).
 */
class CotizacionEmitirTest extends TestCase
{
    use RefreshDatabase;

    private function material(array $overrides = []): Material
    {
        return Material::create(array_merge([
            'codigo' => 'MAT-001',
            'serie' => Material::SERIE,
            'correlativo' => Material::siguienteCorrelativo(),
            'descripcion' => 'Tubo de polietileno',
            'unidad' => 'RLL',
            'precio_base' => 100.0,
            'margen_pct' => 0.10,
            'stock_inicial' => 100.0,
            'stock_minimo' => 10.0,
            'factor_metros_por_unidad' => 1.0,
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        // igv = 0.18 por defecto del Excel.
        ParametroControlMaterial::actual();
    }

    public function test_cotizacion_a_contratista_usa_precio_de_venta_y_suma_igv(): void
    {
        $material = $this->material(['precio_base' => 100.0, 'margen_pct' => 0.10]); // venta S/IGV = 110
        $cuadrilla = PersonaCampo::create(['nombre' => 'Contratista X', 'tipo' => PersonaCampo::TIPO_CONTRATISTA]);

        $cotizacion = Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 2.0]], '2026-09-10');

        $this->assertFalse($cotizacion->es_vale);
        $this->assertSame(Cotizacion::ESTADO_PENDIENTE, $cotizacion->estado);
        $this->assertSame(220.0, $cotizacion->monto_sin_igv); // 2 x 110
        $this->assertSame(39.6, $cotizacion->igv); // 220 x 0.18
        $this->assertSame(259.6, $cotizacion->total_con_igv);
        $this->assertStringStartsWith('C&C-260910-', $cotizacion->numero);
        $this->assertSame(Cotizacion::SERIE_COTIZACION, $cotizacion->serie);
    }

    public function test_vale_a_personal_directo_usa_precio_vigente_sin_margen_ni_igv(): void
    {
        $material = $this->material(['precio_base' => 100.0, 'margen_pct' => 0.10]);
        $cuadrilla = PersonaCampo::create(['nombre' => 'Cuadrilla 1', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO]);

        $vale = Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 2.0]], '2026-07-16');

        $this->assertTrue($vale->es_vale);
        $this->assertSame(Cotizacion::ESTADO_VALE, $vale->estado);
        $this->assertSame(200.0, $vale->monto_sin_igv); // 2 x 100 (precio vigente, sin margen)
        $this->assertSame(0.0, $vale->igv);
        $this->assertSame(200.0, $vale->total_con_igv);
        $this->assertStringStartsWith('VALE-260716-', $vale->numero);
        $this->assertSame(Cotizacion::SERIE_VALE, $vale->serie);
    }

    public function test_numeracion_correlativa_por_prefijo_reinicia_por_tipo_y_fecha(): void
    {
        $material = $this->material();
        $contratista = PersonaCampo::create(['nombre' => 'Contratista X', 'tipo' => PersonaCampo::TIPO_CONTRATISTA]);
        $items = [['material_id' => $material->id, 'cantidad' => 1.0]];

        $c1 = Cotizacion::emitir($contratista, $items, '2026-09-10');
        $c2 = Cotizacion::emitir($contratista, $items, '2026-09-10');
        $c3 = Cotizacion::emitir($contratista, $items, '2026-09-11'); // otra fecha: reinicia

        $this->assertStringEndsWith('-01', $c1->numero);
        $this->assertStringEndsWith('-02', $c2->numero);
        $this->assertStringEndsWith('-01', $c3->numero);
    }

    public function test_correlativo_interno_de_8_digitos_es_unico_por_serie_y_no_se_reutiliza_al_corregir(): void
    {
        $material = $this->material();
        $cuadrilla = PersonaCampo::create(['nombre' => 'Contratista X', 'tipo' => PersonaCampo::TIPO_CONTRATISTA]);

        $c1 = Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 1.0]], '2026-09-10');
        $this->assertSame('00000001', $c1->correlativo);

        $corregida = Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 5.0]], '2026-09-10', $c1);
        $this->assertSame($c1->id, $corregida->id, 'corregir no debe crear un documento nuevo');
        $this->assertSame('00000001', $corregida->correlativo, 'el correlativo interno no cambia al corregir');
        $this->assertSame($c1->numero, $corregida->numero, 'el número visible tampoco cambia al corregir');
    }

    public function test_corregir_reemplaza_el_detalle_entero_y_no_toca_el_estado(): void
    {
        $material = $this->material();
        $cuadrilla = PersonaCampo::create(['nombre' => 'Contratista X', 'tipo' => PersonaCampo::TIPO_CONTRATISTA]);

        $original = Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 1.0]], '2026-09-10');
        $original->update(['estado' => Cotizacion::ESTADO_DESCONTADO, 'n_valorizacion' => 'VAL-01']);

        $corregida = Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 3.0]], '2026-09-10', $original->fresh());

        $this->assertCount(1, $corregida->detalles, 'el detalle viejo debe reemplazarse, no acumularse');
        $this->assertSame(3.0, $corregida->detalles->first()->cantidad);
        $this->assertSame(Cotizacion::ESTADO_DESCONTADO, $corregida->estado, 'corregir no debe tocar el estado ya marcado');
        $this->assertStringContainsString('MODIFICADA', $corregida->observacion);
    }

    public function test_emitir_descuenta_stock_de_inmediato_para_contratista_pero_no_para_vale(): void
    {
        $material = $this->material(['stock_inicial' => 100.0]);
        $contratista = PersonaCampo::create(['nombre' => 'Contratista X', 'tipo' => PersonaCampo::TIPO_CONTRATISTA]);
        $personalDirecto = PersonaCampo::create(['nombre' => 'Cuadrilla 1', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO]);

        Cotizacion::emitir($contratista, [['material_id' => $material->id, 'cantidad' => 10.0]], '2026-09-10');
        $this->assertSame(90.0, $material->stockActual(), 'la cotización a contratista SÍ descuenta al emitir');

        Cotizacion::emitir($personalDirecto, [['material_id' => $material->id, 'cantidad' => 10.0]], '2026-09-10');
        $this->assertSame(90.0, $material->stockActual(), 'el VALE no descuenta stock al emitir (recién al reportar EJECUTADO)');
    }
}
