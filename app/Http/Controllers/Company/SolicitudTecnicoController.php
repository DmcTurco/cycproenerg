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
        $solicitudes = $tecnico->solicitudes()->paginate(10); 
        return view('company.pages.solicitudesTecnico.index', compact('solicitudes', 'tecnico'));
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

        $tecnico->solicitudes()->attach($solicitud->id, ['categoria' => $categoria]);

        return redirect()->route('company.technicals.requests.index', $tecnico->id);








        // $tecnico = Tecnico::findOrFail($tecnicoID);

        // if (Auth::user()->id != $tecnico->company_id) {
        //     abort(403);
        // }

        // $solicitudID = $request->solicitudID;

        // $validator = Validator::make($request->all(), [
        //     'numero_solicitud' => 'required|integer',
        //     'numero_documento' => 'required|max:50',
        //     'tipo_cliente' => 'required|string',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }


        // if (!empty($solicitudID)) {

        //     $solicitud = SolicitanteTecnico::find($solicitudID);

        //     if ($solicitud->tecnico_id != $tecnico->id) {
        //         abort(403);
        //     }

        //     $solicitud->update([
        //         'numero_solicitud' => $request->numero_solicitud,
        //         'numero_documento' => $request->numero_documento,
        //         'tipo_cliente' => $request->tipo_cliente,
        //     ]);
        //     session()->flash('message', __('Actualización éxitosa') );
        //     return response()->json(['redirect' => route('company.technicals.requests.index', $tecnicoID)]);

        // } else {

        //     $solicitud = SolicitanteTecnico::create([
        //         'tecnico_id' => $tecnico->id,
        //         'numero_solicitud' => $request->numero_solicitud,
        //         'numero_documento' => $request->numero_documento,
        //         'tipo_cliente' => $request->tipo_cliente,
        //     ]);

        //     session()->flash('message', __('Registro éxitoso') );
        //     return response()->json(['redirect' => route('company.technicals.requests.index', $tecnicoID)]);
        // }

    }

    public function edit($tecnicoID, $solicitudID) {

        // $tecnico = Tecnico::findOrFail($tecnicoID);
        // $solicitud = SolicitanteTecnico::findOrFail($solicitudID);
        // if ($tecnico->company_id != Auth::user()->id  && $solicitud->tecnico_id != $tecnico->id) {
        //     abort(403);
        // }

        // return response()->json( ['solicitud' => $solicitud]);

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
        return view('company.pages.solicitudesTecnico.respuestas', compact('registros' , 'tecnicoID'));

    }
}
