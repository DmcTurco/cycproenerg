<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ParametroControlGeneral;
use Illuminate\Http\Request;

class ParametroControlGeneralController extends Controller
{
    public function edit()
    {
        return view('employee.pages.control-general.parametros', ['parametros' => ParametroControlGeneral::actual()]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'dias_limite_aprobacion' => 'required|integer|min:1|max:365',
            'ambito_departamentos' => 'required|array|min:1',
            'ambito_departamentos.*' => 'string|in:LIMA,CALLAO',
        ], [
            'ambito_departamentos.required' => 'Elige al menos una provincia.',
        ]);

        ParametroControlGeneral::actual()->update($datos);

        return redirect()->route('employee.control-general.parametros.edit')->with('success', 'Parámetros guardados.');
    }
}
