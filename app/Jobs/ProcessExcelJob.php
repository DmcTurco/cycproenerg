<?php

namespace App\Jobs;

use App\Events\ExcelProcessed;
use App\Events\ExcelProcessingFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Solicitante;
use App\Models\Empresa;
use App\Models\Concesionaria;
use App\Models\Solicitud;
use App\Models\Ubicacion;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Log;
use App\Events\RowProcessed;
use App\Helpers\TipoDocumentoHelper;
use App\Models\EstadoInterno;
use App\Models\EstadoPortal;
use App\Models\Instalacion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    public $processId;

    /**
     * Create a new job instance.
     */

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->processId = uniqid('excel_');
    }


    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $spreadsheet = IOFactory::load(Storage::path($this->filePath));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) <= 1) {
                throw new \Exception("El archivo Excel está vacío o solo contiene encabezados.");
            }

            // Inicializar el progreso
            Cache::put("excel_progress_{$this->processId}", [
                'progress' => 0,
                'processed' => 0,
                'total' => count($rows) - 1, // Excluir header
                'created' => 0,
                'updated' => 0
            ], now()->addHours(1));

            $result = $this->processRows($rows);

            // Marcar como completado
            Cache::put("excel_progress_{$this->processId}", [
                'progress' => 100,
                'processed' => $result['total'],
                'total' => $result['total'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'completed' => true
            ], now()->addHours(1));

            // Aquí podrías emitir un evento para notificar que el proceso terminó
            event(new ExcelProcessed($result));

            // Limpieza: eliminar el archivo temporal
            Storage::delete($this->filePath);
        } catch (\Exception $e) {
            Log::error('Error procesando el archivo Excel: ' . $e->getMessage());

            Cache::put("excel_progress_{$this->processId}", [
                'error' => $e->getMessage()
            ], now()->addHours(1));

            event(new ExcelProcessingFailed($e->getMessage()));
            Storage::delete($this->filePath);
            throw $e;
        }
    }
    private function validateRow($row)
    {
        return !empty($row['A']) && !empty($row['H']) && !empty($row['G']) && is_numeric($row['H']);
    }

    private function processRows($rows)
    {
        $created = 0;
        $updated = 0;
        $processed = 0;
        $totalRows = count($rows) - 1; // Excluir header

        foreach ($rows as $index => $row) {
            if ($index == 1) continue; // Ignorar el header

            if (!$this->validateRow($row)) continue;

            $empresa = $this->processEmpresa($row);
            $concesionaria = $this->processConcesionaria($row);
            $solicitante = $this->processSolicitante($row);
            $estadoPortal = $this->processEstadoPortal($row);
            $solicitud = $this->processSolicitud($row, $solicitante, $empresa, $concesionaria, $estadoPortal);
            $this->processEstadoInterno($estadoPortal, $solicitud);
            $ubicacion = $this->processUbicacion($row, $solicitud);
            $proyecto = $this->processProyecto($row, $solicitud);
            $instalacion = $this->processInstalacion($row, $solicitud);

            $solicitud->wasRecentlyCreated ? $created++ : $updated++;
            $processed++;

            // Actualizar progreso
            $progress = ($processed / $totalRows) * 100;
            Cache::put("excel_progress_{$this->processId}", [
                'progress' => $progress,
                'processed' => $processed,
                'total' => $totalRows,
                'created' => $created,
                'updated' => $updated
            ], now()->addHours(1));
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated
        ];
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }
        return date('Y-m-d', strtotime($date));
    }

    private function processEmpresa($row)
    {
        $tipo_documento_id = TipoDocumentoHelper::getTypeDocument(trim($row['AK']));

        return Empresa::firstOrCreate(
            ['numero_documento' => trim($row['AL'])],
            [
                'tipo_documento' => $tipo_documento_id,
                'nombre' => trim($row['AM']),
                'registro_gas_natural' => trim($row['AN']),
            ]
        );
    }

    private function processConcesionaria($row)
    {
        $tipo_documento_id = TipoDocumentoHelper::getTypeDocument(trim($row['AO']));
        return Concesionaria::firstOrCreate(
            ['numero_documento' => trim($row['AP'])],
            [
                'tipo_documento' => $tipo_documento_id,
                'nombre' => trim($row['AQ']),
            ]
        );
    }

    private function processSolicitante($row)
    {
        $tipo_documento_id = TipoDocumentoHelper::getTypeDocument(trim($row['G']));
        return Solicitante::updateOrCreate(
            [
                'numero_documento' => trim($row['H']),
                'tipo_documento' => $tipo_documento_id,
            ],
            [
                'nombre' => trim($row['I']),
                'celular' => trim($row['K']),
                'correo_electronico' => trim($row['L']),
                'usuario_fise' => trim($row['U']),
            ]
        );
    }

    private function processEstadoPortal($row)
    {
        // Procesar el estado
        $estadoCompleto = trim($row['CO']);
        $partes = explode('-', $estadoCompleto, 2);
        $codigo = $partes[0];
        $nombre = $partes[1] ?? '';
        $abreviatura = $this->obtenerAbreviatura($nombre);

        // Crear el estado si no existe
        return EstadoPortal::firstOrCreate(
            ['codigo' => $codigo],
            [
                'nombre' => $nombre,
                'abreviatura' => $abreviatura
            ]
        );
    }

    private function obtenerAbreviatura($nombre)
    {
        $palabrasExcluidas = ['de', 'del', 'la', 'las', 'los', 'el', 'y', 'e', 'o', 'u'];
        $palabras = explode(' ', strtolower($nombre));
        $iniciales = '';

        foreach ($palabras as $palabra) {
            if (!in_array($palabra, $palabrasExcluidas)) {
                $iniciales .= strtoupper(substr($palabra, 0, 1));
            }
        }

        if (strlen($iniciales) < 2) {
            return implode(' ', array_slice($palabras, 0, 2));
        }

        return $iniciales;
    }

    private function processSolicitud($row, $solicitante, $empresa, $concesionaria, $estadoPortal)
    {
        return Solicitud::updateOrCreate(
            ['numero_solicitud' => trim($row['A'])],
            [
                'solicitante_id' => $solicitante->id,
                'empresa_id' => $empresa->id,
                'concesionaria_id' => $concesionaria->id,
                'numero_suministro' => trim($row['C']) ?: null,
                'numero_contrato_suministro' => trim($row['D']) ?: null,
                'fecha_aprobacion_contrato' => $this->parseDate(trim($row['F'])),
                'fecha_registro_portal' => $this->parseDate(trim($row['X'])),
                'estado_portal_id' => $estadoPortal->id,
            ]
        );
    }


    public function processEstadoInterno($estadoPortal, $solicitud)
    {
        if (in_array($estadoPortal->codigo, ["01", "01.1", "02"])) {
            $estadoPendiente = config('const.tipo_estado')[0]['id']; // Estado "pendiente"

            // Verificamos si ya existe un estado interno para esta solicitud
            $existeEstadoInterno = EstadoInterno::where('solicitud_id', $solicitud->id)->exists();

            // Solo creamos el estado interno si no existe ninguno
            if (!$existeEstadoInterno) {
                EstadoInterno::create([
                    'solicitud_id' => $solicitud->id,
                    'estado_const_id' => $estadoPendiente
                ]);
            }
            // Si ya existe un estado interno, no hacemos nada para mantener el estado actual
            // (especialmente importante si ya está asignado a un técnico)
        }
    }

    private function processUbicacion($row, $solicitud)
    {
        return Ubicacion::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'ubicacion' => trim($row['Q']) ?: null,
                'codigo_manzana' => trim($row['R']) ?: null,
                'codigo_identificacion_interna' => trim($row['B']) ?: null,
                'nombre_malla' => trim($row['S']) ?: null,
                'direccion' => trim($row['M']) ?: null,
                'departamento' => trim($row['N']),
                'provincia' => trim($row['O']),
                'distrito' => trim($row['P']),
                'venta_zona_no_gasificada' => trim($row['W']),

            ]
        );
    }

    private function processProyecto($row, $solicitud)
    {
        return Proyecto::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'tipo_proyecto' => trim($row['AE']) ?: null,
                'codigo_proyecto' => trim($row['AF']) ?: null,
                'categoria_proyecto' => trim($row['CL']) ?: null,
                'sub_categoria_proyecto' => trim($row['CM']) ?: null,
                'codigo_objeto_conexion' => trim($row['CN']) ?: null,

            ]
        );
    }

    private function processInstalacion($row, $solicitud)
    {
        return Instalacion::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'tipo_instalacion' => trim($row['AG']) ?: null,
                'tipo_acometida' => trim($row['AH']) ?: null,
                'numero_puntos_instalacion' => trim($row['AJ']) ?: null,
                'fecha_finalizacion_instalacion_interna' => $this->parseDate(trim($row['AA'])) ?: null,
                'fecha_finalizacion_instalacion_acometida' => $this->parseDate(trim($row['AB'])) ?: null,
                'resultado_instalacion_tc' => trim($row['CQ']) ?: null,
                'fecha_programacion_habilitacion' => $this->parseDate(trim($row['AC'])) ?: null,
            ]
        );
    }
}
