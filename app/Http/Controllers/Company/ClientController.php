<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

use App\Models\Client;
use App\Models\Document;
use App\Models\Installation;
use App\Models\Enterprise;
use App\Models\Inspector;
use App\Models\Project;
use App\Models\Result;
use App\Jobs\ProcessExcelJob;
use App\Models\Concesionaria;
use App\Models\Empresa;
use App\Models\Proyecto;
use App\Models\Prueba;
use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\Ubicacion;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

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

    public function change(Request $request)
    {
        // Validación del archivo cargado
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo debe ser un documento Excel válido (.xlsx o .xls).'
            ], 422);
        }

        // Inicializar contadores y flag de error
        $createdCount = 0;
        $updatedCount = 0;
        $hasErrors = false;

        // Verificar si el archivo ha sido subido
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            try {
                // Cargar el archivo Excel
                $spreadsheet = IOFactory::load($file->getPathName());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true);

                // Verificar si el archivo está vacío
                if (count($rows) <= 1) {
                    throw new \Exception("El archivo Excel está vacío o solo contiene encabezados.");
                }

                // Iterar sobre las filas (omitir la primera fila si es el header)
                foreach ($rows as $index => $row) {
                    if ($index == 1) {
                        continue; // Ignorar el header (primera fila)
                    }

                    // Validar que las columnas clave existen y tienen datos válidos
                    if (empty($row['H']) || empty($row['I']) || empty($row['G']) || !is_numeric($row['H']) || !filter_var($row['L'], FILTER_VALIDATE_EMAIL)) {
                        $hasErrors = true;
                        continue;
                    }

                    // Procesamiento del cliente (crear o actualizar)
                    $dni = trim($row['H']);
                    $client = Solicitante::where('numero_documento_identificacion', $dni)->first();
                    if (!$client) {
                        $client = new Solicitante();
                        $client->numero_documento_identificacion = $dni;
                        $createdCount++;
                    } else {
                        $updatedCount++;
                    }

                    // Actualizar datos del cliente
                    $client->tipo_documento_identificacion = trim($row['G']);
                    $client->nombre = trim($row['I']);
                    $client->telefono = trim($row['J']);
                    $client->celular = trim($row['K']);
                    $client->correo_electronico = trim($row['L']);
                    $client->save();

                    $solicitud = Solicitud::where('solicitante_id', $client->id)->first();

                    if (!$solicitud) {
                        $solicitud = new Solicitud();
                        $solicitud->solicitante_id = $client->id;
                    }

                    $solicitud->numero_solicitud = trim($row['A']) ?: null;
                    $solicitud->codigo_identificacion_predio = trim($row['B']) ?: null;
                    $solicitud->numero_suministro = trim($row['C']) ?: null;
                    $solicitud->numero_contrato_suministro = trim($row['D']) ?: null;
                    $solicitud->fecha_registro_aprobacion_portal = $this->parseDate(trim($row['E']));
                    $solicitud->fecha_aprobacion_contrato = $this->parseDate(trim($row['F']));
                    $solicitud->fecha_registro_solicitud_portal = $this->parseDate(trim($row['X']));
                    $solicitud->fecha_programada_instalacion_interna = $this->parseDate(trim($row['Y']));
                    $solicitud->fecha_inicio_instalacion_interna = $this->parseDate(trim($row['Z']));
                    $solicitud->fecha_finalizacion_instalacion_interna = $this->parseDate(trim($row['AA']));
                    $solicitud->fecha_finalizacion_instalacion_acometida = $this->parseDate(trim($row['AB']));
                    $solicitud->fecha_programacion_habilitacion = $this->parseDate(trim($row['AC']));
                    $solicitud->fecha_entrega_documentos_concesionario = $this->parseDate(trim($row['AD']));
                    $solicitud->estado_solicitud = trim($row['CO']) ?: null;
                    $solicitud->ultima_accion_realizada = trim($row['CP']) ?: null;
                    $solicitud->save();

                    $ubicacion = Ubicacion::where('solicitante_id', $client->id)->first();

                    if (!$ubicacion) {
                        $ubicacion = new Ubicacion();
                        $ubicacion->solicitante_id = $client->id;
                    }
    
                    $ubicacion->direccion = trim($row['M']) ?: null;
                    $ubicacion->departamento = trim($row['N']) ?: null;
                    $ubicacion->provincia = trim($row['O']) ?: null;
                    $ubicacion->distrito = trim($row['P']) ?: null;
                    $ubicacion->ubicacion = trim($row['Q']) ?: null;
                    $ubicacion->codigo_manzana = trim($row['R']) ?: null;
                    $ubicacion->nombre_malla = trim($row['S']) ?: null;
                    $ubicacion->save();
    
                    $proyecto = Proyecto::where('solicitante_id', $client->id)->first();
    
                    if (!$proyecto) {
                        $proyecto = new Proyecto();
                        $proyecto->solicitante_id = $client->id;
                    }
    
                    $proyecto->tipo_proyecto = trim($row['AE']) ?: null;
                    $proyecto->codigo_proyecto = trim($row['AF']) ?: null;
                    $proyecto->categoria = trim($row['CL']) ?: null;
                    $proyecto->sub_categoria = trim($row['CM']) ?: null;
                    $proyecto->codigo_objeto_conexion = trim($row['CN']) ?: null;
                    $proyecto->save();
    
                    $empresa = Empresa::where('solicitante_id', $client->id)->first();
    
                    if (!$empresa) {
                        $empresa = new Empresa();
                        $empresa->solicitante_id = $client->id;
                    }
    
                    $empresa->tipo_documento_identificacion = trim($row['AK']) ? : null;
                    $empresa->numero_documento_identificacion = trim($row['AL']) ? : null;
                    $empresa->nombre = trim($row['AM']) ? : null;
                    $empresa->registro_gas_natural = trim($row['AN']) ? : null;
                    $empresa->save();

                    $concesionaria = Concesionaria::where('solicitante_id', $client->id)->first();
    
                    if (!$concesionaria) {
                        $concesionaria = new Concesionaria();
                        $concesionaria->solicitante_id = $client->id;
                    }
    
                    $concesionaria->tipo_documento_identificacion = trim($row['AO']) ? : null;
                    $concesionaria->numero_documento_identificacion = trim($row['AP']) ? : null;
                    $concesionaria->nombre = trim($row['AQ']) ? : null;
                    $concesionaria->save();
                }





                // Preparar el mensaje de respuesta
                if ($hasErrors) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Se encontraron datos inconsistentes. Por favor, revise el archivo y vuelva a intentarlo.'
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => "Se agregaron $createdCount nuevos clientes y se actualizaron $updatedCount clientes existentes."
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error procesando el archivo Excel: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el archivo. Por favor, verifique el formato e intente nuevamente.'
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No se ha subido ningún archivo.'
        ], 400);
    }
}
