<?php

namespace App\Http\Controllers\Api;

use App\Helpers\TipoDocumentoHelper;
use App\Http\Controllers\Controller;
use App\Models\EstadoInterno;
use App\Models\Historial;
use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiSolicitudTecnico extends Controller
{


    public function getSolicitudTecnico(Request $request)
    {
        // Verificar autenticación
        $tecnico = Tecnico::find(auth()->id());
        if (!$tecnico) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $estadosCase = TipoDocumentoHelper::buildEstadosCase("ei.estado_const_id");

        $solicitudes = DB::table('solicituds as s')
            ->select([
                's.*',
                'ei.estado_const_id',
                'ep.abreviatura',
                DB::raw($estadosCase['nombre']),
                DB::raw($estadosCase['badge']),
                'sol.numero_documento',
                'sol.nombre as solicitante_nombre',
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
            ->leftJoin('proyectos as p', 's.id', '=', 'p.solicitud_id')
            ->join('solicitud_tecnico as st', function ($join) use ($tecnico) {
                $join->on('s.id', '=', 'st.solicitud_id')
                    ->where('st.tecnico_id', $tecnico->id)
                    ->whereNull('st.deleted_at');
            })
            //    ->latest('st.created_at')
            ->get();

        return response()->json($solicitudes);
    }


    public function updateEstado(Request $request)
    {
       $request->validate([
           'solicitud_id' => 'required|exists:solicituds,id',
           'estado_id' => 'required|integer',
           'estado_nombre' => 'required|string'
       ]);
    
       $tecnico = auth()->user();
    
       // Verificar si la solicitud pertenece al técnico
       $solicitudTecnico = DB::table('solicitud_tecnico')
           ->where('solicitud_id', $request->solicitud_id)
           ->where('tecnico_id', $tecnico->id)
           ->whereNull('deleted_at')
           ->first();
    
       if (!$solicitudTecnico) {
           return response()->json(['error' => 'La solicitud no pertenece a este técnico'], 403);
       }
    
       try {
           DB::beginTransaction();
    
           EstadoInterno::where('solicitud_id', $request->solicitud_id)
               ->update(['estado_const_id' => $request->estado_id]);
    
           Historial::create([
               'solicitud_id' => $request->solicitud_id,
               'tecnico_id' => $tecnico->id,
               'estado_const_id' => $request->estado_id,
               'descripcion' => "El técnico {$tecnico->nombre} ha cambiado el estado a: {$request->estado_nombre}",
               'employee_id' => null,
           ]);
    
           DB::commit();
           return response()->json(['message' => 'Estado actualizado correctamente']);
       } catch (\Exception $e) {
           DB::rollback();
           return response()->json(['error' => $e->getMessage()], 500);
       }
    }





}
