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
use App\Models\FaseControlInterno;
use App\Models\Instalacion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    public $processId;

    // Añadir estas propiedades:
    public $timeout = 1800;    // 30 minutos máximo para ejecutar el job
    public $tries = 3;         // Número de intentos si falla
    public $backoff = [60, 300, 600]; // Reintentar después de 1, 5 y 10 minutos

    /**
     * Mapa de "nombre de encabezado" => letra de columna, resuelto en tiempo de
     * ejecución a partir de la fila 1 del Excel. Leer por nombre (en vez de por
     * letra fija) hace que la carga sobreviva a que el portal de origen inserte,
     * borre o reordene columnas — solo se rompe si cambia el TEXTO del encabezado.
     */
    protected array $columnMap = [];

    /**
     * Create a new job instance.
     */

    public function __construct($filePath, $processId)
    {
        $this->filePath = $filePath;
        $this->processId = $processId;
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

            $this->buildColumnMap($rows[1]);

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
                'failed' => $result['failed'],
                'completed' => true
            ], now()->addHours(1));

            // Aquí podrías emitir un evento para notificar que el proceso terminó
            event(new ExcelProcessed($result));
            // broadcast(new ExcelProcessed($result));

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

    /**
     * Construye el mapa encabezado => columna a partir de la fila 1 del Excel.
     */
    private function buildColumnMap(array $headerRow): void
    {
        $this->columnMap = [];

        foreach ($headerRow as $letter => $header) {
            $header = trim((string) $header);
            // Si el encabezado está duplicado en el archivo (ej. "Tipo de
            // instalación" vuelve a aparecer en la sección de Habilitación),
            // nos quedamos con la primera aparición, no con la última.
            if ($header !== '' && !isset($this->columnMap[$header])) {
                $this->columnMap[$header] = $letter;
            }
        }
    }

    /**
     * Lee una celda de la fila por el nombre de su encabezado (no por letra fija).
     * Si el encabezado no existe en este archivo, devuelve null en vez de fallar.
     */
    private function col(array $row, string $headerName)
    {
        $letter = $this->columnMap[$headerName] ?? null;

        if ($letter === null) {
            Log::warning("Columna con encabezado \"{$headerName}\" no encontrada en el Excel.");
            return null;
        }

        return $row[$letter] ?? null;
    }

    private function validateRow($row)
    {
        $numeroSolicitud = $this->col($row, 'Número de Solicitud');
        $tipoDocumento = $this->col($row, 'Tipo de documento de identificación del solicitante');
        $numeroDocumento = $this->col($row, 'Número de documento de identificación del solicitante');

        return !empty($numeroSolicitud) && !empty($numeroDocumento) && !empty($tipoDocumento) && is_numeric($numeroDocumento);
    }

    private function processRows($rows)
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $processed = 0;
        $totalRows = count($rows) - 1; // Excluir header

        foreach ($rows as $index => $row) {
            if ($index == 1) continue; // Ignorar el header

            if (!$this->validateRow($row)) continue;

            try {
                $wasCreated = DB::transaction(function () use ($row) {
                    $empresa = $this->processEmpresa($row);
                    $concesionaria = $this->processConcesionaria($row);
                    $solicitante = $this->processSolicitante($row);
                    $estadoPortal = $this->processEstadoPortal($row);
                    $solicitud = $this->processSolicitud($row, $solicitante, $empresa, $concesionaria, $estadoPortal);
                    $this->processEstadoInterno($estadoPortal, $solicitud);
                    $this->processFaseControlInterno($solicitud);
                    $this->processUbicacion($row, $solicitud);
                    $this->processProyecto($row, $solicitud);
                    $this->processInstalacion($row, $solicitud);

                    return $solicitud->wasRecentlyCreated;
                });

                $wasCreated ? $created++ : $updated++;
            } catch (\Throwable $e) {
                // Una fila con datos inesperados no debe tirar abajo el resto del
                // archivo: se revierte solo esta fila (transacción por fila) y se
                // sigue con la siguiente.
                $failed++;
                Log::error("Fila {$index} del Excel omitida por error: " . $e->getMessage());
            }

            $processed++;

            // Actualizar progreso
            $progress = ($processed / $totalRows) * 100;
            Cache::put("excel_progress_{$this->processId}", [
                'progress' => $progress,
                'processed' => $processed,
                'total' => $totalRows,
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
            ], now()->addHours(1));
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total' => $created + $updated
        ];
    }

    /**
     * Igual que Model::firstOrCreate(), pero resistente a que dos cargas se
     * pisen: si otro proceso crea el mismo registro entre nuestro SELECT y
     * nuestro INSERT, en vez de fallar por la restricción de unicidad,
     * simplemente volvemos a buscarlo y devolvemos el que ya quedó guardado.
     */
    private function firstOrCreateSafe(string $modelClass, array $attributes, array $values = [])
    {
        try {
            return $modelClass::firstOrCreate($attributes, $values);
        } catch (\Illuminate\Database\QueryException $e) {
            // 23505 = unique_violation (Postgres), 1062 = duplicate entry (MySQL)
            if (!in_array($e->getCode(), ['23505', '1062'], true)) {
                throw $e;
            }

            return $modelClass::where($attributes)->firstOrFail();
        }
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // Limpiar la fecha de posibles espacios u otros caracteres
        $date = trim($date);

        // Verificar si la fecha está en formato dd/mm/yyyy o d/m/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            // Convertir al formato yyyy-mm-dd para MySQL
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        // Intentar convertir con DateTime para manejar otros formatos
        try {
            $dateTime = new \DateTime($date);
            return $dateTime->format('Y-m-d');
        } catch (\Exception $e) {
            // Si hay un error en la conversión, registrarlo y devolver null
            Log::warning("No se pudo convertir la fecha: {$date}. Error: " . $e->getMessage());
            return null;
        }
    }

    private function processEmpresa($row)
    {
        $tipo_documento_id = TipoDocumentoHelper::getTypeDocument(trim($this->col($row, 'Tipo de documento de identificación de la Empresa Instaladora Ejecutora')));

        return $this->firstOrCreateSafe(Empresa::class,
            ['numero_documento' => trim($this->col($row, 'Número de documento de identificación de la Empresa Instaladora Ejecutora'))],
            [
                'tipo_documento' => $tipo_documento_id,
                'nombre' => trim($this->col($row, 'Nombre de la Empresa Instaladora Ejecutora')),
                'registro_gas_natural' => trim($this->col($row, 'Registro de Gas Natural de la de Empresa Instaladora Ejecutora')),
            ]
        );
    }

    private function processConcesionaria($row)
    {
        $tipo_documento_id = TipoDocumentoHelper::getTypeDocument(trim($this->col($row, 'Tipo de documento de identificación de la Empresa Concesionaria')));
        return $this->firstOrCreateSafe(Concesionaria::class,
            ['numero_documento' => trim($this->col($row, 'Número de documento de identificación de la Empresa Concesionaria'))],
            [
                'tipo_documento' => $tipo_documento_id,
                'nombre' => trim($this->col($row, 'Nombre de la Empresa Concesionaria')),
            ]
        );
    }

    private function processSolicitante($row)
    {
        $tipo_documento_id = TipoDocumentoHelper::getTypeDocument(trim($this->col($row, 'Tipo de documento de identificación del solicitante')));
        return Solicitante::updateOrCreate(
            [
                'numero_documento' => trim($this->col($row, 'Número de documento de identificación del solicitante')),
                'tipo_documento' => $tipo_documento_id,
            ],
            [
                'nombre' => trim($this->col($row, 'Nombre del solicitante')),
                'celular' => trim($this->col($row, 'Celular')),
                'correo_electronico' => trim($this->col($row, 'Correo electrónico')),
                'usuario_fise' => trim($this->col($row, 'Usuario FISE')),
            ]
        );
    }

    private function processEstadoPortal($row)
    {
        // Procesar el estado
        $estadoCompleto = trim($this->col($row, 'Estado de Solicitud'));
        $partes = explode('-', $estadoCompleto, 2);
        $codigo = $partes[0];
        $nombre = $partes[1] ?? '';
        $abreviatura = $this->obtenerAbreviatura($nombre);

        // Crear el estado si no existe
        return $this->firstOrCreateSafe(EstadoPortal::class,
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
            ['numero_solicitud' => trim($this->col($row, 'Número de Solicitud'))],
            [
                'solicitante_id' => $solicitante->id,
                'empresa_id' => $empresa->id,
                'concesionaria_id' => $concesionaria->id,
                'numero_suministro' => trim($this->col($row, 'Número de Suministro')) ?: null,
                'numero_contrato_suministro' => trim($this->col($row, 'Número de Contrato de Suministro')) ?: null,
                'fecha_aprobacion_contrato' => $this->parseDate(trim($this->col($row, 'Fecha de aprobación del contrato'))),
                'fecha_registro_portal' => $this->parseDate(trim($this->col($row, 'Fecha de registro de la Solicitud en el Portal'))),
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

    /**
     * Enganche mínimo de Control Interno (CI-2): a toda solicitud que todavía
     * no tenga fase le asigna GENERAL con fecha de ingreso hoy — igual que el
     * Excel marca "NUEVO" con su F. INGRESO al entrar por primera vez.
     *
     * Ojo: esto NO es el motor de clasificación completo (mover a CONSTRUIDO
     * cuando hay F. CONSTRUCCIÓN, a TC cuando el portal dice "Concluida", a
     * PEND_ANULACION con la marca ANULAR o Rechazada/Anulada del portal).
     * Esa lógica es CI-4 y todavía no está implementada; una solicitud que ya
     * tiene fase asignada no se toca aquí.
     */
    private function processFaseControlInterno($solicitud)
    {
        $existeFase = FaseControlInterno::where('solicitud_id', $solicitud->id)->exists();

        if (!$existeFase) {
            FaseControlInterno::create([
                'solicitud_id' => $solicitud->id,
                'fase' => FaseControlInterno::GENERAL,
                'fecha_ingreso_general' => now()->toDateString(),
            ]);
        }
    }

    private function processUbicacion($row, $solicitud)
    {
        return Ubicacion::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'ubicacion' => trim($this->col($row, 'Ubicación')) ?: null,
                'codigo_manzana' => trim($this->col($row, 'Código de Manzana')) ?: null,
                'codigo_identificacion_interna' => trim($this->col($row, 'Código de identificación interna del predio')) ?: null,
                'nombre_malla' => trim($this->col($row, 'Nombre de Malla')) ?: null,
                'direccion' => trim($this->col($row, 'Dirección')) ?: null,
                'departamento' => trim($this->col($row, 'Departamento')),
                'provincia' => trim($this->col($row, 'Provincia')),
                'distrito' => trim($this->col($row, 'Distrito')),
                'venta_zona_no_gasificada' => trim($this->col($row, 'Venta en zona no gasificada')),

            ]
        );
    }

    private function processProyecto($row, $solicitud)
    {
        return Proyecto::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'tipo_proyecto' => trim($this->col($row, 'Tipo de proyecto')) ?: null,
                'codigo_proyecto' => trim($this->col($row, 'Código de proyecto')) ?: null,
                'categoria_proyecto' => trim($this->col($row, 'Categoría de proyecto')) ?: null,
                'sub_categoria_proyecto' => trim($this->col($row, 'Sub Categoría de proyecto')) ?: null,
                'codigo_objeto_conexion' => trim($this->col($row, 'Código de Objeto de conexión')) ?: null,

            ]
        );
    }

    private function processInstalacion($row, $solicitud)
    {
        return Instalacion::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'tipo_instalacion' => trim($this->col($row, 'Tipo de instalación')) ?: null,
                'tipo_acometida' => trim($this->col($row, 'Tipo de acometida')) ?: null,
                'numero_puntos_instalacion' => trim($this->col($row, 'Número de puntos de instalación proyectados')) ?: null,
                'fecha_finalizacion_instalacion_interna' => $this->parseDate(trim($this->col($row, 'Fecha de finalización de la Instalación Interna'))) ?: null,
                'fecha_finalizacion_instalacion_acometida' => $this->parseDate(trim($this->col($row, 'Fecha de finalización de la Instalación de Acometida'))) ?: null,
                'resultado_instalacion_tc' => trim($this->col($row, 'Resultado de la Instalación de TC')) ?: null,
                'fecha_programacion_habilitacion' => $this->parseDate(trim($this->col($row, 'Fecha de programación de Habilitación'))) ?: null,
            ]
        );
    }
}
