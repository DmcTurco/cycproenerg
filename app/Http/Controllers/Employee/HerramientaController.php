<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Herramienta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CM-1: CRUD del catálogo de herramientas (hoja CATALOGO, bloque de
 * herramientas). El código HER-### se autogenera al crear, nunca se edita.
 */
class HerramientaController extends Controller
{
    public function index()
    {
        return response()->json(['herramientas' => Herramienta::orderBy('codigo')->get()]);
    }

    public function store(Request $request)
    {
        $herramientaId = $request->id;

        $rules = [
            'codigo' => 'required|string|max:20|unique:herramientas,codigo' . ($herramientaId ? ",$herramientaId" : ''),
            'descripcion' => 'required|string|max:255',
            'marca_modelo' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:100',
            'fecha_compra' => 'nullable|date',
            'precio' => 'nullable|numeric|min:0',
            'estado' => 'required|string|in:OPERATIVA,MALOGRADA,PERDIDA',
            'observacion' => 'nullable|string|max:1000',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['codigo', 'descripcion', 'marca_modelo', 'numero_serie', 'fecha_compra', 'precio', 'estado', 'observacion']);

        if ($herramientaId) {
            $herramienta = Herramienta::findOrFail($herramientaId);
            $herramienta->update($data);
            $message = 'Herramienta actualizada.';
        } else {
            // El correlativo nunca lo escribe el staff: se autogenera, igual
            // que la fórmula "HER-"&TEXT(fila,"000") del Excel.
            $data['serie'] = Herramienta::SERIE;
            $data['correlativo'] = Herramienta::siguienteCorrelativo();
            $herramienta = Herramienta::create($data);
            $message = 'Herramienta registrada: ' . $herramienta->serie . '-' . $herramienta->correlativo;
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.materiales.index', ['tab' => 'herramientas']),
        ]);
    }

    public function edit(Herramienta $herramienta)
    {
        return response()->json([
            'herramienta' => [
                'id' => $herramienta->id,
                'codigo' => $herramienta->codigo,
                'serie' => $herramienta->serie,
                'correlativo' => $herramienta->correlativo,
                'descripcion' => $herramienta->descripcion,
                'marca_modelo' => $herramienta->marca_modelo,
                'numero_serie' => $herramienta->numero_serie,
                'fecha_compra' => optional($herramienta->fecha_compra)->format('Y-m-d'),
                'precio' => $herramienta->precio,
                'estado' => $herramienta->estado,
                'observacion' => $herramienta->observacion,
            ],
        ]);
    }

    public function destroy(Herramienta $herramienta)
    {
        $herramienta->delete();

        session()->flash('message', 'Herramienta eliminada.');
        return redirect()->route('employee.materiales.index', ['tab' => 'herramientas']);
    }
}
