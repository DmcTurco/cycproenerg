<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\Cuadrilla;
use App\Models\Material;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CM-4/CM-5: Cotización (contratistas) / Vale de entrega (personal
 * directo) + su registro — equivalente a las hojas COTIZACION +
 * COTIZACIONES del Excel (Turco pidió hacerlas juntas).
 *
 * A diferencia de CM-1/CM-2/CM-3 (formularios de un solo registro, con el
 * crudModal genérico), acá el formulario tiene una lista dinámica de
 * ítems (como la hoja COTIZACION, filas 9-28) — es una pantalla completa
 * propia (cotizacion-form.blade.php), no un modal.
 */
class CotizacionController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->query('estado');
        $cuadrillaId = $request->query('cuadrilla_id');
        $search = trim((string) $request->query('search'));

        $cotizaciones = Cotizacion::with('cuadrilla')
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($cuadrillaId, fn ($query) => $query->where('cuadrilla_id', $cuadrillaId))
            ->when($search, fn ($query) => $query->where('numero', 'ilike', "%{$search}%"))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $cuadrillas = Cuadrilla::orderBy('nombre')->get();

        return view('employee.pages.materiales.cotizaciones.index', compact('cotizaciones', 'cuadrillas', 'estado', 'cuadrillaId', 'search'));
    }

    public function create()
    {
        $cotizacion = null;
        $cuadrillas = Cuadrilla::where('estado', 'ACTIVO')->orderBy('nombre')->get();
        $materiales = Material::orderBy('codigo')->get();
        $initialItems = [];

        return view('employee.pages.materiales.cotizaciones.form', compact('cotizacion', 'cuadrillas', 'materiales', 'initialItems'));
    }

    public function store(Request $request)
    {
        $data = $this->validarFormulario($request);

        $cuadrilla = Cuadrilla::findOrFail($data['cuadrilla_id']);
        $cotizacion = Cotizacion::emitir($cuadrilla, $data['items'], $data['fecha']);

        session()->flash('message', ($cotizacion->es_vale ? 'Vale generado: ' : 'Cotización generada: ') . $cotizacion->numero);
        session()->flash('pdfUrl', route('employee.materiales.cotizaciones.pdf', $cotizacion));

        return redirect()->route('employee.materiales.cotizaciones.index');
    }

    public function edit(Cotizacion $cotizacion)
    {
        $cotizacion->load('detalles');

        $cuadrillas = Cuadrilla::where('estado', 'ACTIVO')->orWhere('id', $cotizacion->cuadrilla_id)->orderBy('nombre')->get();
        $materiales = Material::orderBy('codigo')->get();
        $initialItems = $cotizacion->detalles->map(fn ($detalle) => [
            'material_id' => $detalle->material_id,
            'cantidad' => $detalle->cantidad,
        ])->values();

        return view('employee.pages.materiales.cotizaciones.form', compact('cotizacion', 'cuadrillas', 'materiales', 'initialItems'));
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        $data = $this->validarFormulario($request);

        $cuadrilla = Cuadrilla::findOrFail($data['cuadrilla_id']);
        Cotizacion::emitir($cuadrilla, $data['items'], $data['fecha'], $cotizacion);

        session()->flash('message', 'Documento ' . $cotizacion->numero . ' corregido (mismo número, no se creó uno nuevo).');
        session()->flash('pdfUrl', route('employee.materiales.cotizaciones.pdf', $cotizacion));

        return redirect()->route('employee.materiales.cotizaciones.index');
    }

    /**
     * Marca una cotización a CONTRATISTA como descontada en una
     * valorización real (columna ESTADO + N° VALORIZACION de
     * COTIZACIONES). Los vales de personal directo nunca pasan por acá.
     */
    public function marcarDescontado(Request $request, Cotizacion $cotizacion)
    {
        if ($cotizacion->es_vale) {
            abort(422, 'Los vales de personal directo no se descuentan en valorización.');
        }

        $request->validate([
            'n_valorizacion' => 'required|string|max:60',
        ]);

        $cotizacion->update([
            'estado' => Cotizacion::ESTADO_DESCONTADO,
            'n_valorizacion' => $request->n_valorizacion,
        ]);

        session()->flash('message', $cotizacion->numero . ' marcada como descontada en valorización.');

        return redirect()->route('employee.materiales.cotizaciones.index');
    }

    public function destroy(Cotizacion $cotizacion)
    {
        $cotizacion->delete();

        session()->flash('message', $cotizacion->numero . ' eliminada (queda en la papelera, no se pierde el dato).');

        return redirect()->route('employee.materiales.cotizaciones.index');
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load(['detalles.material', 'cuadrilla']);
        $parametros = \App\Models\ParametroControlMaterial::actual();

        $pdf = Pdf::loadView('employee.pages.materiales.cotizaciones.pdf', compact('cotizacion', 'parametros'))->setPaper('a4');

        return $pdf->download($cotizacion->numero . '.pdf');
    }

    private function validarFormulario(Request $request): array
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'cuadrilla_id' => 'required|exists:cuadrillas,id',
            'items' => 'required|array|min:1',
            'items.*.material_id' => ['required', Rule::exists('materiales', 'id')],
            'items.*.cantidad' => 'required|integer|min:1',
        ], [
            'items.required' => 'Agrega al menos un ítem con material y cantidad.',
            'items.min' => 'Agrega al menos un ítem con material y cantidad.',
        ]);

        return $validated;
    }
}
