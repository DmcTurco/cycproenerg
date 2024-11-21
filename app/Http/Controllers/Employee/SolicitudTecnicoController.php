<?php

namespace App\Http\Controllers\Employee;

use App\Helpers\TipoDocumentoHelper;
use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\SolicitudTecnico;
use App\Models\Tecnico;
use App\Http\Controllers\Controller;
use App\Models\EstadoInterno;
use App\Models\Historial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SolicitudTecnicoController extends Controller
{

    private function getEstados()
    {
        $estados = Config::get('const.tipo_estado');
        return [
            'pendiente' => collect($estados)->where('name', 'pendiente')->first()['id'],
            'reasignado' => collect($estados)->where('name', 'reasignado')->first()['id']
        ];
    }
    public function index($tecnicoId, Request $request)
    {
        $tecnico = Tecnico::findOrFail($tecnicoId);
        $estados = $this->getEstados();
        $estadosCase = TipoDocumentoHelper::buildEstadosCase();

        // Query base que se reutiliza
        $baseQuery = DB::table('solicituds as s')
            ->select([
                's.*',
                'ei.estado_const_id',
                'ep.abreviatura',
                DB::raw($estadosCase['nombre']),
                DB::raw($estadosCase['badge']),
                'sol.numero_documento',
                'sol.nombre as solicitante_nombre', // Usamos el nombre del solicitante de la tabla solicitantes
                'u.direccion',
                'u.departamento',
                'u.provincia',
                'u.distrito',
                'u.ubicacion',
                'p.categoria_proyecto'
            ])
            ->join('estado_internos as ei', 's.id', '=', 'ei.solicitud_id')
            ->join('estado_portals as ep', 's.estado_portal_id', '=', 'ep.id')
            ->leftJoin('solicitantes as sol', 's.solicitante_id', '=', 'sol.id')
            ->leftJoin('ubicacions as u', 's.id', '=', 'u.solicitud_id')
            ->leftJoin('proyectos as p', 's.id', '=', 'p.solicitud_id');

        // Solicitudes disponibles (con búsqueda)
        $solicitudesDisponibles = clone $baseQuery;
        $solicitudesDisponibles->whereIn('ei.estado_const_id', [$estados['pendiente'], $estados['reasignado']]);

        // Búsqueda mejorada
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $searchWords = preg_split('/\s+/', trim($searchTerm));

            $solicitudesDisponibles->where(function ($query) use ($searchWords) {
                foreach ($searchWords as $word) {
                    $query->where(function ($subQuery) use ($word) {
                        $subQuery->where(function ($innerQuery) use ($word) {
                            $innerQuery->where('s.numero_solicitud', 'ilike', '%' . $word . '%')
                                ->orWhere('u.distrito', 'ilike', '%' . $word . '%')
                                ->orWhere('p.categoria_proyecto', 'ilike', '%' . $word . '%')
                                ->orWhere('sol.nombre', 'ilike', '%' . $word . '%')
                                ->orWhere('u.departamento', 'ilike', '%' . $word . '%')
                                ->orWhere('u.provincia', 'ilike', '%' . $word . '%');
                        });
                    });
                }
            });
        }

        $solicitudesDisponibles = $solicitudesDisponibles
            ->orderBy('s.numero_solicitud', 'DESC')
            ->paginate(10, ['*'], 'disponibles_page')
            ->appends($request->all());

        // Solicitudes asignadas
        $solicitudesAsignadas = clone $baseQuery;
        $solicitudesAsignadas = $solicitudesAsignadas
            ->join('solicitud_tecnico as st', 's.id', '=', 'st.solicitud_id')
            ->where('st.tecnico_id', $tecnicoId)
            ->whereNull('st.deleted_at')
            ->orderBy('s.numero_solicitud', 'DESC')
            ->paginate(10, ['*'], 'asignadas_page')
            ->appends($request->all());

        return view(
            'employee.pages.solicitudesTecnico.index',
            compact('tecnico', 'solicitudesAsignadas', 'solicitudesDisponibles')
        );
    }


    public function store(Request $request, $tecnicoId)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para continuar.');
        }

        $empleadoID = Auth::id();

        try {
            DB::beginTransaction();

            $tecnico = Tecnico::findOrFail($tecnicoId);

            // Verificar si es una asignación múltiple o individual
            $solicitudIds = $request->has('solicitudes') ? $request->solicitudes : [$request->solicitud_id];

            foreach ($solicitudIds as $solicitudId) {
                // Asignar solicitud al técnico
                $tecnico->solicitudes()->attach($solicitudId);

                EstadoInterno::whereIn('solicitud_id', $solicitudIds)
                    ->update(['estado_const_id' => 2]);

                Historial::create([
                    'solicitud_id' => $solicitudId,
                    'tecnico_id' => $tecnicoId,
                    'estado_const_id' => 2, // Estado "asignado"
                    'descripcion' => 'Solicitud asignada al técnico ' . $tecnico->nombre,
                    'employee_id' => $empleadoID
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'redirect' => route('employee.technicals.requests.index', $tecnicoId),
                'message' => count($solicitudIds) > 1 ? 'Solicitudes asignadas correctamente' : 'Solicitud asignada correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al asignar solicitud(es): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al asignar solicitud(es)'], 500);
        }
    }
    // Método original para eliminación individual
    public function destroy($tecnicoId, $solicitudId)
    {
        try {
            DB::beginTransaction();

            $tecnico = Tecnico::findOrFail($tecnicoId);
            $solicitud = Solicitud::findOrFail($solicitudId);

            // Verificar si la solicitud está asignada al técnico
            $asignacion = SolicitudTecnico::where('tecnico_id', $tecnicoId)
                ->where('solicitud_id', $solicitudId)
                ->exists();
            if (!$asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud no está asignada a este técnico'
                ], 400);
            }

            // Eliminar la relación entre el técnico y la solicitud usando el modelo de la tabla pivote
            SolicitudTecnico::where('tecnico_id', $tecnicoId)
                ->where('solicitud_id', $solicitudId)
                ->delete(); // Esto realiza la eliminación lógica en la tabla pivote

            // Actualizar estado a pendiente (1) en la tabla estado_internos
            EstadoInterno::where('solicitud_id', $solicitudId)
                ->update(['estado_const_id' => 1]);

            Historial::where('tecnico_id', $tecnicoId)
                ->where('solicitud_id', $solicitudId)
                ->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'redirect' => route('employee.technicals.requests.index', $tecnicoId),
                'message' => 'La solicitud ha sido eliminada.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar solicitud: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    // Nuevo método para eliminación múltiple
    public function destroyMultiple($tecnicoId, Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Validar que el técnico existe
            $tecnico = Tecnico::findOrFail($tecnicoId);
            
            // Obtener y validar los IDs de solicitudes
            $solicitudIds = $request->input('solicitudes', []);
            if (empty($solicitudIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se seleccionaron solicitudes para eliminar'
                ], 400);
            }
    
            // Verificar que todas las solicitudes existen y pertenecen al técnico
            $solicitudesPertenecientes = SolicitudTecnico::where('tecnico_id', $tecnicoId)
                ->whereIn('solicitud_id', $solicitudIds)
                ->get();
                
            if ($solicitudesPertenecientes->count() !== count($solicitudIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Algunas solicitudes no existen o ya fueron eliminadas'
                ], 404);
            }
    
            // Realizar las eliminaciones y actualizaciones en batch
            SolicitudTecnico::where('tecnico_id', $tecnicoId)
                ->whereIn('solicitud_id', $solicitudIds)
                ->delete();
    
            EstadoInterno::whereIn('solicitud_id', $solicitudIds)
                ->update(['estado_const_id' => 1]);
    
            Historial::where('tecnico_id', $tecnicoId)
                ->whereIn('solicitud_id', $solicitudIds)
                ->delete();
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'redirect' => route('employee.technicals.requests.index', $tecnicoId),
                'message' => 'Las solicitudes han sido eliminadas exitosamente'
            ]);
    
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar solicitudes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar las solicitudes'
            ], 500);
        }
    }
}
