<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Cuadrilla;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CM-2: registro de cuadrillas (equivalente a "REGISTRO DE CUADRILLAS"
 * dentro de RESUMEN). El listado se renderiza acá mismo (a diferencia de
 * materiales/herramientas, que viven bajo CatalogoController) porque no
 * tiene pestañas — mismo patrón crudModal que el resto del módulo.
 */
class CuadrillaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        $cuadrillas = Cuadrilla::with('empresas')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'ilike', "%{$search}%")
                        ->orWhere('dni', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        $empresas = Empresa::orderBy('codigo')->get();

        return view('employee.pages.materiales.cuadrillas', compact('cuadrillas', 'empresas', 'search'));
    }

    public function store(Request $request)
    {
        $cuadrillaId = $request->id;

        $rules = [
            'nombre' => 'required|string|max:150',
            'tipo' => 'required|in:' . Cuadrilla::TIPO_CONTRATISTA . ',' . Cuadrilla::TIPO_PERSONAL_DIRECTO,
            'estado' => 'required|in:ACTIVO,INACTIVO',
            'dni' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'celular' => 'nullable|string|max:20',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['nombre', 'tipo', 'estado', 'dni', 'fecha_nacimiento', 'celular']);

        if ($cuadrillaId) {
            $cuadrilla = Cuadrilla::findOrFail($cuadrillaId);
            $cuadrilla->update($data);
            $message = 'Cuadrilla actualizada.';
        } else {
            $cuadrilla = Cuadrilla::create($data);
            $message = 'Cuadrilla registrada.';
        }

        // El checkbox de empresas llega como "empresas" con los ids
        // separados por coma (form.empresas es un array en Alpine; el
        // crudModal genérico serializa cualquier valor con
        // FormData.append(key, value), y un array se convierte solo a
        // "1,2" por el Array.prototype.toString() del navegador — no hace
        // falta tocar resources/js/crud-modal.js).
        $empresaIds = collect(explode(',', (string) $request->input('empresas', '')))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => $id !== '' && is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $cuadrilla->empresas()->sync($empresaIds);

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.materiales.cuadrillas.index'),
        ]);
    }

    public function edit(Cuadrilla $cuadrilla)
    {
        return response()->json([
            'cuadrilla' => [
                'id' => $cuadrilla->id,
                'nombre' => $cuadrilla->nombre,
                'tipo' => $cuadrilla->tipo,
                'estado' => $cuadrilla->estado,
                'dni' => $cuadrilla->dni,
                'fecha_nacimiento' => optional($cuadrilla->fecha_nacimiento)->format('Y-m-d'),
                'celular' => $cuadrilla->celular,
                'empresas' => $cuadrilla->empresas()->pluck('empresas.id')->map(fn ($id) => (string) $id)->all(),
            ],
        ]);
    }

    public function destroy(Cuadrilla $cuadrilla)
    {
        $cuadrilla->delete();

        session()->flash('message', 'Cuadrilla eliminada.');
        return redirect()->route('employee.materiales.cuadrillas.index');
    }
}
