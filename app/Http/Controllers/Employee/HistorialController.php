<?php

namespace App\Http\Controllers\Employee;

use App\Helpers\TipoDocumentoHelper;
use App\Http\Controllers\Controller;
use App\Models\Historial;
use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistorialController extends Controller
{
    public function index($tecnicoId, Request $request)
    {
        // Validar el request
        $request->validate([
            'search' => 'nullable|string|max:50'
        ]);

        $tecnico = Tecnico::findOrFail($tecnicoId);
        $estadosCase = TipoDocumentoHelper::buildEstadosCase();
        // $historial = Historial::where('tecnico_id', $tecnicoId)->paginate(10);
        try {
            $query = DB::table('historials as h')
                ->select([
                    'h.id',
                    's.numero_solicitud',
                    DB::raw($estadosCase['nombre']),
                    DB::raw($estadosCase['badge']),
                    'h.descripcion',
                    'e.name',
                    'h.created_at',
                    'ep.abreviatura',
                ])
                ->join('solicituds as s', 's.id', '=', 'h.solicitud_id')
                ->join('estado_internos as ei', 's.id', '=', 'ei.solicitud_id')
                ->join('estado_portals as ep', 's.estado_portal_id', '=', 'ep.id')
                ->join('employees as e', 'e.id', '=', 'h.employee_id')
                ->orderBy('s.numero_solicitud', 'DESC')
                ->whereNull('h.deleted_at');

            // Agregar condición de búsqueda si existe
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = trim($request->search); // Eliminar espacios en blanco
                $query->where('s.numero_solicitud', 'LIKE', "%{$searchTerm}%");
            }

            // Ordenar y paginar resultados
            $historial = $query->orderBy('s.numero_solicitud', 'DESC')
                ->paginate(10)
                ->appends($request->query());

            return view('employee.pages.tecnicos.historial.index', compact('tecnico', 'historial'));
        } catch (\Exception $e) {
            Log::error('Error en la búsqueda de historial: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al buscar el historial.');
        }
    }
}
