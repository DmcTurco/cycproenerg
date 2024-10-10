<?php

namespace App\Http\Controllers\Company;

use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\SolicitudTecnico;
use App\Models\Tecnico;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SolicitudTecnicoController extends Controller
{
    public function index($tecnicoID) {
        $tecnico = Tecnico::findOrFail($tecnicoID);
        if ( $tecnico->company_id != Auth::user()->id) {
            abort(403);
        }
        $solicitudesIndex = $tecnico->solicitudes()->paginate(10);
        return view('company.pages.solicitudesTecnico.index', compact('solicitudesIndex', 'tecnico'));
    }

    public function obtenerSolicitudesIndex($tecnicoID) {
        $tecnico = Tecnico::findOrFail($tecnicoID);
        if ( $tecnico->company_id != Auth::user()->id) {
            abort(403);
        }
        $solicitudes = $tecnico->solicitudes()->paginate(10);
        return response()->json([
            'solicitudes' => $solicitudes,
        ]);
    }

    public function store($tecnicoID, Request $request) {

        $tecnico = Tecnico::findOrFail($tecnicoID);

        if ($tecnico->company_id != Auth::user()->id) {
            abort(403);
        }

        $solicitanteID = $request->solicitanteID;
        $categoria = $request->categoria;
        $numSolicitud = $request->numSolicitud;
        $numDocIdentificacion = $request->numDocIdentificacion;
        $nombre = $request->nombre;
        $direccion = $request->direccion;

        $solicitud = Solicitud::where('numero_solicitud', $numSolicitud)->first();
        $solicitudExistente = SolicitudTecnico::where('solicitud_id', $solicitud->id)->first();

        if (!is_null($solicitudExistente)) {

            $IDtecnico =  $solicitudExistente->tecnico_id;
            $tecnicoConSolicitud = Tecnico::find($IDtecnico);
            return response()->json([
                'errors' => 'El número de solicitud ya esta registrado' . "<br>" .
                            'Nombre del técnico: ' . $tecnicoConSolicitud->nombre . "<br>".
                            'Documento de identidad del técnico: ' . $tecnicoConSolicitud->numero_documento_identificacion
            ], 422);
        }

        $tecnico->solicitudes()->attach($solicitud->id, ['categoria' => $categoria]);

        return redirect()->route('company.technicals.requests.index', $tecnico->id);




    }

    public function edit($tecnicoID, $solicitudID) {

    }

    public function destroy($tecnicoID, $solicitudID) {

        $tecnico = Tecnico::findOrFail($tecnicoID);
        $solicitud = Solicitud::findOrFail($solicitudID);
        if ($tecnico->company_id != Auth::user()->id) {
            abort(403);
        }

        $tecnico->solicitudes()->detach($solicitud->id);
    }

    public function obtenerRegistros(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_solicitud' => 'nullable|integer',
            'numero_documento_identificacion' => 'nullable|integer',
            'nombre' => 'nullable|max:255',
            'direccion' => 'nullable',
        ]);

        $validator->after(function($validator) use ($request) {
            if ( empty($request->numero_solicitud) && empty($request->numero_documento_identificacion)
                && empty($request->nombre) && empty($request->direccion) ) {
                    $validator->errors()->add('atleast_one', 'Debes proporcionar al menos un campo de búsqueda.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tecnicoID = $request->tecnicoID;
        $numSolicitud = $request->numero_solicitud;
        $numDocIdentidad = $request->numero_documento_identificacion;
        $nombre = strtolower($request->nombre);
        $direccion = strtolower($request->direccion);

        $query = Solicitante::query();

        if (!empty($numSolicitud)) {
            $query->whereHas('solicitudes', function ($q) use ($numSolicitud) {
                $q->where('numero_solicitud', 'like' ,"%$numSolicitud%");
            });
        }

        if ($numDocIdentidad) {
            $query->where('numero_documento_identificacion', 'like' ,"%$numDocIdentidad%");
        }

        // if ($nombre) {
        //     $query->where('nombre', 'like' , "%$nombre%");
        // }
        if ($nombre) {
            $query->whereRaw('LOWER(nombre) LIKE ?', ["%$nombre%"]);
        }

        // if ($direccion) {
        //     $query->whereHas('ubicaciones', function($q) use ($direccion) {
        //         $q->where('direccion', 'like' ,"%$direccion%");
        //     });
        // }

        if ($direccion) {
            $query->whereHas('ubicaciones', function($q) use ($direccion) {
                $q->whereRaw('LOWER(direccion) LIKE ?', ["%$direccion%"]);
            });
        }

        $registros = $query->paginate(10);
        $registros->appends($request->all());

        return view('company.pages.solicitudesTecnico.respuestas', compact('registros' , 'tecnicoID', 'request'));

    }
}
