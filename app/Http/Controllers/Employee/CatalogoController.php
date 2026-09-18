<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Herramienta;
use App\Models\Material;
use Illuminate\Http\Request;

/**
 * CM-1: pantalla de Catálogo — equivalente a la hoja CATALOGO del Excel,
 * con sus dos bloques (materiales y herramientas) como pestañas en vez de
 * estar uno debajo del otro en el mismo sheet.
 */
class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'herramientas' ? 'herramientas' : 'materiales';
        $search = trim((string) $request->query('search'));

        $materiales = Material::when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'ilike', "%{$search}%")
                        ->orWhere('descripcion', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('codigo')
            ->get();

        $herramientas = Herramienta::when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'ilike', "%{$search}%")
                        ->orWhere('descripcion', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('codigo')
            ->get();

        return view('employee.pages.materiales.index', compact('tab', 'materiales', 'herramientas', 'search'));
    }
}
