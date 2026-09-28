<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CierreMaterial;
use App\Services\ControlMaterialesCierre;
use App\Services\ControlMaterialesResumen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CM-11: Cierre de mes — pantalla y acción de
 * App\Services\ControlMaterialesCierre. Ver ese servicio y la migración
 * create_cierres_materiales_table para el detalle completo.
 */
class CierreMaterialesController extends Controller
{
    public function index()
    {
        return view('employee.pages.materiales.cierres.index', [
            'cierres' => CierreMaterial::orderByDesc('fecha_cierre')->paginate(12),
            'generales' => ControlMaterialesResumen::generales(),
            'ultimoCierre' => CierreMaterial::orderByDesc('fecha_cierre')->first(),
            'etiquetaSugerida' => now()->format('Y-m'),
            'fechaSugerida' => now()->toDateString(),
        ]);
    }

    public function show(CierreMaterial $cierre)
    {
        return view('employee.pages.materiales.cierres.show', [
            'cierre' => $cierre,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'etiqueta' => 'required|string|max:20|unique:cierres_materiales,etiqueta',
            'fecha_cierre' => 'required|date',
            'confirmacion' => 'required|accepted',
        ], [
            'etiqueta.unique' => 'Ya existe un cierre con esa etiqueta.',
            'confirmacion.accepted' => 'Confirma la casilla para poder cerrar el mes.',
        ]);

        $fechaCierre = Carbon::parse($data['fecha_cierre'])->endOfDay();

        $ultimoCierre = CierreMaterial::orderByDesc('fecha_cierre')->first();
        if ($ultimoCierre && $fechaCierre->lte($ultimoCierre->fecha_cierre)) {
            return back()->withErrors([
                'fecha_cierre' => 'La fecha de corte debe ser posterior al último cierre (' . $ultimoCierre->fecha_cierre->format('d/m/Y') . ', etiqueta ' . $ultimoCierre->etiqueta . ').',
            ])->withInput();
        }

        $cierre = ControlMaterialesCierre::cerrar($data['etiqueta'], $fechaCierre, Auth::id());

        session()->flash('message', 'Mes "' . $cierre->etiqueta . '" cerrado. El stock quedó consolidado y los movimientos hasta el ' . $fechaCierre->format('d/m/Y') . ' quedaron archivados en el historial de cierres.');

        return redirect()->route('employee.materiales.cierres.show', $cierre);
    }
}
