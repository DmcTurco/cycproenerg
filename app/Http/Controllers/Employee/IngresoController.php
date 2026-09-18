<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Ingreso;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CM-3: kardex de entradas (hoja INGRESOS). Listado paginado (a diferencia
 * de CM-1/CM-2, que son catálogos/registros acotados, este es un ledger
 * que crece con el tiempo — mismo patrón de paginate() que Control Interno
 * / Asesores / Técnicos).
 */
class IngresoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $materialId = $request->query('material_id');

        $ingresos = Ingreso::with('material')
            ->when($materialId, fn ($query) => $query->where('material_id', $materialId))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('proveedor', 'ilike', "%{$search}%")
                        ->orWhere('guia_factura', 'ilike', "%{$search}%")
                        ->orWhereHas('material', function ($mq) use ($search) {
                            $mq->where('codigo', 'ilike', "%{$search}%")
                                ->orWhere('descripcion', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $materiales = Material::orderBy('codigo')->get();

        return view('employee.pages.materiales.ingresos', compact('ingresos', 'materiales', 'search', 'materialId'));
    }

    public function store(Request $request)
    {
        $ingresoId = $request->id;

        $rules = [
            'material_id' => 'required|exists:materiales,id',
            'fecha' => 'required|date',
            'cantidad' => 'required|numeric|min:0.01',
            'proveedor' => 'nullable|string|max:255',
            'guia_factura' => 'nullable|string|max:60',
            'precio_compra' => 'nullable|numeric|min:0',
            'observacion' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'material_id', 'fecha', 'cantidad', 'proveedor', 'guia_factura', 'precio_compra', 'observacion',
        ]);

        if ($ingresoId) {
            $ingreso = Ingreso::findOrFail($ingresoId);
            $ingreso->update($data);
            $message = 'Ingreso actualizado.';
        } else {
            Ingreso::create($data);
            $message = 'Ingreso registrado.';
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.materiales.ingresos.index'),
        ]);
    }

    public function edit(Ingreso $ingreso)
    {
        return response()->json([
            'ingreso' => [
                'id' => $ingreso->id,
                'material_id' => $ingreso->material_id,
                'fecha' => optional($ingreso->fecha)->format('Y-m-d'),
                'cantidad' => $ingreso->cantidad,
                'proveedor' => $ingreso->proveedor,
                'guia_factura' => $ingreso->guia_factura,
                'precio_compra' => $ingreso->precio_compra,
                'observacion' => $ingreso->observacion,
            ],
        ]);
    }

    public function destroy(Ingreso $ingreso)
    {
        $ingreso->delete();

        session()->flash('message', 'Ingreso eliminado.');
        return redirect()->route('employee.materiales.ingresos.index');
    }
}
