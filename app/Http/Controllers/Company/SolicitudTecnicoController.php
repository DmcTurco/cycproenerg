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

    public function index($tecnicoId)
    {
        $tecnico = Tecnico::findOrFail($tecnicoId);
        $estados = $this->getEstados();

        $solicitudesDisponibles = DB::select("
            SELECT 
                s.*,
                e.estado_id,
                sol.numero_documento,
                sol.nombre as solicitante_nombre,
                u.direccion,
                p.categoria_proyecto
            FROM solicituds s
            INNER JOIN estado_solicitud e ON s.id = e.solicitud_id
            LEFT JOIN solicitantes sol ON s.solicitante_id = sol.id
            LEFT JOIN ubicacions u ON s.id = u.solicitud_id
            LEFT JOIN proyectos p ON s.id = p.solicitud_id
            WHERE e.estado_id IN (?, ?)
            ORDER BY s.id ASC
        ", [$estados['pendiente'], $estados['reasignado']]);

        dd($solicitudesDisponibles);
        return view('company.pages.solicitudesTecnico.index', compact('tecnico', 'solicitudesDisponibles'));
    }




    // public function index($tecnicoID)
    // {
    //     $tecnico = Tecnico::findOrFail($tecnicoID);

    //     $solicitudesIndex = $tecnico->solicitudes()->withPivot('solicitud_tecnico.id')->orderBy('solicitud_tecnico.id', 'desc')->paginate(10);
    //     return view('company.pages.solicitudesTecnico.index', compact('solicitudesIndex', 'tecnico'));
    // }

    // public function obtenerSolicitudesIndex($tecnicoID)
    // {
    //     $tecnico = Tecnico::findOrFail($tecnicoID);
    //     if ($tecnico->company_id != Auth::user()->id) {
    //         abort(403);
    //     }
    //     $solicitudes = $tecnico->solicitudes()->withPivot('solicitud_tecnico.id')->orderBy('solicitud_tecnico.id', 'desc')->paginate(10);
    //     $solicitudes->load('solicitante:id,numero_documento_identificacion');
    //     $solicitudes->load('proyecto:id,categoria');
    //     return response()->json([
    //         'solicitudes' => $solicitudes,
    //     ]);
    // }

    // public function store($tecnicoID, Request $request)
    // {

    //     $tecnico = Tecnico::findOrFail($tecnicoID);

    //     if ($tecnico->company_id != Auth::user()->id) {
    //         abort(403);
    //     }

    //     $solicitanteID = $request->solicitanteID;
    //     $categoria = $request->categoria;
    //     $numSolicitud = $request->numSolicitud;
    //     $numDocIdentificacion = $request->numDocIdentificacion;
    //     $nombre = $request->nombre;
    //     $direccion = $request->direccion;

    //     $solicitud = Solicitud::where('numero_solicitud', $numSolicitud)->first();
    //     $solicitudExistente = SolicitudTecnico::where('solicitud_id', $solicitud->id)->first();

    //     if (!is_null($solicitudExistente)) {

    //         $IDtecnico =  $solicitudExistente->tecnico_id;
    //         $tecnicoConSolicitud = Tecnico::find($IDtecnico);
    //         return response()->json([
    //             'errors' => 'El número de solicitud ya esta registrado' . "<br>" .
    //                 'Nombre del técnico: ' . $tecnicoConSolicitud->nombre . "<br>" .
    //                 'Documento de identidad del técnico: ' . $tecnicoConSolicitud->numero_documento_identificacion
    //         ], 422);
    //     }

    //     $tecnico->solicitudes()->attach($solicitud->id, ['categoria' => $categoria]);

    //     return redirect()->route('company.technicals.requests.index', $tecnico->id);
    // }

    // public function edit($tecnicoID, $solicitudID) {}

    // public function destroy($tecnicoID, $solicitudID)
    // {

    //     $tecnico = Tecnico::findOrFail($tecnicoID);
    //     $solicitud = Solicitud::findOrFail($solicitudID);
    //     if ($tecnico->company_id != Auth::user()->id) {
    //         abort(403);
    //     }

    //     $tecnico->solicitudes()->detach($solicitud->id);
    // }

    // public function obtenerRegistros(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'numero_solicitud' => 'nullable|integer',
    //         'numero_documento_identificacion' => 'nullable|integer',
    //         'nombre' => 'nullable|max:255',
    //         'direccion' => 'nullable',
    //     ]);

    //     $validator->after(function ($validator) use ($request) {
    //         if (
    //             empty($request->numero_solicitud) && empty($request->numero_documento_identificacion)
    //             && empty($request->nombre) && empty($request->direccion)
    //         ) {
    //             $validator->errors()->add('atleast_one', 'Debes proporcionar al menos un campo de búsqueda.');
    //         }
    //     });

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $tecnicoID = $request->tecnicoID;
    //     $numSolicitud = $request->numero_solicitud;
    //     $numDocIdentidad = $request->numero_documento_identificacion;
    //     $nombre = strtolower($request->nombre);
    //     $direccion = strtolower($request->direccion);

    //     $query = Solicitud::query();

    //     // Filtro por estado "pendiente" (1) y "reasignado" (4)
    //     $estadoPendiente = 1; // Id del estado "pendiente"
    //     $estadoReasignado = 4;  // Id del estado "reasignado"

    //     if (!empty($numSolicitud)) {
    //         $query->where('numero_solicitud', 'like', "%$numSolicitud%");
    //     }

    //     if (!empty($numDocIdentidad)) {
    //         $query->whereHas('solicitante', function ($q) use ($numDocIdentidad) {
    //             $q->where('numero_documento', 'like', "%$numDocIdentidad%");
    //         });
    //     }

    //     if (!empty($nombre)) {
    //         $query->whereHas('solicitante', function ($q) use ($nombre) {
    //             $q->whereRaw('LOWER(nombre) LIKE ?', ["%" . strtolower($nombre) . "%"]);
    //         });
    //     }

    //     if (!empty($direccion)) {
    //         $query->whereHas('ubicacion', function ($q) use ($direccion) {
    //             $q->whereRaw('LOWER(direccion) LIKE ?', ["%" . strtolower($direccion) . "%"]);
    //         });
    //     }

    //     // Filtrar solo las solicitudes que tienen estados "pendiente" (id 1) o "reasignado" (id 4) en la tabla estado_solicitud
    //     $query->whereHas('estadoSolicitud', function ($q) use ($estadoPendiente, $estadoReasignado) {
    //         $q->whereIn('estado_id', [$estadoPendiente, $estadoReasignado]);
    //     });


    //     $registros = $query->paginate(10);
    //     $registros->appends($request->all());

    //     // Obtenemos los nombres de los estados a partir de la constante
    //     $tipoEstado = collect(config('const.tipo_estado'))->pluck('name', 'id');

    //     return view('company.pages.solicitudesTecnico.respuestas', compact('registros', 'tecnicoID', 'request', 'tipoEstado'));
    //     // return view('company.pages.solicitudesTecnico.respuestas', compact('registros', 'tecnicoID', 'request'));
    // }
}
