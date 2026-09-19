<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Cuadrilla;
use App\Models\Ejecutado;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CM-6: Registro rápido + Ejecutado. Exclusivo de PERSONAL DIRECTO — los
 * contratistas no pasan por acá (ver CM-4).
 *
 * Formulario de página completa con lista dinámica de ítems, mismo patrón
 * que CotizacionController (no crudModal). A diferencia de la cotización,
 * acá no hay "corregir": si algo se reportó mal, se elimina (soft delete)
 * y se vuelve a registrar — no es un documento emitido con número.
 */
class EjecutadoController extends Controller
{
    public function index(Request $request)
    {
        $cuadrillaId = $request->query('cuadrilla_id');
        $materialId = $request->query('material_id');
        $movimiento = $request->query('movimiento');

        $ejecutados = Ejecutado::with(['cuadrilla', 'material'])
            ->when($cuadrillaId, fn ($query) => $query->where('cuadrilla_id', $cuadrillaId))
            ->when($materialId, fn ($query) => $query->where('material_id', $materialId))
            ->when($movimiento, fn ($query) => $query->where('movimiento', $movimiento))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('employee.pages.materiales.ejecutados.index', [
            'ejecutados' => $ejecutados,
            'cuadrillas' => Cuadrilla::where('tipo', Cuadrilla::TIPO_PERSONAL_DIRECTO)->orderBy('nombre')->get(),
            'materiales' => Material::orderBy('codigo')->get(),
            'cuadrillaId' => $cuadrillaId,
            'materialId' => $materialId,
            'movimiento' => $movimiento,
        ]);
    }

    public function create(Request $request)
    {
        $cuadrillas = Cuadrilla::where('tipo', Cuadrilla::TIPO_PERSONAL_DIRECTO)
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $cuadrillaId = $request->query('cuadrilla_id');
        $cuadrilla = $cuadrillaId ? $cuadrillas->firstWhere('id', (int) $cuadrillaId) : null;

        return view('employee.pages.materiales.ejecutados.form', [
            'cuadrillas' => $cuadrillas,
            'cuadrillaSeleccionada' => $cuadrilla,
            'materiales' => Material::orderBy('codigo')->get(),
            'saldos' => $cuadrilla ? $cuadrilla->saldosEnPoder() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => 'required|date',
            'cuadrilla_id' => 'required|exists:cuadrillas,id',
            'tipo_trabajo' => 'nullable|string|max:60',
            'n_suministro' => 'nullable|string|max:40',
            'movimiento' => ['required', Rule::in([Ejecutado::MOVIMIENTO_SALIDA, Ejecutado::MOVIMIENTO_DEVOLUCION])],
            'items' => 'required|array|min:1',
            'items.*.material_id' => ['required', Rule::exists('materiales', 'id')],
            'items.*.cantidad' => 'required|numeric|min:0.01',
        ], [
            'items.required' => 'Agrega al menos una cantidad.',
            'items.min' => 'Agrega al menos una cantidad.',
        ]);

        $cuadrilla = Cuadrilla::findOrFail($data['cuadrilla_id']);

        if ($cuadrilla->tipo !== Cuadrilla::TIPO_PERSONAL_DIRECTO) {
            return back()->withErrors([
                'cuadrilla_id' => $cuadrilla->nombre . ' es CONTRATISTA. Esta pantalla es solo para PERSONAL DIRECTO (los contratistas descuentan su material por cotización, ver CM-4).',
            ])->withInput();
        }

        if ($data['movimiento'] === Ejecutado::MOVIMIENTO_SALIDA && empty($data['tipo_trabajo'])) {
            return back()->withErrors([
                'tipo_trabajo' => 'Elige el tipo de trabajo (obligatorio para una SALIDA).',
            ])->withInput();
        }

        foreach ($data['items'] as $item) {
            Ejecutado::create([
                'fecha' => $data['fecha'],
                'tipo_trabajo' => $data['tipo_trabajo'] ?? null,
                'cuadrilla_id' => $cuadrilla->id,
                'material_id' => $item['material_id'],
                'cantidad' => $item['cantidad'],
                'n_suministro' => $data['n_suministro'] ?? null,
                'movimiento' => $data['movimiento'],
            ]);
        }

        session()->flash('message', count($data['items']) . ' ítem(s) registrados en EJECUTADO para ' . $cuadrilla->nombre . '.');

        return redirect()->route('employee.materiales.ejecutados.index');
    }

    public function destroy(Ejecutado $ejecutado)
    {
        $ejecutado->delete();

        session()->flash('message', 'Registro eliminado (queda en la papelera, no se pierde el dato).');

        return redirect()->route('employee.materiales.ejecutados.index');
    }
}
