<?php

namespace Tests\Feature\ControlGeneral;

use App\Models\ControlGeneral;
use App\Models\Solicitud;
use App\Services\ControlGeneralClasificador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Motor de etapas de Control General: traducción de las macros
 * ActualizarControl / MoverMarcadas del Excel "Control General CLB - C&C".
 */
class ControlGeneralClasificadorTest extends TestCase
{
    use RefreshDatabase;

    private int $n = 0;

    private function solicitud(): Solicitud
    {
        return Solicitud::create(['numero_solicitud' => 'SOL-' . (++$this->n)]);
    }

    private function portal(array $extra = []): array
    {
        return array_merge([
            'provincia' => 'LIMA',
            'estado' => '02',
            'resultado_tc' => 'Sin registro',
            'fecha_suscripcion' => '2026-10-01',
            'fecha_entrega_documentos' => null,
        ], $extra);
    }

    private function cg(Solicitud $s): ControlGeneral
    {
        return ControlGeneral::where('solicitud_id', $s->id)->firstOrFail();
    }

    // ---------- Entrada desde el portal ----------

    public function test_estado_02_entra_a_aprobadas_con_fecha_de_suscripcion(): void
    {
        $s = $this->solicitud();

        $r = ControlGeneralClasificador::aplicarPortal($s, $this->portal());

        $this->assertSame(['antes' => null, 'despues' => ControlGeneral::APROBADA], $r);
        $this->assertSame('2026-10-01', $this->cg($s)->fecha_aprobacion->toDateString());
    }

