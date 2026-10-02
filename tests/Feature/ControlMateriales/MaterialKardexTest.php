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
        $cuadrilla = \App\Models\PersonaCampo::create(['nombre' => 'Contratista X', 'tipo' => \App\Models\PersonaCampo::TIPO_CONTRATISTA]);

        \App\Models\Cotizacion::emitir($cuadrilla, [['material_id' => $material->id, 'cantidad' => 10.0]], '2026-09-10');

        $this->assertSame(40.0, $material->stockActual());
    }

    public function test_salidas_de_personal_directo_se_convierten_de_metros_a_rollos_con_el_factor(): void
    {
        // factor_metros_por_unidad = 100 (se entrega en rollos, se ejecuta en
        // metros): 250 metros ejecutados = 2.5 rollos de salida.
        $material = $this->material(['stock_inicial' => 50.0, 'factor_metros_por_unidad' => 100.0]);
        $cuadrilla = \App\Models\PersonaCampo::create(['nombre' => 'Cuadrilla 1', 'tipo' => \App\Models\PersonaCampo::TIPO_PERSONAL_DIRECTO]);

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
        $cuadrilla = \App\Models\PersonaCampo::create(['nombre' => 'Cuadrilla 1', 'tipo' => \App\Models\PersonaCampo::TIPO_PERSONAL_DIRECTO]);

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

    public function test_precio_venta_se_guarda_en_la_tabla_al_crear_y_editar(): void
    {
        ParametroControlMaterial::actual(); // igv = 0.18, margen_general = 0.10

        $material = $this->material(['precio_base' => 100.0, 'margen_pct' => null]);
        $this->assertDatabaseHas('materiales', ['id' => $material->id, 'precio_venta_sin_igv' => 110.00, 'precio_venta_con_igv' => 129.80]);

        $material->update(['margen_pct' => 0.20]);
        $this->assertDatabaseHas('materiales', ['id' => $material->id, 'precio_venta_sin_igv' => 120.00, 'precio_venta_con_igv' => 141.60]);
    }

    public function test_precio_venta_guardado_se_recalcula_al_registrar_modificar_y_eliminar_ingresos(): void
    {
        ParametroControlMaterial::actual();
        $material = $this->material(['precio_base' => 100.0, 'margen_pct' => 0.10]);

        $ingreso = Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-01', 'cantidad' => 1, 'precio_compra' => 200.0]);
        $this->assertSame(220.0, $material->fresh()->precio_venta_sin_igv);

        $ingreso->update(['precio_compra' => 150.0]);
        $this->assertSame(165.0, $material->fresh()->precio_venta_sin_igv);

        $ingreso->delete();
        $this->assertSame(110.0, $material->fresh()->precio_venta_sin_igv);
    }

    public function test_precio_venta_guardado_se_recalcula_en_ambos_materiales_si_el_ingreso_cambia_de_material(): void
    {
        ParametroControlMaterial::actual();
        $a = $this->material(['codigo' => 'MAT-A', 'precio_base' => 100.0, 'margen_pct' => 0.10]);
        $b = $this->material(['codigo' => 'MAT-B', 'precio_base' => 100.0, 'margen_pct' => 0.10]);

        $ingreso = Ingreso::create(['material_id' => $a->id, 'fecha' => '2026-09-01', 'cantidad' => 1, 'precio_compra' => 200.0]);
        $ingreso->update(['material_id' => $b->id]);

        $this->assertSame(110.0, $a->fresh()->precio_venta_sin_igv);
        $this->assertSame(220.0, $b->fresh()->precio_venta_sin_igv);
    }

    public function test_precio_venta_guardado_se_recalcula_al_cambiar_margen_general_o_igv(): void
    {
        $parametros = ParametroControlMaterial::actual();
        $general = $this->material(['codigo' => 'MAT-GEN', 'precio_base' => 100.0, 'margen_pct' => null]);
        $propio = $this->material(['codigo' => 'MAT-PRO', 'precio_base' => 100.0, 'margen_pct' => 0.25]);

        $parametros->update(['margen_general' => 0.30, 'igv' => 0.10]);

        $this->assertSame(130.0, $general->fresh()->precio_venta_sin_igv);
        $this->assertSame(143.0, $general->fresh()->precio_venta_con_igv);
        $this->assertSame(125.0, $propio->fresh()->precio_venta_sin_igv, 'el margen propio no cambia con el general');
        $this->assertSame(137.5, $propio->fresh()->precio_venta_con_igv, 'pero el IGV sí aplica a todos');
    }
}
