<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Feriado;
use App\Models\ParametroControlInterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParametroControlInternoController extends Controller
{
    /**
     * Pantalla de configuración de Control Interno: plazos, semáforo, meta
     * IND2, ámbito de departamentos y feriados. Equivalente a la hoja PARAM
     * del Excel, pero editable por el staff sin tocar código ni el archivo.
     */
    public function edit()
    {
        $parametros = ParametroControlInterno::actual();
        $feriados = Feriado::orderBy('fecha')->get();

        return view('employee.pages.control-interno.parametros', compact('parametros', 'feriados'));
    }

    public function update(Request $request)
    {
        $rules = [
            'plazo_construccion_dias_habiles' => 'required|integer|min:1|max:365',
            'semaforo_verde_dias' => 'required|integer|min:1|max:365',
            'semaforo_ambar_dias' => 'required|integer|min:1|max:365|gte:semaforo_verde_dias',
            'espera_tc_verde_dias' => 'required|integer|min:1|max:365',
            'espera_tc_ambar_dias' => 'required|integer|min:1|max:365|gte:espera_tc_verde_dias',
            'meta_ind2' => 'required|numeric|min:0|max:100',
            'ambito_departamentos' => 'required|array|min:1',
            'ambito_departamentos.*' => 'string|max:50',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'semaforo_ambar_dias.gte' => 'El ámbar debe ser mayor o igual que el verde.',
            'espera_tc_ambar_dias.gte' => 'El ámbar debe ser mayor o igual que el verde.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $parametros = ParametroControlInterno::actual();
        $parametros->update([
            'plazo_construccion_dias_habiles' => $request->plazo_construccion_dias_habiles,
            'semaforo_verde_dias' => $request->semaforo_verde_dias,
            'semaforo_ambar_dias' => $request->semaforo_ambar_dias,
            'espera_tc_verde_dias' => $request->espera_tc_verde_dias,
            'espera_tc_ambar_dias' => $request->espera_tc_ambar_dias,
            // El formulario trabaja en porcentaje (0-100); en base de datos
            // se guarda como fracción (0-1), igual que PARAM!C14 lo usan las
            // fórmulas del Excel.
            'meta_ind2' => round($request->meta_ind2 / 100, 4),
            'ambito_departamentos' => array_map('strtoupper', $request->ambito_departamentos),
        ]);

        session()->flash('message', 'Parámetros de Control Interno actualizados.');
        return redirect()->route('employee.control-interno.parametros.edit');
    }
}