    public function test_sin_suscripcion_usa_fecha_de_entrega_de_documentos(): void
    {
        $s = $this->solicitud();

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['fecha_suscripcion' => null, 'fecha_entrega_documentos' => '2026-09-15']));

        $this->assertSame('2026-09-15', $this->cg($s)->fecha_aprobacion->toDateString());
    }

    public function test_fuera_de_lima_y_callao_no_entra(): void
    {
        $s = $this->solicitud();

        $this->assertNull(ControlGeneralClasificador::aplicarPortal($s, $this->portal(['provincia' => 'CAÑETE'])));
        $this->assertSame(0, ControlGeneral::count());
    }

    public function test_anuladas_o_rechazadas_en_el_portal_no_entran_ni_se_actualizan(): void
    {
        $s = $this->solicitud();
        $this->assertNull(ControlGeneralClasificador::aplicarPortal($s, $this->portal(['anulada' => true])));
        $this->assertNull(ControlGeneralClasificador::aplicarPortal($s, $this->portal(['rechazada' => true])));

        ControlGeneralClasificador::aplicarPortal($s, $this->portal());
        $this->assertNull(ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '06', 'anulada' => true])));
        $this->assertSame(ControlGeneral::APROBADA, $this->cg($s)->etapa);
    }

    public function test_callao_si_entra(): void
    {
        $s = $this->solicitud();

        $this->assertNotNull(ControlGeneralClasificador::aplicarPortal($s, $this->portal(['provincia' => 'Callao'])));
    }

    public function test_estados_06_y_08_entran_a_tc_construidas(): void
    {
        foreach (['06', '08', '08.1'] as $estado) {
            $s = $this->solicitud();
            ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => $estado, 'resultado_tc' => 'Pendiente']));
            $this->assertSame(ControlGeneral::TC_CONSTRUIDA, $this->cg($s)->etapa, "estado {$estado}");
            $this->assertSame('Pendiente', $this->cg($s)->resultado_tc);
        }
    }

    public function test_tc_concluida_entra_directo_a_habilitacion_y_trae_observado_del_portal(): void
    {
        $s = $this->solicitud();

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '08.1', 'resultado_tc' => 'Concluida']));

        $cg = $this->cg($s);
        $this->assertSame(ControlGeneral::HABILITACION, $cg->etapa);
        $this->assertTrue($cg->observado);
        $this->assertSame('Observado', $cg->situacion());
    }

    public function test_otros_estados_no_entran(): void
    {
        foreach (['01', '01.2', '03', '04', '12'] as $estado) {
            $s = $this->solicitud();
            $this->assertNull(ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => $estado])), "estado {$estado}");
        }
    }

    // ---------- Movimientos por lo que dice el portal ----------

    public function test_aprobada_pasa_a_tc_cuando_el_portal_reporta_06_y_conserva_lo_manual(): void
    {
        $s = $this->solicitud();
        ControlGeneralClasificador::aplicarPortal($s, $this->portal());
        $this->cg($s)->update(['asesor' => 'ANA', 'cuadrilla' => 'JUAN', 'observacion' => 'llamar']);

        $r = ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '06']));

        $cg = $this->cg($s);
        $this->assertSame(ControlGeneral::TC_CONSTRUIDA, $r['despues']);
        $this->assertSame(['ANA', 'JUAN', 'llamar'], [$cg->asesor, $cg->cuadrilla, $cg->observacion]);
    }

    public function test_aprobada_que_avanza_a_04_se_queda_en_aprobadas_como_avanzo_en_portal(): void
    {
        $s = $this->solicitud();
        ControlGeneralClasificador::aplicarPortal($s, $this->portal());

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '04']));

        $cg = $this->cg($s);
        $this->assertSame(ControlGeneral::APROBADA, $cg->etapa);
        $this->assertSame(ControlGeneral::PLAZO_AVANZO, $cg->controlPlazo(20, '04'));
    }

    public function test_tc_construida_pasa_a_habilitacion_cuando_el_portal_reporta_tc_concluida(): void
    {
        $s = $this->solicitud();
        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '06', 'resultado_tc' => 'Pendiente']));

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '08', 'resultado_tc' => 'Concluida']));

        $this->assertSame(ControlGeneral::HABILITACION, $this->cg($s)->etapa);
    }

    public function test_habilitacion_vuelve_a_aprobadas_si_el_portal_regresa_a_estado_02(): void
    {
        $s = $this->solicitud();
        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '08', 'resultado_tc' => 'Concluida']));

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '02', 'resultado_tc' => 'Concluida']));

        $this->assertSame(ControlGeneral::APROBADA, $this->cg($s)->etapa);
    }

    public function test_anuladas_y_habilitadas_no_cambian_de_etapa_con_el_portal(): void
    {
        $a = $this->solicitud();
        ControlGeneral::create(['solicitud_id' => $a->id, 'etapa' => ControlGeneral::ANULACION, 'accion' => 'Anular']);
        $h = $this->solicitud();
        ControlGeneral::create(['solicitud_id' => $h->id, 'etapa' => ControlGeneral::HABILITADA, 'habilitado' => true]);

        ControlGeneralClasificador::aplicarPortal($a, $this->portal(['estado' => '08', 'resultado_tc' => 'Concluida']));
        ControlGeneralClasificador::aplicarPortal($h, $this->portal(['estado' => '02']));

        $this->assertSame(ControlGeneral::ANULACION, $this->cg($a)->etapa);
        $this->assertSame(ControlGeneral::HABILITADA, $this->cg($h)->etapa);
    }

    // ---------- "Manda el portal" (MergeVal) ----------

    public function test_resultado_tc_manual_se_respeta_si_el_portal_repite_el_mismo_valor(): void
    {
        $s = $this->solicitud();
        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '06', 'resultado_tc' => 'Pendiente']));
        $this->cg($s)->update(['resultado_tc' => 'Rechazada']);

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '06', 'resultado_tc' => 'Pendiente']));

        $this->assertSame('Rechazada', $this->cg($s)->resultado_tc);
    }

    public function test_resultado_tc_del_portal_gana_cuando_cambia(): void
    {
        $s = $this->solicitud();
        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '06', 'resultado_tc' => 'Sin registro']));
        $this->cg($s)->update(['resultado_tc' => 'Pendiente']);

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '06', 'resultado_tc' => 'Rechazada']));

        $this->assertSame('Rechazada', $this->cg($s)->resultado_tc);
    }

    public function test_observado_manual_se_respeta_hasta_que_el_portal_cambia(): void
    {
        $s = $this->solicitud();
        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '08', 'resultado_tc' => 'Concluida']));
        $this->cg($s)->update(['observado' => true]);

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '08', 'resultado_tc' => 'Concluida']));
        $this->assertTrue($this->cg($s)->observado);

        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '08.1', 'resultado_tc' => 'Concluida']));
        ControlGeneralClasificador::aplicarPortal($s, $this->portal(['estado' => '09', 'resultado_tc' => 'Concluida']));
        $this->assertFalse($this->cg($s)->observado);
    }

    // ---------- Movimientos por marca manual ----------

    public function test_construida_si_pasa_a_tc_construidas(): void
    {
        $cg = ControlGeneral::create(['solicitud_id' => $this->solicitud()->id, 'etapa' => ControlGeneral::APROBADA, 'construida' => true]);

        $this->assertSame(ControlGeneral::TC_CONSTRUIDA, ControlGeneralClasificador::aplicarMovimientos($cg));
    }

    public function test_anular_pasa_a_anulaciones_con_fecha_y_tramite_pendiente(): void
    {
        $cg = ControlGeneral::create(['solicitud_id' => $this->solicitud()->id, 'etapa' => ControlGeneral::APROBADA, 'accion' => 'Anular']);

        ControlGeneralClasificador::aplicarMovimientos($cg);

        $cg->refresh();
        $this->assertSame(ControlGeneral::ANULACION, $cg->etapa);
        $this->assertSame('Pendiente', $cg->estado_tramite);
        $this->assertSame(now()->toDateString(), $cg->fecha_marca->toDateString());
    }

    public function test_construida_tiene_prioridad_sobre_anular(): void
    {
        $cg = ControlGeneral::create(['solicitud_id' => $this->solicitud()->id, 'etapa' => ControlGeneral::APROBADA, 'accion' => 'Anular', 'construida' => true]);

        $this->assertSame(ControlGeneral::TC_CONSTRUIDA, ControlGeneralClasificador::aplicarMovimientos($cg));
    }

    public function test_quitar_la_accion_devuelve_a_aprobadas(): void
    {
        $cg = ControlGeneral::create(['solicitud_id' => $this->solicitud()->id, 'etapa' => ControlGeneral::ANULACION, 'accion' => null, 'estado_tramite' => 'En trámite']);

        $this->assertSame(ControlGeneral::APROBADA, ControlGeneralClasificador::aplicarMovimientos($cg));
        $this->assertNull($cg->fresh()->estado_tramite);
    }

    public function test_tc_concluida_manual_pasa_a_habilitacion(): void
    {
        $cg = ControlGeneral::create(['solicitud_id' => $this->solicitud()->id, 'etapa' => ControlGeneral::TC_CONSTRUIDA, 'resultado_tc' => 'Concluida']);

        $this->assertSame(ControlGeneral::HABILITACION, ControlGeneralClasificador::aplicarMovimientos($cg));
    }

    public function test_habilitado_pasa_a_habilitadas_con_fecha_de_hoy_y_se_puede_deshacer(): void
    {
        $cg = ControlGeneral::create(['solicitud_id' => $this->solicitud()->id, 'etapa' => ControlGeneral::HABILITACION, 'habilitado' => true, 'tecnico' => 'JIAN']);

        $this->assertSame(ControlGeneral::HABILITADA, ControlGeneralClasificador::aplicarMovimientos($cg));
        $this->assertSame(now()->toDateString(), $cg->fresh()->fecha_habilitacion->toDateString());

        $cg->habilitado = false;
        $this->assertSame(ControlGeneral::HABILITACION, ControlGeneralClasificador::aplicarMovimientos($cg));
        $this->assertNull($cg->fresh()->fecha_habilitacion);
        $this->assertSame('JIAN', $cg->fresh()->tecnico);
    }

    // ---------- Indicadores ----------

    public function test_control_de_plazo(): void
    {
        $cg = new ControlGeneral(['fecha_aprobacion' => now()->subDays(21)->toDateString()]);
        $this->assertSame(ControlGeneral::PLAZO_DESFASE, $cg->controlPlazo(20, '02'));

        $cg->fecha_aprobacion = now()->subDays(20)->toDateString();
        $this->assertSame(ControlGeneral::PLAZO_EN_PLAZO, $cg->controlPlazo(20, '02'));

        $cg->fecha_aprobacion = null;
        $this->assertSame(ControlGeneral::PLAZO_SIN_FECHA, $cg->controlPlazo(20, '02'));
    }

    public function test_situacion_de_habilitacion(): void
    {
        $cg = new ControlGeneral();
        $this->assertSame('Por programar', $cg->situacion());
        $cg->fecha_programacion = '2026-11-10';
        $this->assertSame('Programado', $cg->situacion());
        $cg->observado = true;
        $this->assertSame('Observado', $cg->situacion());
    }
}
