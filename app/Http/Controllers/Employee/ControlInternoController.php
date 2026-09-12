<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FaseControlInterno;
use App\Models\Solicitud;
use App\Services\ControlInternoIndicadores;
use Illuminate\Http\Request;

/**
 * CI-9: listado de Control Interno — equivalente a las hojas GENERAL /
 * CONSTRUIDO / TC / PEND_ANULACION del Excel, pero en una sola pantalla con
 * filtro por fase, en vez de una hoja por fase.
 */
class ControlInternoController extends Controller
{
    public function index(Request $request)
    {
        $fase = $request->query('fase');
        if (!in_array($fase, FaseControlInterno::FASES, true)) {
            $fase = null;
        }

        $solicitudes = Solicitud::query()
            ->with(['faseControlInterno', 'instalacion', 'empresa', 'proyecto'])
            ->when($fase, function ($query) use ($fase) {
                $query->whereHas('faseControlInterno', fn ($q) => $q->where('fase', $fase));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('numero_solicitud', 'ilike', '%' . $request->query('search') . '%');
            })
            ->orderByDesc('numero_solicitud')
            ->paginate(20)
            ->appends($request->query());

        $solicitudes->getCollection()->transform(function (Solicitud $solicitud) {
            $solicitud->ci_indicadores = ControlInternoIndicadores::para($solicitud);

            return $solicitud;
        });

        return view('employee.pages.control-interno.index', [
            'solicitudes' => $solicitudes,
            'fases' => FaseControlInterno::FASES,
            'faseActual' => $fase,
        ]);
    }
}
