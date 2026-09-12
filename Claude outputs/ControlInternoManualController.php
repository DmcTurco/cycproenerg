<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FaseControlInterno;
use App\Models\Solicitud;
use Illuminate\Http\Request;

/**
 * CI-3: columnas "amarillas" del Excel (F. CONSTRUCCIÓN control, OBSERVACIÓN,
 * ANULAR) — las llena el staff a mano desde el detalle de la solicitud, nunca
 * la carga del portal (ProcessExcelJob).
 */
class ControlInternoManualController extends Controller
{
    public function show(Solicitud $solicitud)
    {
        $fase = $solicitud->faseControlInterno;

        return response()->json([
            'fase' => $fase->fase ?? FaseControlInterno::GENERAL,
            'fecha_ingreso_general' => optional($fase?->fecha_ingreso_general)->format('d/m/Y'),
            'fecha_construccion_control' => optional($fase?->fecha_construccion_control)->format('Y-m-d'),
            'observacion_control' => $fase->observacion_control ?? '',
            'marcado_para_anular' => (bool) ($fase->marcado_para_anular ?? false),
        ]);
    }

    public function update(Request $request, Solicitud $solicitud)
    {
        $data = $request->validate([
            'fecha_construccion_control' => 'nullable|date',
            'observacion_control' => 'nullable|string|max:1000',
            'marcado_para_anular' => 'boolean',
        ]);

        // La solicitud debería tener siempre su fila de fase (ProcessExcelJob
        // la crea en GENERAL al importar), pero por si es una solicitud
        // anterior a CI-2, la creamos aquí en vez de fallar.
        $fase = FaseControlInterno::firstOrCreate(
            ['solicitud_id' => $solicitud->id],
            ['fase' => FaseControlInterno::GENERAL, 'fecha_ingreso_general' => now()->toDateString()]
        );

        $fase->update($data);

        return response()->json(['success' => true, 'message' => 'Control Interno actualizado.']);
    }
}
