<?php

namespace Tests\Feature\ControlGeneral;

use App\Models\ControlGeneral;
use App\Models\Solicitud;
use App\Services\ControlGeneralImportador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Importación del trabajo manual del Excel "Control General CLB - C&C".
 * El libro de prueba replica su estructura: claves en la fila 3, títulos en
 * la 4, datos desde la 5, filas "vivas" con fórmula y la hoja _base.
 */
class ControlGeneralImportadorTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['100', '200', '300', '400', '500', '600'] as $sol) {
            Solicitud::create(['numero_solicitud' => $sol, 'numero_suministro' => 'S' . $sol]);
        }

        $libro = new Spreadsheet();
        $libro->removeSheetByIndex(0);

        $this->hoja($libro, '1-Aprobadas',
            ['n', 'emp', 'sum', 'sol', 'fapr', 'constr', 'asesor', 'cuadrilla', 'reing', 'obs'],
            [
                [1, 'CLB', 'S100', '100', '2026-10-01', 'No', 'ANA', 'JUAN', null, 'llamar lunes'],
                [2, 'CLB', 'S500', '500', '2026-10-02', 'No', 'ANA', null, 'Anular', 'cliente desiste'],
                [3, 'CLB', 'S999', '999', '2026-10-02', 'No', null, null, null, null],
            ]);
        $hoja2 = $this->hoja($libro, '2-TC Construidas',
            ['n', 'emp', 'sum', 'sol', 'fapr', 'tc', 'asesor', 'cuadrilla', 'obs', '', '', 'hab'],
            [[1, 'C&C', 'S200', '200', '2026-09-01', 'Rechazada', 'LUIS', 'PEDRO', 'falta llave', '2026-10-20', 'CONTRATISTA X', null]],
            ['', '', '', '', '', '', '', '', '', 'FECHA DE OBS.', 'CONTRATISTA', '']);
        $this->hoja($libro, '3-Habilitación',
            ['n', 'emp', 'sum', 'sol', 'fprog', 'tecnico', 'obsv', 'obs', 'habil'],
            [
                [1, 'C&C', 'S300', '300', '2026-11-10', 'JIAN CASTRO', 'No', 'urgente', 'No'],
                [2, 'C&C', 'S600', '600', null, 'JIAN CASTRO', 'No', null, 'Sí'],
            ]);
        $this->hoja($libro, '4-Anulaciones-Reingresos',
            ['n', 'emp', 'sum', 'sol', 'reing', 'fmarca', 'tramite', 'obs'],
            [[1, 'CLB', 'S400', '400', 'Reingresar', '2026-10-16', 'En trámite', 'DESFACE']]);
        $this->hoja($libro, '5-Habilitadas', ['n', 'emp', 'sum', 'sol', 'fprog', 'tecnico', 'fhab', 'obs'], []);

        // Fila "viva" en la hoja 2 (fórmula): no es una fila propia.
        $hoja2->setCellValue('C6', '=C5');
        $hoja2->setCellValue('D6', '200');

        $base = $libro->createSheet()->setTitle('_base');
        $base->fromArray([['sol', 'fapr', 'tipo', 'tc', 'ftcp', 'ftcr', 'fprog', 'obsv'], ['200', null, null, 'Pendiente', null, null, null, 'No']]);

        $this->archivo = tempnam(sys_get_temp_dir(), 'cg') . '.xlsx';
        (new Xlsx($libro))->save($this->archivo);
    }

    protected function tearDown(): void
    {
        @unlink($this->archivo);
        parent::tearDown();
    }

    private function hoja(Spreadsheet $libro, string $nombre, array $claves, array $filas, ?array $titulos = null)
    {
        $hoja = $libro->createSheet()->setTitle($nombre);
        $hoja->fromArray($claves, null, 'A3');
        $hoja->fromArray($titulos ?? array_map('strtoupper', $claves), null, 'A4');
        if ($filas) {
            $hoja->fromArray($filas, null, 'A5');
        }

        return $hoja;
    }

    private function cg(string $sol): ControlGeneral
    {
        return ControlGeneral::whereHas('solicitud', fn ($q) => $q->where('numero_solicitud', $sol))->firstOrFail();
    }

    public function test_simulacion_no_guarda_nada(): void
    {
        $r = (new ControlGeneralImportador())->importar($this->archivo, guardar: false);

        $this->assertSame(0, ControlGeneral::count());
        $this->assertSame(6, $r['importadas']);
        $this->assertSame(['999'], $r['no_encontradas']);
    }

    public function test_importa_cada_hoja_con_lo_escrito_a_mano(): void
    {
        $r = (new ControlGeneralImportador())->importar($this->archivo);

        $this->assertSame(['999'], $r['no_encontradas']);

        $a = $this->cg('100');
        $this->assertSame(ControlGeneral::APROBADA, $a->etapa);
        $this->assertSame(['ANA', 'JUAN', 'llamar lunes', '2026-10-01'], [$a->asesor, $a->cuadrilla, $a->observacion, $a->fecha_aprobacion->toDateString()]);

        $tc = $this->cg('200');
        $this->assertSame(ControlGeneral::TC_CONSTRUIDA, $tc->etapa);
        $this->assertSame('Rechazada', $tc->resultado_tc);
        $this->assertSame('Pendiente', $tc->resultado_tc_portal);
        $this->assertSame('CONTRATISTA X', $tc->contratista);
        $this->assertSame('2026-10-20', $tc->fecha_observacion->toDateString());

        $h = $this->cg('300');
        $this->assertSame(ControlGeneral::HABILITACION, $h->etapa);
        $this->assertSame(['JIAN CASTRO', '2026-11-10', 'Programado'], [$h->tecnico, $h->fecha_programacion->toDateString(), $h->situacion()]);

        $an = $this->cg('400');
        $this->assertSame(ControlGeneral::ANULACION, $an->etapa);
        $this->assertSame(['Reingresar', 'En trámite', '2026-10-16'], [$an->accion, $an->estado_tramite, $an->fecha_marca->toDateString()]);
    }

    public function test_aplica_las_marcas_pendientes_como_la_macro(): void
    {
        $r = (new ControlGeneralImportador())->importar($this->archivo);

        // Marcada Anular en 1-Aprobadas -> 4; Habilitado = Sí en 3 -> 5.
        $this->assertSame(ControlGeneral::ANULACION, $this->cg('500')->etapa);
        $this->assertSame('Pendiente', $this->cg('500')->estado_tramite);
        $this->assertSame(ControlGeneral::HABILITADA, $this->cg('600')->etapa);
        $this->assertSame(2, $r['movidas_por_marca']);
    }

    public function test_se_puede_correr_dos_veces_sin_duplicar(): void
    {
        (new ControlGeneralImportador())->importar($this->archivo);
        (new ControlGeneralImportador())->importar($this->archivo);

        $this->assertSame(6, ControlGeneral::count());
    }
}
