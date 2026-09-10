<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolicitudController extends Controller
{


    public function getFullSolicitudDetails($id)
    {
        try {
            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de solicitud inválido'
                ], 400);
            }

            // Query SQL con nombres de tablas corregidos (en minúscula)
            $solicitud = DB::select("
                SELECT 
                    -- Datos de Solicitud
                    s.id as solicitud_id,
                    s.numero_solicitud,
                    s.numero_suministro,
                    s.numero_contrato_suministro,
                    s.fecha_aprobacion_contrato,
                    s.fecha_registro_portal,
                    s.estado_portal_id,
                    est.codigo as estado_codigo,
                    est.nombre as estado_nombre,
                    est.abreviatura as estado_abreviatura,

                    -- Datos del Solicitante
                    sol.tipo_documento as solicitante_tipo_documento,
                    sol.numero_documento as solicitante_numero_documento,
                    sol.nombre as solicitante_nombre,
                    sol.celular as solicitante_celular,
                    sol.correo_electronico as solicitante_email,
                    sol.usuario_fise as solicitante_usuario_fise,
                    
                    -- Datos de la Empresa
                    e.tipo_documento as empresa_tipo_documento,
                    e.numero_documento as empresa_numero_documento,
                    e.nombre as empresa_nombre,
                    e.registro_gas_natural as empresa_registro_gas,
                    
                    -- Datos de la Concesionaria
                    c.tipo_documento as concesionaria_tipo_documento,
                    c.numero_documento as concesionaria_numero_documento,
                    c.nombre as concesionaria_nombre,
                    
                    -- Datos de Instalación
                    i.tipo_instalacion,
                    i.tipo_acometida,
                    i.numero_puntos_instalacion,
                    i.fecha_finalizacion_instalacion_interna,
                    i.fecha_finalizacion_instalacion_acometida,
                    i.resultado_instalacion_tc,
                    i.fecha_programacion_habilitacion,
                    
                    -- Datos del Proyecto
                    p.tipo_proyecto,
                    p.codigo_proyecto,
                    p.categoria_proyecto,
                    p.sub_categoria_proyecto,
                    p.codigo_objeto_conexion,
                    
                    -- Datos de Ubicación
                    u.ubicacion,
                    u.codigo_manzana,
                    u.codigo_identificacion_interna,
                    u.nombre_malla,
                    u.direccion,
                    u.departamento,
                    u.provincia,
                    u.distrito,
                    u.venta_zona_no_gasificada,
                    
                    -- Datos del Asesor
                    a.nombre as asesor_nombre,
                    a.tipo_documento as asesor_tipo_documento,
                    a.numero_documento_identificacion as asesor_numero_documento,
                    a.telefono as asesor_telefono,
                    a.email as asesor_email,
                    a.direccion as asesor_direccion
                FROM solicituds s
                LEFT JOIN solicitantes sol ON s.solicitante_id = sol.id
                LEFT JOIN empresas e ON s.empresa_id = e.id
                LEFT JOIN concesionarias c ON s.concesionaria_id = c.id
                LEFT JOIN instalacions i ON s.id = i.solicitud_id
                LEFT JOIN proyectos p ON s.id = p.solicitud_id
                LEFT JOIN ubicacions u ON s.id = u.solicitud_id
                LEFT JOIN asesores a ON s.asesor_id = a.id
                LEFT JOIN estado_portals est ON CAST(s.estado_portal_id AS INTEGER) = est.id
                WHERE s.id = ?
            ", [$id]);

            // Si no se encontró la solicitud
            if (empty($solicitud)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada'
                ], 404);
            }

            // Para solucionar el problema de UTF-8, limpiamos los datos antes del JSON
            $tiposDocumento = config('const.tipo_documeto');
            $tiposDocumentoMap = collect($tiposDocumento)->pluck('name', 'id')->toArray();
            $solicitudData = $solicitud[0];

            // Limpiar datos para eliminar caracteres UTF-8 malformados
            $solicitudData = json_decode(json_encode($solicitudData), true);
            array_walk_recursive($solicitudData, function (&$item) {
                if (is_string($item)) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            });

            $tipoDocumentoId = $solicitudData['solicitante_tipo_documento'];
            $solicitudData['solicitante_tipo_documento_nombre'] = $tiposDocumentoMap[$tipoDocumentoId] ?? 'No especificado';

            $fechasAFormatear = [
                'fecha_aprobacion_contrato',
                'fecha_registro_portal',
                'fecha_finalizacion_instalacion_interna',
                'fecha_finalizacion_instalacion_acometida',
                'fecha_programacion_habilitacion'
            ];

            foreach ($fechasAFormatear as $campo) {
                if (!empty($solicitudData[$campo])) {
                    try {
                        $fecha = date_create($solicitudData[$campo]);
                        if ($fecha) {
                            $solicitudData[$campo] = date_format($fecha, 'd/m/Y');
                        }
                    } catch (\Exception $ex) {
                        // Si hay un error al formatear, dejamos la fecha original
                        Log::warning("Error al formatear la fecha {$campo}: " . $ex->getMessage());
                    }
                }
            }

            // Retornar el resultado con los datos limpios
            return response()->json([
                'success' => true,
                'data' => $solicitudData
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener solicitud: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
