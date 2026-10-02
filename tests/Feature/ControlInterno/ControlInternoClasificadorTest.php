<?php

namespace Tests\Feature\ControlInterno;

use App\Models\FaseControlInterno;
use App\Models\Solicitud;
use App\Services\ControlInternoClasificador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CI-4: ControlInternoClasificador — motor de movimiento entre fases,
 * traducido de la macro `Procesar` original (Modulo_Internas.bas). Cubre
 * las 5 reglas documentadas en el docblock de la clase, en el mismo orden.
 */
class ControlInternoClasificadorTest extends TestCase
{
    use RefreshDatabase;

    private function solicitud(): Solicitud
    {
        return Solicitud::create(['numero_solicitud' => 'SOL-001']);
    }

    public function test_solicitud_nueva_entra_en_general(): void
    {
        $solicitud = $this->solicitud();

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: false);

        $this->assertSame(FaseControlInterno::GENERAL, $fase->fase);
        $this->assertNotNull($fase->fecha_ingreso_general);
    }

    public function test_general_pasa_a_construido_cuando_hay_fecha_de_construccion_y_tc_no_concluida(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::GENERAL,
            'fecha_construccion_control' => '2026-09-01',
        ]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: false);

        $this->assertSame(FaseControlInterno::CONSTRUIDO, $fase->fase);
    }

    public function test_fecha_fin_interna_del_portal_completa_f_construccion_y_mueve_a_construido(): void
    {
        $solicitud = $this->solicitud();

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: false, fechaFinInterna: '2026-08-31');

        $this->assertSame(FaseControlInterno::CONSTRUIDO, $fase->fase);
        $this->assertSame('2026-08-31', $fase->fecha_construccion_control->toDateString());
    }

    public function test_fecha_fin_interna_del_portal_no_pisa_f_construccion_manual(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::GENERAL,
            'fecha_construccion_control' => '2026-09-01',
        ]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: true, fechaTc: '2026-09-10', fechaFinInterna: '2026-08-31');

        $this->assertSame(FaseControlInterno::TC, $fase->fase);
        $this->assertSame('2026-09-01', $fase->fecha_construccion_control->toDateString());
    }

    public function test_general_pasa_directo_a_tc_si_el_portal_ya_reporta_tc_concluida(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::GENERAL,
            'fecha_construccion_control' => '2026-09-01',
        ]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: true, fechaTc: '2026-09-10');

        $this->assertSame(FaseControlInterno::TC, $fase->fase);
        $this->assertSame('2026-09-10', $fase->fecha_tc->toDateString());
    }

    public function test_general_sin_fecha_de_construccion_se_queda_en_general(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create(['solicitud_id' => $solicitud->id, 'fase' => FaseControlInterno::GENERAL]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: false);

        $this->assertSame(FaseControlInterno::GENERAL, $fase->fase);
    }

    public function test_construido_pasa_a_tc_cuando_el_portal_reporta_tc_concluida(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::CONSTRUIDO,
            'fecha_construccion_control' => '2026-08-01',
        ]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: true, fechaTc: '2026-09-15');

        $this->assertSame(FaseControlInterno::TC, $fase->fase);
        $this->assertSame('2026-09-15', $fase->fecha_tc->toDateString());
    }

    public function test_construido_sin_tc_concluida_se_queda_en_construido(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::CONSTRUIDO,
            'fecha_construccion_control' => '2026-08-01',
        ]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: false);

        $this->assertSame(FaseControlInterno::CONSTRUIDO, $fase->fase);
    }

    public function test_tc_es_historico_cerrado_y_no_se_reclasifica(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::TC,
            'fecha_tc' => '2026-06-01',
        ]);

        // Aunque llegue marcado_para_anular = true, TC ya es terminal en
        // este alcance (CI-6 decide cómo sale de ahí, no este clasificador).
        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: false);

        $this->assertSame(FaseControlInterno::TC, $fase->fase);
        $this->assertSame('2026-06-01', $fase->fecha_tc->toDateString());
    }

    public function test_marcado_para_anular_mueve_a_pend_anulacion_desde_cualquier_fase_abierta(): void
    {
        foreach ([FaseControlInterno::GENERAL, FaseControlInterno::CONSTRUIDO] as $faseInicial) {
            $solicitud = $this->solicitud();
            FaseControlInterno::create([
                'solicitud_id' => $solicitud->id,
                'fase' => $faseInicial,
                'marcado_para_anular' => true,
            ]);

            $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: false);

            $this->assertSame(FaseControlInterno::PEND_ANULACION, $fase->fase, "fase inicial: {$faseInicial}");
        }
    }

    public function test_pend_anulacion_es_terminal_en_este_alcance(): void
    {
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::PEND_ANULACION,
        ]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: true, fechaTc: '2026-09-10');

        $this->assertSame(FaseControlInterno::PEND_ANULACION, $fase->fase);
    }

    public function test_marcado_para_anular_gana_incluso_si_tambien_hay_tc_concluida(): void
    {
        // Regla 2 (marcado_para_anular) se evalúa ANTES que la regla 4
        // (CONSTRUIDO -> TC): si llegan las dos señales juntas, gana la
        // anulación.
        $solicitud = $this->solicitud();
        FaseControlInterno::create([
            'solicitud_id' => $solicitud->id,
            'fase' => FaseControlInterno::CONSTRUIDO,
            'marcado_para_anular' => true,
        ]);

        $fase = ControlInternoClasificador::clasificar($solicitud, tcConcluida: true, fechaTc: '2026-09-10');

        $this->assertSame(FaseControlInterno::PEND_ANULACION, $fase->fase);
    }
}
