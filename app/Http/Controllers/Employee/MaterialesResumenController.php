<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Cuadrilla;
use App\Services\ControlMaterialesResumen;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * CM-8: RESUMEN / Panel de control — indicadores generales (hoja RESUMEN)
 * + corte por cuadrilla con PDF (macro GenerarResumenPDF, hoja oculta
 * RESUMEN CUADRILLA). Ver App\Services\ControlMaterialesResumen para el
 * detalle de cada fórmula, traducida de las fuentes originales.
 */
class MaterialesResumenController extends Controller
{
    public function index(Request $request)
    {
        $generales = ControlMaterialesResumen::generales();
        $cuadrillas = ControlMaterialesResumen::registroCuadrillas();
        $hastaDefault = now()->toDateString();

        return view('employee.pages.materiales.resumen.index', compact('generales', 'cuadrillas', 'hastaDefault'));
    }

    public function corte(Request $request, Cuadrilla $cuadrilla)
    {
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();
        $desde = $request->query('desde') ? Carbon::parse($request->query('desde')) : null;
        $corte = ControlMaterialesResumen::corteCuadrilla($cuadrilla, $hasta);

        return view('employee.pages.materiales.resumen.corte', compact('corte', 'desde'));
    }

    public function cerrar(Request $request, Cuadrilla $cuadrilla)
    {
        if ($cuadrilla->tipo !== Cuadrilla::TIPO_CONTRATISTA) {
            abort(422, 'Solo se cierra corte de contratistas: los vales de personal directo no se descuentan en valorización.');
        }

        $data = $request->validate([
            'hasta' => 'required|date',
            'desde' => 'nullable|date',
            'n_valorizacion' => 'required|string|max:60',
        ]);

        $hasta = Carbon::parse($data['hasta'])->endOfDay();
        $desde = $data['desde'] ?? null ? Carbon::parse($data['desde']) : null;

        $marcadas = ControlMaterialesResumen::cerrarCorteContratista($cuadrilla, $hasta, $data['n_valorizacion'], $desde);

        session()->flash('message', $marcadas . ' cotización(es) de ' . $cuadrilla->nombre . ' marcadas como descontadas en ' . $data['n_valorizacion'] . '.');

        return redirect()->route('employee.materiales.resumen.index');
    }

    public function pdf(Request $request, Cuadrilla $cuadrilla)
    {
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();
        $desde = $request->query('desde') ? Carbon::parse($request->query('desde')) : null;

        $corte = ControlMaterialesResumen::corteCuadrilla($cuadrilla, $hasta);

        $pdf = Pdf::loadView('employee.pages.materiales.resumen.pdf', compact('corte', 'desde'))->setPaper('a4');

        $nombreArchivo = 'RESUMEN ' . $cuadrilla->nombre . ' - hasta ' . $hasta->format('d-m-Y') . '.pdf';

        return $pdf->download($nombreArchivo);
    }
}
