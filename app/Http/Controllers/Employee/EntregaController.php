<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Cuadrilla;
use App\Models\Entrega;
use App\Models\Herramienta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CM-7: entrega/devolución de herramientas (hoja ENTREGAS). Sin macro
 * dedicada en el Excel — CRUD simple con crudModal, mismo patrón que
 * CM-1/CM-2/CM-3 (a diferencia de CM-4/CM-6, que tienen listas dinámicas
 * de ítems y necesitan un formulario de página completa).
 */
class EntregaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $herramientaId = $request->query('herramienta_id');
        $cuadrillaId = $request->query('cuadrilla_id');

        $entregas = Entrega::with(['herramienta', 'cuadrilla'])
            ->when($herramientaId, fn ($query) => $query->where('herramienta_id', $herramientaId))
            ->when($cuadrillaId, fn ($query) => $query->where('cuadrilla_id', $cuadrillaId))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('herramienta', function ($hq) use ($search) {
                        $hq->where('codigo', 'ilike', "%{$search}%")
                            ->orWhere('descripcion', 'ilike', "%{$search}%");
                    })->orWhereHas('cuadrilla', function ($cq) use ($search) {
                        $cq->where('nombre', 'ilike', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $herramientas = Herramienta::orderBy('codigo')->get();
        $cuadrillas = Cuadrilla::where('estado', 'ACTIVO')->orderBy('nombre')->get();

        return view('employee.pages.materiales.entregas', compact(
            'entregas', 'herramientas', 'cuadrillas', 'search', 'herramientaId', 'cuadrillaId'
        ));
    }

    public function store(Request $request)
    {
        $entregaId = $request->id;

        $rules = [
            'fecha' => 'required|date',
            'herramienta_id' => 'required|exists:herramientas,id',
            'tipo' => 'required|string|in:ENTREGA,DEVOLUCION',
            'cuadrilla_id' => 'required|exists:cuadrillas,id',
            'observacion' => 'nullable|string|max:1000',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['fecha', 'herramienta_id', 'tipo', 'cuadrilla_id', 'observacion']);

        if ($entregaId) {
            $entrega = Entrega::findOrFail($entregaId);
            $entrega->update($data);
            $message = 'Entrega actualizada.';
        } else {
            Entrega::create($data);
            $message = 'Movimiento registrado.';
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.materiales.entregas.index'),
        ]);
    }

    public function edit(Entrega $entrega)
    {
        return response()->json([
            'entrega' => [
                'id' => $entrega->id,
                'fecha' => optional($entrega->fecha)->format('Y-m-d'),
                'herramienta_id' => $entrega->herramienta_id,
                'tipo' => $entrega->tipo,
                'cuadrilla_id' => $entrega->cuadrilla_id,
                'observacion' => $entrega->observacion,
            ],
        ]);
    }

    public function destroy(Entrega $entrega)
    {
        $entrega->delete();

        session()->flash('message', 'Entrega eliminada.');
        return redirect()->route('employee.materiales.entregas.index');
    }
}
