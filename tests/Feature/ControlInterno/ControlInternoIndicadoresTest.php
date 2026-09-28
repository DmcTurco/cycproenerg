<?php

namespace Tests\Feature\ControlInterno;

use App\Models\FaseControlInterno;
use App\Models\Instalacion;
use App\Models\ParametroControlInterno;
use App\Models\Solicitud;
use App\Services\ControlInternoIndicadores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * CI-5: ControlInternoIndicadores — DESFACE/SEMÁFORO/DÍAS HÁBILES/FUERA DE
 * PLAZO, reversa-ingenierizados de las fórmulas de PARAM/GENERAL/CONSTRUIDO/
 * TC del Excel original (ver docblock de la clase). "Hoy" se congela en
 * 2026-09-22 (martes) para que los umbrales de días hábiles sean
 * deterministas sin depender de la fecha real de ejecución.
 *
 * Los conteos de días hábiles usados abajo están verificados a mano contra
 * el calendario real (sin feriados registrados en la tabla `feriados`,
 * que queda vacía por RefreshDatabase):
 *   2026-09-08 (mar) -> hoy: 11 días hábiles inclusive (networkDays) -> 10
 *   2026-09-07 (lun) -> hoy: 12 días hábiles inclusive -> 11
 *   2026-08-24 (lun) -> hoy: 22 días hábiles inclusive -> 21
 */
class ControlInternoIndicadoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-22')); // martes
        ParametroControlInterno::actual(); // verde<=10, ambar<=20, tc_verde<=15, tc_ambar<=30, plazo=20
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function solicitudEn(string $fase, array $faseAttrs = [], ?string $suscripcion = null): Solicitud
    {
        $solicitud = Solicitud::create(['numero_solicitud' => 'SOL-001', 'fecha_aprobacion_contrato' => $suscripcion]);
        FaseControlInterno::create(array_merge(['solicitud_id' => $solicitud->id, 'fase' => $fase], $faseAttrs));

        return $solicitud;
    }

    public function test_general_sin_suscripcion_es_sin_red(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::GENERAL, [], null);

        $this->assertSame('SIN RED', ControlInternoIndicadores::para($solicitud)['semaforo']);
    }

    public function test_general_verde_hasta_10_dias_habiles(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::GENERAL, [], '2026-09-08');

        // Nota: dias_habiles (fuera_de_plazo) solo se calcula cuando hay
        // fecha de fin de instalación interna, algo que en GENERAL todavía
        // no existe — acá solo se prueba el semáforo (que sí tiene su
        // propio cálculo de días hábiles, independiente del campo
        // dias_habiles). El caso con ambas fechas se prueba aparte en
        // test_fuera_de_plazo_...().
        $resultado = ControlInternoIndicadores::para($solicitud);
        $this->assertSame('VERDE', $resultado['semaforo']);
    }

    public function test_general_ambar_desde_11_dias_habiles(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::GENERAL, [], '2026-09-07');

        $resultado = ControlInternoIndicadores::para($solicitud);
        $this->assertSame('AMBAR', $resultado['semaforo']);
    }

    public function test_general_rojo_pasado_20_dias_habiles(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::GENERAL, [], '2026-08-24');

        $resultado = ControlInternoIndicadores::para($solicitud);
        $this->assertSame('ROJO', $resultado['semaforo']);
    }

    public function test_construido_desface_en_dias_calendario_desde_fin_de_instalacion_interna(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::CONSTRUIDO, [
            'fecha_construccion_control' => '2026-08-01',
        ], '2026-07-01');
        Instalacion::create(['solicitud_id' => $solicitud->id, 'fecha_finalizacion_instalacion_interna' => '2026-09-07']);

        $resultado = ControlInternoIndicadores::para($solicitud->fresh());

        $this->assertSame(15, $resultado['desface_dias']); // 07-sep -> 22-sep = 15 días calendario
        $this->assertSame('VERDE', $resultado['semaforo']); // <= espera_tc_verde_dias (15)
    }

    public function test_construido_ambar_y_rojo_segun_espera_de_tc(): void
    {
        $ambar = $this->solicitudEn(FaseControlInterno::CONSTRUIDO, ['fecha_construccion_control' => '2026-08-01'], '2026-07-01');
        Instalacion::create(['solicitud_id' => $ambar->id, 'fecha_finalizacion_instalacion_interna' => '2026-09-06']); // 16 días

        $rojo = $this->solicitudEn(FaseControlInterno::CONSTRUIDO, ['fecha_construccion_control' => '2026-08-01'], '2026-07-01');
        Instalacion::create(['solicitud_id' => $rojo->id, 'fecha_finalizacion_instalacion_interna' => '2026-08-22']); // 31 días

        $this->assertSame('AMBAR', ControlInternoIndicadores::para($ambar->fresh())['semaforo']);
        $this->assertSame('ROJO', ControlInternoIndicadores::para($rojo->fresh())['semaforo']);
    }

    public function test_tc_tiene_semaforo_literal_y_desface_es_el_ciclo_completo(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::TC, [
            'fecha_construccion_control' => '2026-05-01',
            'fecha_tc' => '2026-06-20',
        ], '2026-06-01');

        $resultado = ControlInternoIndicadores::para($solicitud);

        $this->assertSame('TC', $resultado['semaforo']);
        $this->assertSame(19, $resultado['desface_dias']); // suscripción -> fecha_tc, calendario
    }

    public function test_pend_anulacion_tiene_semaforo_literal_anular(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::PEND_ANULACION, [], '2026-06-01');

        $this->assertSame('ANULAR', ControlInternoIndicadores::para($solicitud)['semaforo']);
    }

    public function test_fuera_de_plazo_compara_dias_habiles_de_suscripcion_a_fin_de_instalacion_contra_el_parametro(): void
    {
        $dentroDePlazo = $this->solicitudEn(FaseControlInterno::CONSTRUIDO, [], '2026-09-08');
        Instalacion::create(['solicitud_id' => $dentroDePlazo->id, 'fecha_finalizacion_instalacion_interna' => '2026-09-22']); // 10 días hábiles

        $fueraDePlazo = $this->solicitudEn(FaseControlInterno::CONSTRUIDO, [], '2026-08-24');
        Instalacion::create(['solicitud_id' => $fueraDePlazo->id, 'fecha_finalizacion_instalacion_interna' => '2026-09-22']); // 21 días hábiles

        $this->assertFalse(ControlInternoIndicadores::para($dentroDePlazo->fresh())['fuera_de_plazo']);
        $this->assertTrue(ControlInternoIndicadores::para($fueraDePlazo->fresh())['fuera_de_plazo']);
    }

    public function test_trimestre_y_semana_se_calculan_desde_fin_de_instalacion_interna(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::CONSTRUIDO, [], '2026-09-08');
        Instalacion::create(['solicitud_id' => $solicitud->id, 'fecha_finalizacion_instalacion_interna' => '2026-09-22']);

        $resultado = ControlInternoIndicadores::para($solicitud->fresh());

        $this->assertSame('T3-2026', $resultado['trimestre']);
        $this->assertSame('2026-09-21', $resultado['semana_inicio']); // lunes de esa semana
    }

    public function test_calcular_y_guardar_persiste_el_cache_y_paraAlmacenado_lo_lee_sin_recalcular(): void
    {
        $solicitud = $this->solicitudEn(FaseControlInterno::GENERAL, [], '2026-09-08');

        $resultado = ControlInternoIndicadores::calcularYGuardar($solicitud);
        $this->assertSame('VERDE', $resultado['semaforo']);

        $fase = $solicitud->faseControlInterno()->first();
        $this->assertSame('VERDE', $fase->ind_semaforo);
        $this->assertNotNull($fase->ind_actualizado_en);

        // Se cambia la suscripción para que, si paraAlmacenado() recalculara,
        // el semáforo cambiaría a ROJO — pero como solo debe LEER el caché
        // ya guardado, tiene que seguir devolviendo VERDE.
        $solicitud->update(['fecha_aprobacion_contrato' => '2026-08-24']);
        $solicitud->load('faseControlInterno', 'instalacion');

        $this->assertSame('VERDE', ControlInternoIndicadores::paraAlmacenado($solicitud)['semaforo']);
    }
}
