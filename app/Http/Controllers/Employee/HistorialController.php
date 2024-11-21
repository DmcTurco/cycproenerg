<?php

namespace App\Http\Controllers\Employee;

use App\Helpers\TipoDocumentoHelper;
use App\Http\Controllers\Controller;
use App\Models\Historial;
use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistorialController extends Controller
{
    public function index($tecnicoId) 
    {
        $tecnico = Tecnico::findOrFail($tecnicoId);
        $estadosCase = TipoDocumentoHelper::buildEstadosCase();
        // $historial = Historial::where('tecnico_id', $tecnicoId)->paginate(10);
        $historial = DB::table('historials as h')
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
                    ->join('solicituds as s','s.id','=','h.solicitud_id')
                    ->join('estado_internos as ei', 's.id', '=', 'ei.solicitud_id')
                    ->join('estado_portals as ep', 's.estado_portal_id', '=', 'ep.id')
                    ->join('employees as e','e.id','=','h.employee_id')
                    ->whereNull('h.deleted_at')
                    ->paginate(10);
         

        return view('employee.pages.tecnicos.historial.index', compact('tecnico','historial'));
    }
}
