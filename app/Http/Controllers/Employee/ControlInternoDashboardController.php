<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\ControlInternoDashboard;
use Illuminate\Http\Request;

/**
 * CI-10: dashboard / resumen ejecutivo — equivalente al bloque RESUMEN +
 * BITÁCORA DE LA ÚLTIMA CARGA de la hoja INICIO del Excel original.
 */
class ControlInternoDashboardController extends Controller
{
    public function index(Request $request)
    {
        $anio = (int) $request->query('anio', now()->year);
        $trimestre = (int) $request->query('trimestre', ceil(now()->month / 3));
        $trimestre = in_array($trimestre, [1, 2, 3, 4], true) ? $trimestre : (int) ceil(now()->month / 3);

        return view('employee.pages.control-interno.resumen', [
            'resumen' => ControlInternoDashboard::resumen($anio, $trimestre),
            'anioActual' => $anio,
            'trimestreActual' => $trimestre,
        ]);
    }
}
