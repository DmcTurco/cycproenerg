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
use App\Models\Logs;
use App\Services\ControlInternoClasificador;
use App\Services\ControlInternoIndicadores;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    public $processId;

    // CI-8: id de la fila en `logs` (bitácora persistente) creada por
    // ClientController::change() antes de despachar el job. Nullable por si
    // algún día se dispara este job sin pasar por esa acción (ej. un comando
    // artisan de carga manual) — en ese caso simplemente no se escribe bitácora.
    protected $logId;

    /**
     * CI-8: contadores de Control Interno de esta carga, para la bitácora
     * (equivalente al resumen de la hoja INICIO del Excel original).
     */
    protected array $ciCounters = [
        'nuevas_general' => 0,
        'actualizadas' => 0,
        'movidas_general_construido' => 0,
        'movidas_general_tc' => 0,
        'movidas_construido_tc' => 0,
        'movidas_a_pend_anulacion' => 0,
        'filas_omitidas_validacion' => 0,
    ];

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

            // CI-8: la bitácora ya se creó en ClientController::change() con
            // total_filas=0 (todavía no se había leído el archivo); acá se
            // completa apenas se sabe cuántas filas trae.
            $this->log()?->update(['total_filas' => $totalFilas]);

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

            // CI-8: bitácora final — contadores de filas + resumen de
            // Control Interno de esta carga (nuevas a GENERAL, movidas entre
            // fases, filas omitidas por no pasar la validación mínima).
            $this->log()?->update([
                'filas_procesadas' => $result['total'],
                'filas_con_error' => $result['failed'],
                'estado' => 'completado',
                'resumen_control_interno' => $this->ciCounters,
            ]);

            // CI-10: ControlInternoDashboard::resumen() cachea 5 minutos (el
            // desglose de GENERAL recorre cada solicitud para calcular su
            // semáforo). Sin este forget, una carga de Excel recién terminada
            // no se vería reflejada en el Resumen hasta que venciera ese caché.
            Cache::forget('control_interno_dashboard_' . now()->year . '_' . (int) ceil(now()->month / 3));

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
                'resumen_control_interno' => $this->ciCounters,
            ]);

            event(new ExcelProcessingFailed($e->getMessage()));
            Storage::delete($this->filePath);
            throw $e;
        }
    }

    /**
     * CI-8: la fila de bitácora (`logs`) de esta carga, si se pasó un
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
                $this->ciCounters['filas_omitidas_validacion']++;
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
                    $this->processFaseControlInterno($row, $solicitud);

                    // CI-5 (13/09/2026): recalcula y guarda el caché de
                    // indicadores (semáforo, desface, días hábiles, fuera de
                    // plazo) de esta solicitud ahora que ya quedaron
                    // guardados fase/instalación/proyecto — así el listado y
                    // el Resumen (que leen el caché, no calculan al vuelo)
                    // ya muestran esta carga sin esperar al comando diario.
                    ControlInternoIndicadores::calcularYGuardar($solicitud);

                    return $solicitud->wasRecentlyCreated;
                });

                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                    // CI-8: "Actualizadas" de la bitácora del Excel — total de
                    // solicitudes que ya existían y se volvieron a procesar en
                    // esta carga (se movieran de fase o no). El Excel original
                    // lo etiqueta "(GENERAL + CONSTRUIDO + PEND...)" pero no
                    // se desglosa por fase acá: es el mismo total que ya se
                    // usaba para la barra de progreso ($updated).
                    $this->ciCounters['actualizadas']++;
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
     * CI-1/CI-10: código corto de empresa (CYC/CLB) para Control Interno.
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

        $codigo = config("const.control_interno.codigos_empresa_por_ruc.{$numeroDocumento}");

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
                // 12/09/2026 al construir CI-5) — "Fecha de aprobación del
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
     * CI-4: motor de clasificación y movimientos entre fases.
     *
     * Reversa-ingenierizado del VBA original (Modulo_Internas.bas, macro
     * `Procesar` + funciones `EsTC`/`PortalAnulada`) para reproducir exactamente
     * las mismas reglas del Excel — el detalle completo de las reglas vive en
     * `ControlInternoClasificador::clasificar()`, que es el que de verdad las
     * aplica; este método solo traduce la fila del Excel a los dos datos que
     * ese servicio necesita. Se compartió como servicio porque
     * `ControlInternoManualController` también lo usa (para reclasificar al
     * toque cuando el staff edita F. CONSTRUCCIÓN/ANULAR a mano, sin esperar
     * la próxima carga de Excel).
     */
    private function processFaseControlInterno($row, $solicitud)
    {
        $tcConcluida = strtoupper(trim((string) $this->col($row, 'Resultado de la Instalación de TC'))) === 'CONCLUIDA';
        // "F. TC (portal)" del Excel: la fecha que queda registrada al pasar a
        // TC. CI-5 la usa para el indicador CICLO (suscripción -> TC).
        $fechaTc = $tcConcluida
            ? $this->parseDate(trim((string) $this->col($row, 'Fecha de Registro de resultado de TC')))
            : null;

        // CI-8: se lee la fase ANTES de clasificar (una sola consulta extra)
        // para poder contar en la bitácora si la solicitud es nueva en
        // Control Interno o si esta carga la movió de fase. `null` significa
        // que todavía no tenía fila en fase_control_internos (es nueva).
        $faseAntes = FaseControlInterno::where('solicitud_id', $solicitud->id)->value('fase');

        $fase = ControlInternoClasificador::clasificar($solicitud, $tcConcluida, $fechaTc);

        $this->registrarMovimientoControlInterno($faseAntes, $fase->fase);
    }

    /**
     * CI-8: acumula en $ciCounters el tipo de movimiento de fase que produjo
     * esta fila, para el resumen de Control Interno de la bitácora.
     */
    private function registrarMovimientoControlInterno(?string $antes, string $despues): void
    {
        if ($antes === null) {
            $this->ciCounters['nuevas_general']++;
            return;
        }

        if ($antes === $despues) {
            return;
        }

        $clave = match (true) {
            $antes === FaseControlInterno::GENERAL && $despues === FaseControlInterno::CONSTRUIDO => 'movidas_general_construido',
            $antes === FaseControlInterno::GENERAL && $despues === FaseControlInterno::TC => 'movidas_general_tc',
            $antes === FaseControlInterno::CONSTRUIDO && $despues === FaseControlInterno::TC => 'movidas_construido_tc',
            $despues === FaseControlInterno::PEND_ANULACION => 'movidas_a_pend_anulacion',
            default => null,
        };

        if ($clave !== null) {
            $this->ciCounters[$clave]++;
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
     * CI-1/CI-9/CI-7: el mapeo de categoría a su código corto (RES/MULTI/COM,
     * `config('const.control_interno.categorias')`) compara el texto tal
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

        foreach (array_keys(config('const.control_interno.categorias', [])) as $categoriaConocida) {
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
                // CI-6 (parte segura): se capturan tal cual del portal, pero
                // a propósito NO disparan ninguna regla de eliminación o
                // archivado de la Solicitud — ver la nota de la migración
                // add_anulacion_columns_to_instalacions_table y
                // docs/modulos/control-interno.md (CI-6).
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
        $texto = strtoupper(trim((string) $value));

        return $texto === 'SÍ' || $texto === 'SI';
    }
}
