<?php

namespace App\Http\Controllers\Employee;

use App\Helpers\TipoDocumentoHelper;
use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $tecnicoId = $request->id;

        $rules = [
            'nombre' => 'required|string|max:255',
            'tipo_documento' => 'required|integer|between:0,3',
            'numero_documento_identificacion' => 'required|integer|between:0,999999999|unique:tecnicos,numero_documento_identificacion' . ($tecnicoId ? ",$tecnicoId" : ''),
            'cargo' => 'required|integer|between:0,2',
            'email' => 'required|email|unique:tecnicos,email' . ($tecnicoId ? ",$tecnicoId" : ''),
            'password' => $tecnicoId ? 'nullable|min:8' : 'required|min:8'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['nombre', 'tipo_documento', 'numero_documento_identificacion', 'cargo', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($tecnicoId) {
            $tecnico = Tecnico::findOrFail($tecnicoId);
            $tecnico->update($data);
            $message = 'Técnico actualizado exitosamente';
        } else {
            $data['empresa_id'] = auth()->user()->empresa_id ?? 1;
            $tecnico = Tecnico::create($data);
            $message = 'Técnico registrado exitosamente';
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.technicals.index')
        ]);
    }
    public function edit($id)
    {
        $tecnico = Tecnico::findOrFail($id);
        return response()->json(['tecnico' => $tecnico]);
    }

    public function destroy($id)
    {

        $tecnico = Tecnico::findOrFail($id);

        $tecnico->delete();
        return redirect()->route('employee.technicals.index');
    }
}
