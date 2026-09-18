<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CM-1: CRUD del catálogo de materiales (hoja CATALOGO, bloque de
 * materiales). El listado en sí se renderiza desde CatalogoController; este
 * controlador solo atiende el crudModal (store crea o actualiza según venga
 * o no el id, edit/destroy REST normales) — mismo patrón que FeriadoController.
 */
class MaterialController extends Controller
{
    public function index()
    {
        return response()->json(['materiales' => Material::orderBy('codigo')->get()]);
    }

    public function store(Request $request)
    {
        $materialId = $request->id;

        $rules = [
            'codigo' => 'required|string|max:30|unique:materiales,codigo' . ($materialId ? ",$materialId" : ''),
            'descripcion' => 'required|string|max:255',
            'unidad' => 'required|string|max:20',
            'precio_base' => 'required|numeric|min:0',
            'margen_pct' => 'nullable|numeric|min:0|max:100',
            'stock_inicial' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'factor_metros_por_unidad' => 'required|numeric|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'codigo', 'descripcion', 'unidad', 'precio_base',
            'stock_inicial', 'stock_minimo', 'factor_metros_por_unidad',
        ]);
        // El formulario trabaja el margen en porcentaje (0-100); en base de
        // datos se guarda como fracción (0-1), igual que el resto del
        // sistema (ver ParametroControlInterno::meta_ind2).
        $data['margen_pct'] = $request->filled('margen_pct') ? round($request->margen_pct / 100, 4) : null;

        if ($materialId) {
            $material = Material::findOrFail($materialId);
            $material->update($data);
            $message = 'Material actualizado.';
        } else {
            $material = Material::create($data);
            $message = 'Material agregado.';
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.materiales.index'),
        ]);
    }

    // El resource se registró como Route::resource('items', ...) (para no
    // repetir "materiales/materiales" en la URL bajo el prefijo del
    // módulo), así que el parámetro de ruta implícito es {item} — el
    // nombre del argumento tiene que ser $item para que el route model
    // binding lo resuelva, aunque el tipo siga siendo Material.
    public function edit(Material $item)
    {
        return response()->json([
            'material' => [
                'id' => $item->id,
                'codigo' => $item->codigo,
                'descripcion' => $item->descripcion,
                'unidad' => $item->unidad,
                'precio_base' => $item->precio_base,
                'margen_pct' => $item->margen_pct !== null ? round($item->margen_pct * 100, 2) : null,
                'stock_inicial' => $item->stock_inicial,
                'stock_minimo' => $item->stock_minimo,
                'factor_metros_por_unidad' => $item->factor_metros_por_unidad,
            ],
        ]);
    }

    public function destroy(Material $item)
    {
        $item->delete();

        session()->flash('message', 'Material eliminado.');
        return redirect()->route('employee.materiales.index');
    }
}
