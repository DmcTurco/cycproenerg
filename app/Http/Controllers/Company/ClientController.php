<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

use App\Jobs\ProcessExcelJob;
use App\Models\Concesionaria;
use App\Models\Empresa;
use App\Models\Proyecto;
use App\Models\Prueba;
use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\Ubicacion;
use App\Models\Instalacion;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use App\Helpers\TipoDocumentoHelper;


class ClientController extends Controller
{
    public function index()
    {
        // $clientesConSolicitudes = Solicitante::with(['solicitudes' => function ($query) {
        //     $query->select('id', 'solicitante_id', 'numero_solicitud', 'numero_suministro', 'numero_contrato_suministro');
        // }])
        //     ->select('id', 'tipo_documento_identificacion', 'nombre')
        //     ->paginate(10); // Paginamos los resultados, 15 por página

        $clientesConSolicitudes = Solicitud::paginate(10);

        return view('company.pages.clients.index', compact('clientesConSolicitudes'));
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }
        return date('Y-m-d', strtotime($date));
    }

    // public function change(Request $request)
    // {
    //     // Validación del archivo cargado
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|file|mimes:xlsx,xls'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'El archivo debe ser un documento Excel válido (.xlsx o .xls).'
    //         ], 422);
    //     }

    //     // Inicializar contadores y flag de error
    //     $createdCount = 0;
    //     $updatedCount = 0;
    //     $hasErrors = false;

    //     // Verificar si el archivo ha sido subido
    //     if ($request->hasFile('file')) {
    //         $file = $request->file('file');

    //         try {
    //             // Cargar el archivo Excel
    //             $spreadsheet = IOFactory::load($file->getPathName());
    //             $sheet = $spreadsheet->getActiveSheet();
    //             $rows = $sheet->toArray(null, true, true, true);

    //             // Verificar si el archivo está vacío
    //             if (count($rows) <= 1) {
    //                 throw new \Exception("El archivo Excel está vacío o solo contiene encabezados.");
    //             }

    //             // Iterar sobre las filas (omitir la primera fila si es el header)
    //             foreach ($rows as $index => $row) {
    //                 if ($index == 1) {
    //                     continue; // Ignorar el header (primera fila)
    //                 }

    //                 // Validar que las columnas clave existen y tienen datos válidos
    //                 if (empty($row['A']) || empty($row['H']) || empty($row['G']) || !is_numeric($row['H'])) {
    //                     $hasErrors = true;
    //                     continue;
    //                 }

    //                 // Procesamiento del solicitante (crear o actualizar)
    //                 $solicitante = Solicitante::updateOrCreate(
    //                     ['numero_documento' => trim($row['H'])],
    //                     [
    //                         'tipo_documento' => trim($row['G']),
    //                         'nombre' => trim($row['I']),
    //                         'celular' => trim($row['K']),
    //                         'correo_electronico' => trim($row['L']),
    //                         'usuario_fise' => trim($row['U']),
    //                     ]
    //                 );

    //                 $empresa = Empresa::updatedOrCreate(
    //                     ['numero_documento' => trim($row['AL'])],
    //                     [
    //                         'tipo_documento' => trim($row['AK']),
    //                         'nombre' => trim($row['AM']),
    //                         'registro_gas_natural' => trim($row['AN']),
    //                     ]
    //                 );

    //                 $concesionaria = Concesionaria::updatedOrCreate(
    //                     ['numero_documento' => trim($row['AP'])],
    //                     [
    //                         'tipo_documento' => trim($row['AO']),
    //                         'nombre' => trim($row['AQ']),
    //                     ]
    //                 );

    //                 $solicitud = Solicitud::updateOrCreate(
    //                     ['solicitante_id' => $solicitante->id],
    //                     ['empresa_id' => $empresa->id],
    //                     ['concesionaria_id' => $concesionaria->id],
    //                     [
    //                         'numero_solicitud' => trim($row['A']) ?: null,
    //                         'numero_suministro' => trim($row['C']) ?: null,
    //                         'numero_contrato_suministro' => trim($row['D']) ?: null,
    //                         'fecha_aprobacion_contrato' => $this->parseDate(trim($row['F'])),
    //                         'fecha_registro_portal' => $this->parseDate(trim($row['X'])),
    //                         'estado_solicitud' => trim($row['CO']),
    //                     ]
    //                 );

    //                 $solicitante->wasRecentlyCreated ? $createdCount++ : $updatedCount++;
    //             }

    //             // Preparar el mensaje de respuesta
    //             if ($hasErrors) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Se encontraron datos inconsistentes. Por favor, revise el archivo y vuelva a intentarlo.'
    //                 ]);
    //             } else {
    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => "Se agregaron $createdCount nuevos solicitantes y se actualizaron $updatedCount solicitantes existentes."
    //                 ]);
    //             }
    //         } catch (\Exception $e) {
    //             Log::error('Error procesando el archivo Excel: ' . $e->getMessage());

    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Error al procesar el archivo. Por favor, verifique el formato e intente nuevamente.'
    //             ], 500);
    //         }
    //     }

    //     return response()->json([
    //         'success' => false,
    //         'message' => 'No se ha subido ningún archivo.'
    //     ], 400);
    // }

    public function change(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo debe ser un documento Excel válido (.xlsx o .xls).'
            ], 422);
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'No se ha subido ningún archivo.'
            ], 400);
        }

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getPathName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) <= 1) {
                throw new \Exception("El archivo Excel está vacío o solo contiene encabezados.");
            }

            $result = $this->processRows($rows);

            return response()->json([
                'success' => true,
                'message' => "Se procesaron {$result['total']} filas. Se agregaron {$result['created']} nuevos registros y se actualizaron {$result['updated']} registros existentes."
            ]);
        } catch (\Exception $e) {
            Log::error('Error procesando el archivo Excel: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processRows($rows)
    {
        $created = 0;
        $updated = 0;

        foreach ($rows as $index => $row) {
            if ($index == 1) continue; // Ignorar el header

            if (!$this->validateRow($row)) continue;

            $empresa = $this->processEmpresa($row);
            $concesionaria = $this->processConcesionaria($row);
            $solicitante = $this->processSolicitante($row);
            $solicitud = $this->processSolicitud($row, $solicitante, $empresa, $concesionaria);
            $ubicacion = $this->processUbicacion($row,$solicitud);
            $proyecto = $this->processProyecto($row,$solicitud);
            $instalacion = $this->processInstalacion($row,$solicitud);

            $solicitud->wasRecentlyCreated ? $created++ : $updated++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated
        ];
    }

    private function validateRow($row)
    {
        return !empty($row['A']) && !empty($row['H']) && !empty($row['G']) && is_numeric($row['H']);
    }

    private function processEmpresa($row)
    {
        $tipo_documento_id = TipoDocumentoHelper::obtenerTipoDocumento(trim($row['AK']));

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
        $tipo_documento_id = TipoDocumentoHelper::obtenerTipoDocumento(trim($row['AO']));
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
        $tipo_documento_id = TipoDocumentoHelper::obtenerTipoDocumento(trim($row['G']));
        return Solicitante::updateOrCreate(
            ['numero_documento' => trim($row['H'])],
            [
                'tipo_documento' => $tipo_documento_id,
                'nombre' => trim($row['I']),
                'celular' => trim($row['K']),
                'correo_electronico' => trim($row['L']),
                'usuario_fise' => trim($row['U']),
            ]
        );
    }

    private function processSolicitud($row, $solicitante, $empresa, $concesionaria)
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
                'estado_solicitud' => trim($row['CO']),
            ]
        );
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
                'fecha_finalizacion_instalacion_interna' =>$this->parseDate(trim($row['AA'])) ?: null,
                'fecha_finalizacion_instalacion_acometida' =>$this->parseDate(trim($row['AB'])) ?: null,
                'resultado_instalacion_tc' => trim($row['CQ']) ?: null,
                'fecha_programacion_habilitacion' =>$this->parseDate(trim($row['AC'])) ?: null,
            ]
        );
    }


}
