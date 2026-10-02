<?php

namespace Tests\Feature\ControlMateriales;

use App\Models\Cotizacion;
use App\Models\PersonaCampo;
use App\Models\Ejecutado;
use App\Models\Ingreso;
use App\Models\Material;
use App\Models\ParametroControlMaterial;
use App\Services\ControlMaterialesCierre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * CM-11: ControlMaterialesCierre::cerrar() — código NUEVO de esta sesión
 * (22/09/2026), sin uso real todavía, con consecuencias directas sobre
 * stock y montos: la razón principal por la que se pidió escribir pruebas
 * automatizadas en vez de solo revisar el código a ojo.
 */
class ControlMaterialesCierreTest extends TestCase
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
            'stock_inicial' => 100.0,
            'stock_minimo' => 10.0,
            'factor_metros_por_unidad' => 1.0,
        ], $overrides));
    }

    public function test_archiva_ingresos_y_ejecutados_con_fecha_hasta_el_cierre(): void
    {
        $material = $this->material();
        $cuadrilla = PersonaCampo::create(['nombre' => 'Cuadrilla 1', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO]);

        $ingreso = Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-10', 'cantidad' => 20.0]);
        $ejecutado = Ejecutado::create([
            'fecha' => '2026-09-12', 'cuadrilla_id' => $cuadrilla->id, 'material_id' => $material->id,
            'cantidad' => 5.0, 'movimiento' => Ejecutado::MOVIMIENTO_SALIDA,
        ]);

        $cierre = ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);

        $ingreso->refresh();
        $ejecutado->refresh();
        $this->assertSame($cierre->id, $ingreso->cierre_materiales_id);
        $this->assertSame($cierre->id, $ejecutado->cierre_materiales_id);
        $this->assertSoftDeleted('ingresos', ['id' => $ingreso->id]);
        $this->assertSoftDeleted('ejecutados', ['id' => $ejecutado->id]);
    }

    public function test_no_archiva_cotizaciones_pendientes_pero_si_las_ya_descontadas_o_vales(): void
    {
        $material = $this->material();
        $contratista = PersonaCampo::create(['nombre' => 'Contratista X', 'tipo' => PersonaCampo::TIPO_CONTRATISTA]);
        $personalDirecto = PersonaCampo::create(['nombre' => 'Cuadrilla 1', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO]);

        $pendiente = Cotizacion::emitir($contratista, [['material_id' => $material->id, 'cantidad' => 1.0]], '2026-09-05');
        $descontada = Cotizacion::emitir($contratista, [['material_id' => $material->id, 'cantidad' => 1.0]], '2026-09-06');
        $descontada->update(['estado' => Cotizacion::ESTADO_DESCONTADO]);
        $vale = Cotizacion::emitir($personalDirecto, [['material_id' => $material->id, 'cantidad' => 1.0]], '2026-09-07');

        ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);

        $this->assertNull($pendiente->fresh()->cierre_materiales_id, 'una cotización PENDIENTE no se archiva en el cierre');
        $this->assertNotSoftDeleted('cotizaciones', ['id' => $pendiente->id]);
        $this->assertSoftDeleted('cotizaciones', ['id' => $descontada->id]);
        $this->assertSoftDeleted('cotizaciones', ['id' => $vale->id]);
    }

    public function test_no_archiva_movimientos_con_fecha_posterior_a_la_fecha_de_cierre(): void
    {
        $material = $this->material();
        $ingresoDentro = Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-10', 'cantidad' => 5.0]);
        $ingresoFuera = Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-20', 'cantidad' => 5.0]);

        ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);

        $this->assertSoftDeleted('ingresos', ['id' => $ingresoDentro->id]);
        $this->assertNotSoftDeleted('ingresos', ['id' => $ingresoFuera->id]);
    }

    public function test_stock_inicial_pasa_a_ser_el_stock_actual_y_precio_base_solo_sube(): void
    {
        $material = $this->material(['stock_inicial' => 100.0, 'precio_base' => 100.0]);
        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-10', 'cantidad' => 20.0, 'precio_compra' => 130.0]);

        // Antes del cierre: stock_actual = 120, precio_vigente = 130 (subió).
        $this->assertSame(120.0, $material->stockActual());
        $this->assertSame(130.0, $material->precioVigente());

        ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);
        $material->refresh();

        $this->assertSame(120.0, (float) $material->stock_inicial);
        $this->assertSame(130.0, (float) $material->precio_base);
    }

    public function test_precio_base_no_baja_aunque_el_vigente_este_por_debajo(): void
    {
        // Sin ningún ingreso, precioVigente() == precio_base, así que el
        // cierre no debería nunca poder BAJAR precio_base — max(base, vigente).
        $material = $this->material(['precio_base' => 150.0]);

        ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);
        $material->refresh();

        $this->assertSame(150.0, (float) $material->precio_base);
    }

    public function test_no_se_puede_cerrar_el_mismo_mes_dos_veces(): void
    {
        ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);

        $this->expectException(\Illuminate\Database\QueryException::class);
        ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-30'), null);
    }

    public function test_el_snapshot_guarda_los_indicadores_de_antes_del_cierre(): void
    {
        $material = $this->material(['stock_inicial' => 100.0, 'precio_base' => 100.0, 'codigo' => 'MAT-SNAP']);
        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-10', 'cantidad' => 20.0]);

        $cierre = ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);

        $filaMaterial = collect($cierre->resumen['materiales'])->firstWhere('codigo', 'MAT-SNAP');
        $this->assertNotNull($filaMaterial);
        // El snapshot pasa por json_encode/decode (columna `resumen`), así
        // que un float sin decimales (100.0) vuelve como int (100) — se
        // compara como número, no por tipo exacto.
        $this->assertEquals(100.0, $filaMaterial['stock_inicial_anterior']);
        $this->assertEquals(20.0, $filaMaterial['ingresos_periodo']);
        $this->assertEquals(120.0, $filaMaterial['stock_actual_final']);
    }

    /**
     * HALLAZGO (ver reporte): un movimiento con fecha POSTERIOR a la fecha
     * de cierre queda correctamente SIN archivar (ver test de arriba), pero
     * su cantidad ya fue absorbida en el nuevo stock_inicial de todos
     * modos, porque `cerrar()` calcula stock_inicial = stockActual() ANTES
     * de archivar, y stockActual() suma TODOS los ingresos/salidas activos
     * sin filtrar por fecha (Material::ingresos()/salidas() no conocen la
     * fecha de cierre). Como esa fila sigue activa después del cierre,
     * vuelve a contar en el kardex del mes nuevo: el mismo movimiento se
     * cuenta DOS VECES.
     *
     * Esto solo ocurre si ya existen movimientos con fecha posterior a la
     * fecha de cierre elegida ANTES de ejecutar el cierre (más probable si
     * se cierra "con unos días de atraso", como el propio código de la
     * migración anticipa que puede pasar) — no en el caso normal de cerrar
     * con fecha_cierre = hoy y sin movimientos futuros todavía registrados.
     *
     * Este test documenta el comportamiento CORRECTO esperado (el ingreso
     * futuro no debería duplicarse) y por eso FALLA contra el código
     * actual: es intencional, para que quede registrado como regresión
     * hasta que se decida si corregirlo.
     */
    public function test_bug_un_movimiento_con_fecha_posterior_al_cierre_se_duplica_en_el_stock_nuevo(): void
    {
        $material = $this->material(['stock_inicial' => 100.0]);
        Ingreso::create(['material_id' => $material->id, 'fecha' => '2026-09-20', 'cantidad' => 30.0]);

        // Antes del cierre: 100 + 30 = 130.
        $this->assertSame(130.0, $material->stockActual());

        ControlMaterialesCierre::cerrar('2026-09', Carbon::parse('2026-09-15'), null);
        $material->refresh();

        // Esperado: como el ingreso de 30 queda con fecha posterior al
        // cierre y sigue "vivo" (no archivado), el nuevo stock_inicial NO
        // debería incluirlo todavía — debería seguir siendo 100, y
        // stockActual() debería seguir dando 130 (100 + 30, sin duplicar).
        $this->assertSame(
            130.0,
            $material->stockActual(),
            'el ingreso con fecha futura a la del cierre se está contando dos veces (bug real, ver docblock del test)'
        );
    }
}
