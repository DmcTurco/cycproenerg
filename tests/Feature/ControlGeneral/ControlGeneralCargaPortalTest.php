<?php

namespace Tests\Feature\ControlGeneral;

use App\Jobs\ProcessExcelJob;
use App\Models\ControlGeneral;
use App\Models\Solicitud;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * La carga del Excel del portal (ProcessExcelJob) ubica cada solicitud en su
 * etapa de Control General.
 */
class ControlGeneralCargaPortalTest extends TestCase
{
    use RefreshDatabase;

    private const ENCABEZADOS = [
        'Número de Solicitud', 'Número de Suministro', 'Número de Contrato de Suministro', 'Estado de Solicitud',
        'Tipo de documento de identificación del solicitante', 'Número de documento de identificación del solicitante', 'Nombre del solicitante',
        'Tipo de documento de identificación de la Empresa Instaladora Ejecutora', 'Número de documento de identificación de la Empresa Instaladora Ejecutora',
        'Nombre de la Empresa Instaladora Ejecutora', 'Provincia', 'Distrito', 'Resultado de la Instalación de TC',
        'Fecha de suscripción de contrato', 'Fecha de entrega de documentos al Concesionario', 'Rechazada', 'Anulada',
    ];

    private function cargar(array $filas): void
    {
        Storage::fake('local');
        Event::fake();

        $libro = new Spreadsheet();
        $libro->getActiveSheet()->fromArray(array_merge([self::ENCABEZADOS], $filas));
        $ruta = 'portal.xlsx';
        (new Xlsx($libro))->save(Storage::disk('local')->path($ruta));

        (new ProcessExcelJob($ruta, 'test'))->handle();
    }

    private function fila(string $sol, string $estado, string $provincia, string $tc, ?string $suscripcion = '01/10/2026', ?string $entrega = null, string $anulada = 'No'): array
    {
        return [$sol, 'S' . $sol, 'C' . $sol, $estado, 'DNI', (string) (40000000 + (int) $sol), "CLIENTE {$sol}",
            'RUC', '20604329397', 'C & C PROYECTOS INTEGRALES EN ENERGIA SAC', $provincia, 'ATE', $tc, $suscripcion, $entrega, 'No', $anulada];
    }

    private function etapa(string $sol): ?string
    {
        return Solicitud::where('numero_solicitud', $sol)->first()?->controlGeneral?->etapa;
    }

    public function test_la_carga_ubica_cada_solicitud_en_su_etapa(): void
    {
        $this->cargar([
            $this->fila('1', '02-Solicitud de Suministro aprobada', 'LIMA', 'Sin registro'),
            $this->fila('2', '08-Expediente de Habilitación registrado', 'LIMA', 'Concluida'),
            $this->fila('3', '06-Instalación interna finalizada', 'CALLAO', 'Pendiente'),
            $this->fila('4', '02-Solicitud de Suministro aprobada', 'CAÑETE', 'Sin registro'),
            $this->fila('5', '02-Solicitud de Suministro aprobada', 'LIMA', 'Sin registro', null, '15/09/2026'),
            $this->fila('6', '02-Solicitud de Suministro aprobada', 'LIMA', 'Sin registro', '01/10/2026', null, 'Sí'),
        ]);

        $this->assertSame(ControlGeneral::APROBADA, $this->etapa('1'));
        $this->assertSame(ControlGeneral::HABILITACION, $this->etapa('2'));
        $this->assertSame(ControlGeneral::TC_CONSTRUIDA, $this->etapa('3'));
        $this->assertNull($this->etapa('4'));
        $this->assertNull($this->etapa('6'), 'Anulada en el portal: no entra');
        $this->assertSame('2026-10-01', Solicitud::where('numero_solicitud', '1')->first()->controlGeneral->fecha_aprobacion->toDateString());
        $this->assertSame('2026-09-15', Solicitud::where('numero_solicitud', '5')->first()->controlGeneral->fecha_aprobacion->toDateString());
    }

    public function test_una_segunda_carga_conserva_lo_manual_y_mueve_lo_que_avanzo(): void
    {
        $this->cargar([$this->fila('1', '02-Solicitud de Suministro aprobada', 'LIMA', 'Sin registro')]);
        Solicitud::where('numero_solicitud', '1')->first()->controlGeneral->update(['asesor' => 'ANA', 'observacion' => 'ok']);

        $this->cargar([$this->fila('1', '06-Instalación interna finalizada', 'LIMA', 'Pendiente')]);

        $cg = Solicitud::where('numero_solicitud', '1')->first()->controlGeneral;
        $this->assertSame(ControlGeneral::TC_CONSTRUIDA, $cg->etapa);
        $this->assertSame(['ANA', 'ok'], [$cg->asesor, $cg->observacion]);
    }
}
