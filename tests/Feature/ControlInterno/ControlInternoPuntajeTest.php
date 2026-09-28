<?php

namespace Tests\Feature\ControlInterno;

use App\Models\Empresa;
use App\Models\FaseControlInterno;
use App\Models\Instalacion;
use App\Models\ParametroControlInterno;
use App\Models\Proyecto;
use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Services\ControlInternoPuntaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CI-7: ControlInternoPuntaje::trimestre() — IND 2 / puntaje, leído
 * directamente de las fórmulas de la hoja PUNTAJE (100% fórmulas de hoja,
 * sin macro). Solo cuentan Usuario FISE = "Sí", fase CONSTRUIDO/TC,
 * categoría Residencial o Comercio (Multifamiliar queda fuera), con fecha
 * de fin de instalación interna dentro del mes.
 */
class ControlInternoPuntajeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ParametroControlInterno::actual();
    }

    private function solicitudFise(
        Empresa $empresa,
        string $categoria,
        string $fase,
        string $fechaFinInterna,
        bool $fueraDePlazo = false
    ): Solicitud {
        $solicitante = Solicitante::create(['nombre' => 'Cliente', 'usuario_fise' => 'Sí']);
        $solicitud = Solicitud::create(['numero_solicitud' => 'SOL-' . uniqid(), 'empresa_id' => $empresa->id, 'solicitante_id' => $solicitante->id]);
        Proyecto::create(['solicitud_id' => $solicitud->id, 'categoria_proyecto' => $categoria]);
        Instalacion::create(['solicitud_id' => $solicitud->id, 'fecha_finalizacion_instalacion_interna' => $fechaFinInterna]);
        // ind_fuera_de_plazo (caché de CI-5) NO está en $fillable a
        // propósito (ver docblock de FaseControlInterno) — solo lo escribe
        // ControlInternoIndicadores::calcularYGuardar() vía forceFill(),
        // así que en el test también hay que usar forceFill() para
        // simular ese caché ya calculado.
        $faseModelo = FaseControlInterno::create(['solicitud_id' => $solicitud->id, 'fase' => $fase]);
        $faseModelo->forceFill(['ind_fuera_de_plazo' => $fueraDePlazo])->save();

        return $solicitud;
    }

    public function test_cuenta_residencial_y_comercio_pero_excluye_multifamiliar(): void
    {
        $empresa = Empresa::create(['codigo' => 'CYC', 'nombre' => 'CYC']);

        $this->solicitudFise($empresa, 'Residencial', FaseControlInterno::CONSTRUIDO, '2026-08-10');
        $this->solicitudFise($empresa, 'Comercio', FaseControlInterno::TC, '2026-08-15');
        $this->solicitudFise($empresa, 'Multifamiliar', FaseControlInterno::CONSTRUIDO, '2026-08-20');

        $resultado = ControlInternoPuntaje::trimestre(2026, 3); // T3 = jul/ago/sep

        $this->assertSame(2, $resultado['empresas']['CYC']['total_fise']);
    }

    public function test_solo_cuenta_fase_construido_o_tc_no_general(): void
    {
        $empresa = Empresa::create(['codigo' => 'CYC', 'nombre' => 'CYC']);

        $this->solicitudFise($empresa, 'Residencial', FaseControlInterno::GENERAL, '2026-08-10');

        $resultado = ControlInternoPuntaje::trimestre(2026, 3);

        $this->assertSame(0, $resultado['empresas']['CYC']['total_fise']);
    }

    public function test_ind2_y_puntaje_segun_fuera_de_plazo_cacheado(): void
    {
        $empresa = Empresa::create(['codigo' => 'CYC', 'nombre' => 'CYC']);

        $this->solicitudFise($empresa, 'Residencial', FaseControlInterno::CONSTRUIDO, '2026-08-05', fueraDePlazo: false);
        $this->solicitudFise($empresa, 'Residencial', FaseControlInterno::CONSTRUIDO, '2026-08-10', fueraDePlazo: false);
        $this->solicitudFise($empresa, 'Comercio', FaseControlInterno::TC, '2026-08-15', fueraDePlazo: false);
        $this->solicitudFise($empresa, 'Residencial', FaseControlInterno::CONSTRUIDO, '2026-08-20', fueraDePlazo: true);

        $resultado = ControlInternoPuntaje::trimestre(2026, 3);
        $empresaCyc = $resultado['empresas']['CYC'];

        $this->assertSame(4, $empresaCyc['total_fise']);
        $this->assertSame(1, $empresaCyc['fuera_de_plazo']);
        $this->assertSame(3, $empresaCyc['en_plazo']);
        $this->assertSame(0.75, $empresaCyc['ind2']); // 3/4
        $this->assertSame(3.75, $empresaCyc['puntaje']); // ind2 x 5
    }

    public function test_fecha_fuera_del_mes_no_cuenta(): void
    {
        $empresa = Empresa::create(['codigo' => 'CYC', 'nombre' => 'CYC']);

        // T3 = jul/ago/sep 2026; esta instalación termina en octubre.
        $this->solicitudFise($empresa, 'Residencial', FaseControlInterno::CONSTRUIDO, '2026-10-05');

        $resultado = ControlInternoPuntaje::trimestre(2026, 3);

        $this->assertSame(0, $resultado['empresas']['CYC']['total_fise']);
    }
}
