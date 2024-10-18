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
use App\Models\Estado;

class ClientController extends Controller
{

    public function index(Request $request)
    {

        // Obtenemos la lista de estados desde la base de datos
        $estados = Estado::orderBy('nombre', 'DESC')->get(); // Esto obtiene todos los estados ordenados por nombre en orden ascendente

        // Comenzamos una consulta base de Solicitud con la relación 'estado'
        $query = Solicitud::with('estado');

        // Aplicar filtros si están presentes en la solicitud
        if ($request->filled('numero_solicitud')) {
            $query->where('numero_solicitud', 'like', '%' . $request->numero_solicitud . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado_solicitud', $request->estado);
        }

        if ($request->filled('dni')) {
            $query->whereHas('solicitante', function ($q) use ($request) {
                $q->where('numero_documento', 'like', '%' . $request->dni . '%');
            });
        }

        if ($request->filled('nombre')) {
            $query->whereHas('solicitante', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->nombre . '%');
            });
        }

        if ($request->filled('numero_suministro')) {
            $query->where('numero_suministro', 'like', '%' . $request->numero_suministro . '%');
        }

        // Ejecutar la consulta paginada
        $clientesConSolicitudes = $query->paginate(10)->appends($request->except('page'));

        // Obtener el total de solicitudes
        $totalSolicitudes = Solicitud::count();

        // Obtener el total de solicitudes filtradas
        $totalSolicitudesFiltradas = $query->count();

        // Aplicar tipo_documento_nombre para cada solicitud como lo hacías antes
        foreach ($clientesConSolicitudes as $solicitud) {
            $solicitud->tipo_documento_nombre = $this->getTipoDocumentoName(optional($solicitud->solicitante)->tipo_documento);
        }

        // Retornar la vista con los resultados
        return view('company.pages.clients.index', compact('clientesConSolicitudes', 'estados', 'totalSolicitudes', 'totalSolicitudesFiltradas'));
    }



    private function getTipoDocumentoName($id)
    {
        $tipos_documento = config('const.tipo_documeto');
        foreach ($tipos_documento as $tipo) {
            if ($tipo['id'] == $id) {
                return $tipo['name'];
            }
        }
        return 'N/A';
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
            $estado = $this->processEstado($row);
            $solicitud = $this->processSolicitud($row, $solicitante, $empresa, $concesionaria, $estado);
            $ubicacion = $this->processUbicacion($row, $solicitud);
            $proyecto = $this->processProyecto($row, $solicitud);
            $instalacion = $this->processInstalacion($row, $solicitud);

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

    private function processEstado($row)
    {
        // Procesar el estado
        $estadoCompleto = trim($row['CO']);
        $partes = explode('-', $estadoCompleto, 2);
        $codigo = $partes[0];
        $nombre = $partes[1] ?? '';
        $abreviatura = $this->obtenerAbreviatura($nombre);

        // Crear el estado si no existe
        return Estado::firstOrCreate(
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

        // Si la abreviatura es muy corta (menos de 2 caracteres), usamos las dos primeras palabras
        if (strlen($iniciales) < 2) {
            return implode(' ', array_slice($palabras, 0, 2));
        }

        return $iniciales;
    }

    // private function obtenerAbreviatura($nombre)
    // {
    //     $palabrasClave = [
    //         'registrada', 'observada', 'subsanada', 'aprobada', 'Habilitación registrado', 'Habilitación observada', 
    //         'programada', 'iniciada', 'finalizada', 'sin descargo'
    //     ];

    //     foreach ($palabrasClave as $palabra) {
    //         if (stripos($nombre, $palabra) !== false) {
    //             return $palabra;
    //         }
    //     }

    //     // Si no se encuentra una palabra clave, usamos las primeras palabras
    //     $palabras = explode(' ', $nombre);
    //     return implode(' ', array_slice($palabras, 0, 2));
    // }

    private function processSolicitud($row, $solicitante, $empresa, $concesionaria, $estado)
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
                'estado_solicitud' => $estado->id,
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
                'fecha_finalizacion_instalacion_interna' => $this->parseDate(trim($row['AA'])) ?: null,
                'fecha_finalizacion_instalacion_acometida' => $this->parseDate(trim($row['AB'])) ?: null,
                'resultado_instalacion_tc' => trim($row['CQ']) ?: null,
                'fecha_programacion_habilitacion' => $this->parseDate(trim($row['AC'])) ?: null,
            ]
        );
    }
}
