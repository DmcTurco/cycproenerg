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
use App\Models\Logs;
use App\Models\Auditoria;
use App\Models\Employee;
use App\Services\ControlGeneralClasificador;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    public $processId;

    // Id de la fila en `logs` (bitácora persistente) creada por
    // ClientController::change() antes de despachar el job. Nullable por si
    // algún día se dispara este job sin pasar por esa acción (ej. un comando
    // artisan de carga manual) — en ese caso simplemente no se escribe bitácora.
    protected $logId;

    /**
     * Control General (Internas · TC · Habilitación): cuántas solicitudes
     * entraron nuevas a cada etapa y cuántas cambiaron de etapa en esta carga
     * (equivalente al mensaje final de la macro ActualizarControl).
     */
    protected array $cgCounters = [
        'filas_omitidas_validacion' => 0,
        'nuevas' => [],
        'movidas' => 0,
        'actualizadas' => 0,
    ];

    // Provincias que entran a Control General (se resuelve una vez por carga).
    protected ?array $cgAmbito = null;

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

    public function __construct($filePath, $processId, $logId = null)
    {
        $this->filePath = $filePath;
        $this->processId = $processId;
        $this->logId = $logId;
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

            $totalFilas = count($rows) - 1; // Excluir header

            // Inicializar el progreso
            Cache::put("excel_progress_{$this->processId}", [
                'progress' => 0,
                'processed' => 0,
                'total' => $totalFilas,
                'created' => 0,
                'updated' => 0
            ], now()->addHours(1));

            // La bitácora ya se creó en ClientController::change() con
            // total_filas=0 (todavía no se había leído el archivo); acá se
            // completa apenas se sabe cuántas filas trae.
            $this->log()?->update(['total_filas' => $totalFilas]);

            // Auditoría: la carga toca miles de registros; en vez de una
            // entrada por fila se registra UNA entrada resumen (abajo).
            $result = Auditoria::sinRegistrar(fn () => $this->processRows($rows));

            Auditoria::registrar(
                'importado',
                "Cargó el Excel del portal ({$totalFilas} filas): {$result['created']} solicitudes nuevas, {$result['updated']} actualizadas, {$result['failed']} con error",
                $this->log(),
                null,
                ['resultado' => $result, 'control_general' => $this->cgCounters],
                'Carga Excel del portal',
                $this->log()?->employee_id ? Employee::find($this->log()->employee_id) : null,
            );

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

            // Bitácora final — contadores de filas + resumen de
            // Control General de esta carga (nuevas por etapa, movidas entre
            // fases, filas omitidas por no pasar la validación mínima).
            $this->log()?->update([
                'filas_procesadas' => $result['total'],
                'filas_con_error' => $result['failed'],
                'estado' => 'completado',
                'resumen_carga' => $this->cgCounters,
            ]);

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

            $this->log()?->update([
                'estado' => 'error',
                'errores' => $e->getMessage(),
                'resumen_carga' => $this->cgCounters,
            ]);

            event(new ExcelProcessingFailed($e->getMessage()));
            Storage::delete($this->filePath);
            throw $e;
        }
    }

    /**
     * La fila de bitácora (`logs`) de esta carga, si se pasó un
     * `$logId` al construir el job (ver ClientController::change()).
     */
    private function log(): ?Logs
    {
        return $this->logId ? Logs::find($this->logId) : null;
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

            if (!$this->validateRow($row)) {
                $this->cgCounters['filas_omitidas_validacion']++;
                continue;
            }

            try {
                $wasCreated = DB::transaction(function () use ($row) {
                    $empresa = $this->processEmpresa($row);
                    $concesionaria = $this->processConcesionaria($row);
                    $solicitante = $this->processSolicitante($row);
                    $estadoPortal = $this->processEstadoPortal($row);
                    $solicitud = $this->processSolicitud($row, $solicitante, $empresa, $concesionaria, $estadoPortal);
                    $this->processEstadoInterno($estadoPortal, $solicitud);
                    $this->processUbicacion($row, $solicitud);
                    $this->processProyecto($row, $solicitud);
                    $this->processInstalacion($row, $solicitud);
                    $this->processControlGeneral($row, $solicitud, $estadoPortal);

                    return $solicitud->wasRecentlyCreated;
                });

                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                }
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

        // Número de serie de Excel (ej. 46274.70204993056): días desde 1900
        if (is_numeric($date) && (float) $date > 0 && (float) $date < 2958466) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $date)->format('Y-m-d');
        }

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
        $numeroDocumento = trim($this->col($row, 'Número de documento de identificación de la Empresa Instaladora Ejecutora'));

        $empresa = $this->firstOrCreateSafe(Empresa::class,
            ['numero_documento' => $numeroDocumento],
            [
                'tipo_documento' => $tipo_documento_id,
                'nombre' => trim($this->col($row, 'Nombre de la Empresa Instaladora Ejecutora')),
                'registro_gas_natural' => trim($this->col($row, 'Registro de Gas Natural de la de Empresa Instaladora Ejecutora')),
            ]
        );

        $this->asignarCodigoEmpresa($empresa, $numeroDocumento);

        return $empresa;
    }

    /**
     * Código corto de empresa (CYC/CLB), usado por el filtro de empresa.
     * Se resuelve acá, por RUC, en cada carga de Excel — no en un seeder
     * aparte — porque esta misma función ya identifica a la Empresa por RUC
     * (`processEmpresa` de arriba); duplicar esa comparación en un seeder
     * era redundante y además dependía de correr ese comando después de que
     * la Empresa ya existiera (si no, el `update` no encontraba nada). Solo
     * completa el código si todavía está vacío, para no pisar un código que
     * se haya corregido a mano.
     */
    private function asignarCodigoEmpresa(Empresa $empresa, string $numeroDocumento): void
    {
        if ($empresa->codigo) {
            return;
        }

        $codigo = config("const.empresas.codigos_por_ruc.{$numeroDocumento}");

        if ($codigo) {
            $empresa->update(['codigo' => $codigo]);
        }
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
                // OJO: el nombre de la columna del portal es "Fecha de suscripción
                // de contrato" (confirmado con una descarga real del portal el
                // 12/09/2026) — "Fecha de aprobación del
                // contrato" no existe en el archivo y nunca se encontraba, así
                // que este campo quedaba siempre null. Se mantiene el nombre de
                // columna `fecha_aprobacion_contrato` en la base de datos (ya se
                // usa en otras pantallas) para no tener que renombrarla; lo que
                // cambia es solo el encabezado que se busca en el Excel.
                'fecha_aprobacion_contrato' => $this->parseDate(trim($this->col($row, 'Fecha de suscripción de contrato'))),
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
     * Control General (Internas · TC · Habilitación): ubica la solicitud en
     * su etapa según lo que dice el portal. Las reglas están en
     * ControlGeneralClasificador::aplicarPortal() (traducción de la macro
     * ActualizarControl del Excel "Control General CLB - C&C.xlsm").
     */
    private function processControlGeneral($row, Solicitud $solicitud, EstadoPortal $estadoPortal): void
    {
        $this->cgAmbito ??= ControlGeneralClasificador::ambito();

        $resultado = ControlGeneralClasificador::aplicarPortal($solicitud, [
            'provincia' => $this->col($row, 'Provincia'),
            'estado' => $estadoPortal->codigo,
            'resultado_tc' => $this->col($row, 'Resultado de la Instalación de TC'),
            'fecha_suscripcion' => $this->parseDate(trim((string) $this->col($row, 'Fecha de suscripción de contrato'))),
            'fecha_entrega_documentos' => $this->parseDate(trim((string) $this->col($row, 'Fecha de entrega de documentos al Concesionario'))),
            'anulada' => $this->esSi($this->col($row, 'Anulada')),
            'rechazada' => $this->esSi($this->col($row, 'Rechazada')),
        ], $this->cgAmbito);

        if (!$resultado) {
            return;
        }

        if ($resultado['antes'] === null) {
            $etapa = $resultado['despues'];
            $this->cgCounters['nuevas'][$etapa] = ($this->cgCounters['nuevas'][$etapa] ?? 0) + 1;
        } elseif ($resultado['antes'] !== $resultado['despues']) {
            $this->cgCounters['movidas']++;
        } else {
            $this->cgCounters['actualizadas']++;
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
                'categoria_proyecto' => $this->normalizarCategoria(trim((string) $this->col($row, 'Categoría de proyecto'))),
                'sub_categoria_proyecto' => trim($this->col($row, 'Sub Categoría de proyecto')) ?: null,
                'codigo_objeto_conexion' => trim($this->col($row, 'Código de Objeto de conexión')) ?: null,

            ]
        );
    }

    /**
     * El mapeo de categoría a su código corto (RES/MULTI/COM,
     * `config('const.categorias_proyecto')`) compara el texto tal
     * cual viene del portal contra "Residencial"/"Multifamiliar"/"Comercio"
     * exactos — a pedido de Turco (13/09/2026), acá se normalizan mayúsculas/
     * minúsculas y espacios antes de guardar, para que ese mapeo no falle
     * silenciosamente solo porque el portal mandó "RESIDENCIAL" o
     * " Residencial " en vez de "Residencial". Si el texto no coincide con
     * ninguna categoría conocida, se guarda tal cual vino (no se inventa una
     * categoría) — sigue mostrándose como texto libre en el listado.
     */
    private function normalizarCategoria(string $valor): ?string
    {
        if ($valor === '') {
            return null;
        }

        foreach (array_keys(config('const.categorias_proyecto', [])) as $categoriaConocida) {
            if (mb_strtoupper($valor, 'UTF-8') === mb_strtoupper($categoriaConocida, 'UTF-8')) {
                return $categoriaConocida;
            }
        }

        return $valor;
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
                // Se capturan tal cual del portal. Control General ignora las
                // filas con Anulada/Rechazada = Sí (ver ControlGeneralClasificador).
                'rechazada' => $this->esSi($this->col($row, 'Rechazada')),
                'anulada' => $this->esSi($this->col($row, 'Anulada')),
                'motivo_anulacion' => trim((string) $this->col($row, 'Motivo de anulación')) ?: null,
            ]
        );
    }

    /**
     * El portal marca sus columnas booleanas (Rechazada, Anulada, ...) con el
     * texto literal "Sí"/"No", igual que compara el VBA original
     * (`Trim(CStr(...)) = "Sí"` en PortalAnulada, Modulo_Internas.bas).
     */
    private function esSi($value): bool
    {
        // mb_strtoupper: strtoupper() no convierte la "í" y "Sí" nunca coincidía.
        $texto = mb_strtoupper(trim((string) $value), 'UTF-8');

        return $texto === 'SÍ' || $texto === 'SI';
    }
}
