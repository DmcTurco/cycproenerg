<?php

namespace App\Http\Controllers\Company;

use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\SolicitanteTecnico;
use App\Models\Tecnico;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SolicitanteTecnicoController extends Controller
{
    public function index($tecnicoID) {

        $tecnico = Tecnico::findOrFail($tecnicoID);
        if ( $tecnico->company_id != Auth::user()->id) {
            abort(403);
        }
        $solicitudes = SolicitanteTecnico::where('tecnico_id', $tecnico->id)->orderBy('id', 'desc')->paginate(10);

        return view('company.pages.solicitudesTecnico.index', compact('solicitudes', 'tecnico'));
    }

    public function store($tecnicoID, Request $request) {

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

        // $tecnico = Tecnico::findOrFail($tecnicoID);
        // $solicitud = SolicitanteTecnico::findOrFail($solicitudID);
        // if ($tecnico->company_id != Auth::user()->id  && $solicitud->tecnico_id != $tecnico->id) {
        //     abort(403);
        // }

        // $solicitud->delete();
        // return redirect()->route('company.technicals.requests.index', $tecnicoID);

    }

    public function obtenerRegistros(Request $request) {
        
        $numeroSolicitud = $request->numero_solicitud;
        $nombre = $request->nombre;
        $direccion = $request->direccion;
        $numeroDocumento = $request->numero_documento_identificacion;

        $solicitante =  Solicitante::where('numero_documento_identificacion', $numeroDocumento)->get();



    }
}
