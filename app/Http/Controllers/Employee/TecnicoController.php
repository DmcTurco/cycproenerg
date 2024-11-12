<?php

namespace App\Http\Controllers\Employee;

use App\Helpers\TipoDocumentoHelper;
use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TecnicoController extends Controller
{

    public function index() 
    { 
        $tecnicos = Tecnico::withCount('solicitudes')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->through(function ($tecnico) {
                $tecnico->tipo_documento_nombre = TipoDocumentoHelper::getTypeDocumentName($tecnico->tipo_documento);
                $tecnico->tipo_cargo_name = TipoDocumentoHelper::getTypeCargoName($tecnico->cargo);
                return $tecnico;
            });
        
        return view('employee.pages.tecnicos.index', compact('tecnicos'));
    }

    public function store(Request $request)
    {

        $tecnicoId = $request->tecnicoId;

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|max:255',
            'tipo_documento' => 'required|integer|min:0|max:3',
            'numero_documento_identificacion' => 'required|integer|min:0|max:999999999|unique:tecnicos',
            'cargo' => 'required|integer|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if (!empty($tecnicoId)) {

            $tecnico = Tecnico::find($tecnicoId);

            $tecnico->update([
                'nombre' => $request->nombre,
                'tipo_documento' => $request->tipo_documento,
                'numero_documento_identificacion' => $request->numero_documento_identificacion,
                'cargo' => $request->cargo,
            ]);
            session()->flash('message', __('Actualización éxitosa') );
            return response()->json(['redirect' => route('employee.technicals.index')]);

        } else {

            $tecnico = Tecnico::create([
                'empresa_id' => 1,
                'nombre' => $request->nombre,
                'tipo_documento' => $request->tipo_documento,
                'numero_documento_identificacion' => $request->numero_documento_identificacion,
                'cargo' => $request->cargo,
            ]);

            session()->flash('message', __('Registro éxitoso') );
            return response()->json(['redirect' => route('employee.technicals.index')]);
        }
    }

    public function edit($id) {
        $tecnico = Tecnico::findOrFail($id);
        return response()->json( ['tecnico' => $tecnico]);
    }

    public function destroy($id) {

        $tecnico = Tecnico::findOrFail($id);

        $tecnico->delete();
        return redirect()->route('employee.technicals.index');
    }


}
