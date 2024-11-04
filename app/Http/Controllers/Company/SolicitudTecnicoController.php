<?php

namespace App\Http\Controllers\Company;

use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\SolicitudTecnico;
use App\Models\Tecnico;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        $estadosCase = $this->buildEstadosCase();
        // Query base que se reutiliza
        $baseQuery = DB::table('solicituds as s')
            ->select([
                's.*',
                'e.estado_id',
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
            ->join('estado_solicitud as e', 's.id', '=', 'e.solicitud_id')
            ->leftJoin('solicitantes as sol', 's.solicitante_id', '=', 'sol.id')
            ->leftJoin('ubicacions as u', 's.id', '=', 'u.solicitud_id')
            ->leftJoin('proyectos as p', 's.id', '=', 'p.solicitud_id');

        // Solicitudes disponibles (con búsqueda)
        $solicitudesDisponibles = clone $baseQuery;
        $solicitudesDisponibles->whereIn('e.estado_id', [$estados['pendiente'], $estados['reasignado']]);

        // Aplicar filtros si existen
        if ($request->filled('numero_solicitud')) {
            $solicitudesDisponibles->where('s.numero_solicitud', 'like', '%' . $request->numero_solicitud . '%');
        }
        if ($request->filled('distrito')) {
            $solicitudesDisponibles->where('u.distrito', 'ilike', '%' . $request->distrito . '%');
        }

        $solicitudesDisponibles = $solicitudesDisponibles
            ->orderBy('s.id', 'ASC')
            ->paginate(10, ['*'], 'disponibles_page')
            ->appends($request->all());

        // Solicitudes asignadas
        $solicitudesAsignadas = clone $baseQuery;
        $solicitudesAsignadas = $solicitudesAsignadas
            ->join('solicitud_tecnico as st', 's.id', '=', 'st.solicitud_id')
            ->where('st.tecnico_id', $tecnicoId)
            ->orderBy('st.created_at', 'DESC')
            ->paginate(10, ['*'], 'asignadas_page');

        return view(
            'company.pages.solicitudesTecnico.index',
            compact('tecnico', 'solicitudesAsignadas', 'solicitudesDisponibles')
        );
    }

    private function buildEstadosCase()
    {
        $nombreCase = "CASE e.estado_id ";
        $badgeCase = "CASE e.estado_id ";

        foreach (Config::get('const.tipo_estado') as $estado) {
            $nombreCase .= " WHEN {$estado['id']} THEN '{$estado['name']}'";
            $badgeCase .= " WHEN {$estado['id']} THEN '{$estado['badge']}'";
        }

        return [
            'nombre' => $nombreCase . " END as estado_nombre",
            'badge' => $badgeCase . " END as estado_badge"
        ];
    }


    public function store(Request $request, $tecnicoId)
    {
        try {
            DB::beginTransaction();

            $tecnico = Tecnico::findOrFail($tecnicoId);
            $solicitudId = $request->solicitud_id;

            // Asignar solicitud al técnico
            $tecnico->solicitudes()->attach($solicitudId);

            // Actualizar estado a "asignado" (2)
            DB::table('estado_solicitud')
                ->where('solicitud_id', $solicitudId)
                ->update(['estado_id' => 2]);

            DB::commit();
            return response()->json([
                'success' => true,
                'redirect' => route('company.technicals.requests.index', $tecnicoId),
                'message' => 'Solicitud asignada correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error al asignar solicitud: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al asignar solicitud'], 500);
        }
    }

    public function destroy($tecnicoId, $solicitudId)
    {
        try {
            DB::beginTransaction();

            $tecnico = Tecnico::findOrFail($tecnicoId);
            $solicitud = Solicitud::findOrFail($solicitudId);

            // Desasignar la solicitud
            $tecnico->solicitudes()->detach($solicitudId);

            // Actualizar estado a pendiente (1)
            DB::table('estado_solicitud')
                ->where('solicitud_id', $solicitudId)
                ->update(['estado_id' => 1]);

            DB::commit();
            return response()->json([
                'success' => true,
                'redirect' => route('company.technicals.requests.index', $tecnicoId),
                'message' => 'La solicitud ha sido eliminada.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false], 500);
        }
    }
}
