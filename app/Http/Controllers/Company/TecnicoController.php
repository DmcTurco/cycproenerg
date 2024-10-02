<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TecnicoController extends Controller
{

    public function index() {
        
        $tecnicos = Tecnico::where('company_id', Auth::user()->id)->orderBy('id', 'desc')->paginate(10);
        $cargos = ['Supervisor', 'empleado'];
        return view('company.pages.tecnicos.index', compact('tecnicos', 'cargos') );
    }
    
    public function store(Request $request)
    {

        $tecnicoId = $request->tecnicoId;

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|max:255', 
            'dni' => 'required',
            'cargo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if (!empty($tecnicoId)) {
            
            $tecnico = Tecnico::find($tecnicoId);

            $tecnico->update([
                'nombre' => $request->nombre,
                'dni' => $request->dni,
                'cargo' => $request->cargo,
            ]);
            session()->flash('message', __('Actualización éxitosa') );
            return response()->json(['redirect' => route('company.technicals.index')]);

        } else {

            $tecnico = Tecnico::create([
                'company_id' => Auth::user()->id,
                'nombre' => $request->nombre,
                'dni' => $request->dni,
                'cargo' => $request->cargo,
            ]);

            session()->flash('message', __('Registro éxitoso') );
            return response()->json(['redirect' => route('company.technicals.index')]);
        }
    }

    public function edit($id) {
        $tecnico = Tecnico::findOrFail($id);
        return response()->json( ['tecnico' => $tecnico]);
    }

    public function destroy($id) {

        $tecnico = Tecnico::findOrFail($id);

        $tecnico->delete();
        return redirect()->route('company.technicals.index');
    }


}
