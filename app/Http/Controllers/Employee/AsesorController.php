<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Asesor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AsesorController extends Controller
{
    public function index()
    {
        $asesores = Asesor::orderBy('id', 'desc')->paginate(10);

        return view('employee.pages.asesores.index', compact('asesores'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $asesorId = $request->id;

        $rules = [
            'nombre' => 'required|string|max:255',
            'tipo_documento' => 'required|string|max:255',
            'numero_documento_identificacion' => 'required|integer|between:0,999999999|unique:asesores,numero_documento_identificacion' . ($asesorId ? ",$asesorId" : ''),
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:asesores,email' . ($asesorId ? ",$asesorId" : ''),
            'direccion' => 'nullable|string|max:255',
            'fecha_contratacion' => 'nullable|date',
            'comision' => 'nullable|numeric|between:0,100',
            'estado' => 'nullable|in:activo,inactivo',
            'observaciones' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'nombre',
            'tipo_documento',
            'numero_documento_identificacion',
            'telefono',
            'email',
            'direccion',
            'fecha_contratacion',
            'comision',
            'estado',
            'observaciones',
        ]);

        if ($asesorId) {
            $asesor = Asesor::findOrFail($asesorId);
            $asesor->update($data);
            $message = 'Asesor actualizado exitosamente';
        } else {
            $data['company_id'] = auth()->user()->company_id ?? 1;
            $asesor = Asesor::create($data);
            $message = 'Asesor registrado exitosamente';
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.advisers.index'),
        ]);
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $asesor = Asesor::findOrFail($id);
        return response()->json(['asesor' => $asesor]);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        $asesor = Asesor::findOrFail($id);
        $asesor->delete();

        return redirect()->route('employee.advisers.index');
    }
}
