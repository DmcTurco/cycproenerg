<?php

namespace Tests\Feature\ControlMateriales;

use App\Models\Ingreso;
use App\Models\Material;
use App\Models\ParametroControlMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CM-1/CM-3: kardex de Material (stock, precio vigente, alertas) —
 * equivalente a las columnas de fórmula de la hoja CATALOGO. Estos
 * accessors son el corazón de todo el módulo (Cotizacion::emitir,
 * ControlMaterialesResumen y ControlMaterialesCierre dependen de ellos),
 * así que se prueban primero y de forma aislada.
 */
class MaterialKardexTest extends TestCase
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
            'margen_pct' => null,
            'stock_inicial' => 50.0,
            'stock_minimo' => 10.0,
            'factor_metros_por_unidad' => 100.0,
        ], $overrides));
    }

    public function test_stock_actual_suma_stock_inicial_mas_ingresos(): void
    {
        $material = $this->material(['stock_inicial' => 50.0]);

        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-01', 'cantidad' => 20.0]);
        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-05', 'cantidad' => 5.0]);

        $this->assertSame(25.0, $material->ingresos());
        $this->assertSame(75.0, $material->stockActual());
    }

    public function test_stock_actual_resta_salidas_de_contratista_al_emitir_la_cotizacion(): void
    {
        $material = $this->material(['stock_inicial' => 50.0]);
        $cuadrilla = \App\Models\Cuadrilla::create(['nombre' => 'Contratista X', 'tipo' => \App\Models\Cuadrilla::TIPO_CONTRATISTA]);

        \App\Models\Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 10.0]], '2026-09-10');

        $this->assertSame(40.0, $material->stockActual());
    }

    public function test_salidas_de_personal_directo_se_convierten_de_metros_a_rollos_con_el_factor(): void
    {
        // factor_metros_por_unidad = 100 (se entrega en rollos, se ejecuta en
        // metros): 250 metros ejecutados = 2.5 rollos de salida.
        $material = $this->material(['stock_inicial' => 50.0, 'factor_metros_por_unidad' => 100.0]);
        $cuadrilla = \App\Models\Cuadrilla::create(['nombre' => 'Cuadrilla 1', 'tipo' => \App\Models\Cuadrilla::TIPO_PERSONAL_DIRECTO]);

        \App\Models\Ejecutado::create([
            'fecha' => '2026-09-10',
            'tipo_trabajo' => 'INSTALACION',
            'cuadrilla_id' => $cuadrilla->id,
            'material_id' => $material->id,
            'cantidad' => 250.0,
            'movimiento' => \App\Models\Ejecutado::MOVIMIENTO_SALIDA,
        ]);

        $this->assertSame(2.5, $material->salidas());
        $this->assertSame(47.5, $material->stockActual());
    }

    public function test_devolucion_de_personal_directo_resta_de_las_salidas_y_repone_stock(): void
    {
        $material = $this->material(['stock_inicial' => 50.0, 'factor_metros_por_unidad' => 100.0]);
        $cuadrilla = \App\Models\Cuadrilla::create(['nombre' => 'Cuadrilla 1', 'tipo' => \App\Models\Cuadrilla::TIPO_PERSONAL_DIRECTO]);

        \App\Models\Ejecutado::create([
            'fecha' => '2026-09-10', 'cuadrilla_id' => $cuadrilla->id, 'material_id' => $material->id,
            'cantidad' => 250.0, 'movimiento' => \App\Models\Ejecutado::MOVIMIENTO_SALIDA,
        ]);
        \App\Models\Ejecutado::create([
            'fecha' => '2026-09-12', 'cuadrilla_id' => $cuadrilla->id, 'material_id' => $material->id,
            'cantidad' => 100.0, 'movimiento' => \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION,
        ]);

        // (250 - 100) / 100 = 1.5 rollos netos de salida.
        $this->assertSame(1.5, $material->salidas());
        $this->assertSame(48.5, $material->stockActual());
    }

    public function test_estado_segun_stock_actual_vs_stock_minimo(): void
    {
        $sinStock = $this->material(['codigo' => 'MAT-A', 'stock_inicial' => 0.0, 'stock_minimo' => 5.0]);
        $reponer = $this->material(['codigo' => 'MAT-B', 'stock_inicial' => 5.0, 'stock_minimo' => 5.0]);
        $ok = $this->material(['codigo' => 'MAT-C', 'stock_inicial' => 20.0, 'stock_minimo' => 5.0]);

        $this->assertSame('SIN STOCK', $sinStock->estado());
        $this->assertSame('REPONER', $reponer->estado());
        $this->assertSame('OK', $ok->estado());
    }

    public function test_precio_vigente_solo_sube_nunca_baja_del_precio_base(): void
    {
        $material = $this->material(['precio_base' => 100.0]);

        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-01', 'cantidad' => 1, 'precio_compra' => 80.0]);
        $this->assertSame(100.0, $material->precioVigente(), 'un ingreso más BARATO que el base no debe bajar el vigente');

        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-05', 'cantidad' => 1, 'precio_compra' => 120.0]);
        $this->assertSame(120.0, $material->precioVigente(), 'un ingreso más CARO que el base debe subir el vigente');
    }

    public function test_ultimo_precio_ingresos_usa_el_mas_reciente_por_fecha_no_por_orden_de_insercion(): void
    {
        $material = $this->material();

        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-10', 'cantidad' => 1, 'precio_compra' => 150.0]);
        // Insertado DESPUÉS pero con fecha ANTERIOR: debe seguir ganando el de fecha 09-10.
        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-01', 'cantidad' => 1, 'precio_compra' => 90.0]);

        $this->assertSame(150.0, $material->ultimoPrecioIngresos());
    }

    public function test_alerta_de_precio_del_material_compara_ultimo_ingreso_contra_base(): void
    {
        $material = $this->material(['precio_base' => 100.0]);

        $this->assertNull($material->alertaPrecio(), 'sin ningún ingreso todavía, no hay alerta');

        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-01', 'cantidad' => 1, 'precio_compra' => 100.0]);
        $this->assertSame('ESTABLE', $material->alertaPrecio());

        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-02', 'cantidad' => 1, 'precio_compra' => 130.0]);
        $this->assertSame('SUBIO: vigente actualizado', $material->alertaPrecio());

        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-03', 'cantidad' => 1, 'precio_compra' => 40.0]);
        $this->assertSame('BAJO: decidir si actualizar BASE', $material->alertaPrecio());
    }

    public function test_margen_efectivo_usa_el_propio_del_material_si_existe_si_no_el_general(): void
    {
        ParametroControlMaterial::actual(); // crea la fila con margen_general = 0.10

        $sinMargenPropio = $this->material(['codigo' => 'MAT-SIN', 'margen_pct' => null]);
        $this->assertSame(0.10, $sinMargenPropio->margenEfectivo());

        $conMargenPropio = $this->material(['codigo' => 'MAT-CON', 'margen_pct' => 0.25]);
        $this->assertSame(0.25, $conMargenPropio->margenEfectivo());
    }

    public function test_precio_venta_aplica_margen_y_luego_igv_por_separado(): void
    {
        ParametroControlMaterial::actual(); // igv = 0.18

        $material = $this->material(['precio_base' => 100.0, 'margen_pct' => 0.10]);

        $this->assertSame(110.0, $material->precioVentaSinIgv());
        $this->assertSame(129.8, $material->precioVentaConIgv());
    }
}
