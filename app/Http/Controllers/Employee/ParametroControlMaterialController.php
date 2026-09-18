<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ParametroControlMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CM-1: pantalla de configuración de Control de Materiales — IGV, margen
 * general sobre compra y umbral de alarma de precio. Equivalente al bloque
 * "PARAMETROS GENERALES" de la hoja INICIO del Excel.
 */
class ParametroControlMaterialController extends Controller
{
    public function edit()
    {
        $parametros = ParametroControlMaterial::actual();

        return view('employee.pages.materiales.parametros', compact('parametros'));
    }

    public function update(Request $request)
    {
        $rules = [
            'igv' => 'required|numeric|min:0|max:100',
            'margen_general' => 'required|numeric|min:0|max:100',
            'umbral_alarma_precio' => 'required|numeric|min:0|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $parametros = ParametroControlMaterial::actual();
        // El formulario trabaja en porcentaje (0-100); en base de datos se
        // guarda como fracción (0-1), igual que el resto del sistema.
        $parametros->update([
            'igv' => round($request->igv / 100, 4),
            'margen_general' => round($request->margen_general / 100, 4),
            'umbral_alarma_precio' => round($request->umbral_alarma_precio / 100, 4),
        ]);

        session()->flash('message', 'Parámetros de Control de Materiales actualizados.');
        return redirect()->route('employee.materiales.parametros.edit');
    }
}
