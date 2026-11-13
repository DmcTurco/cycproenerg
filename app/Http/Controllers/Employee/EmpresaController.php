<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de cada empresa instaladora que salen en los documentos de
 * habilitación (dirección, categoría, teléfono, póliza…) y sus imágenes:
 * logo y firma y sello del representante legal. Las empresas las crea sola
 * la carga del Excel del portal; acá solo se completan.
 */
class EmpresaController extends Controller
{
    public function index()
    {
        return view('employee.pages.empresas.index', [
            'empresas' => Empresa::query()->orderByRaw('codigo IS NULL')->orderBy('codigo')->orderBy('nombre')->get(),
        ]);
    }

    public function edit(Empresa $empresa)
    {
        return view('employee.pages.empresas.edit', ['empresa' => $empresa]);
    }

    public function update(Request $request, Empresa $empresa)
    {
        $datos = $request->validate([
            'registro_gas_natural' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
            'distrito' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'categoria' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:255',
            'numero_poliza' => 'nullable|string|max:30',
            'representante_legal' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'sello' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'logo.image' => 'El logo debe ser una imagen PNG o JPG.',
            'sello.image' => 'El sello debe ser una imagen PNG o JPG.',
            'logo.max' => 'El logo pesa más de 2 MB.',
            'sello.max' => 'El sello pesa más de 2 MB.',
        ]);

        foreach (['logo', 'sello'] as $imagen) {
            unset($datos[$imagen]);

            if ($request->hasFile($imagen)) {
                if ($empresa->{$imagen}) {
                    Storage::disk('public')->delete($empresa->{$imagen});
                }
                $datos[$imagen] = $request->file($imagen)->store('empresas', 'public');
            } elseif ($request->boolean("quitar_{$imagen}") && $empresa->{$imagen}) {
                Storage::disk('public')->delete($empresa->{$imagen});
                $datos[$imagen] = null;
            }
        }

        $empresa->update($datos);

        return redirect()->route('employee.empresas.index')->with('success', 'Datos de la empresa guardados.');
    }

    /** Muestra el logo o el sello en la pantalla (no hace falta `storage:link`). */
    public function imagen(Empresa $empresa, string $tipo)
    {
        abort_unless(in_array($tipo, ['logo', 'sello'], true), 404);

        $ruta = $empresa->rutaArchivo($tipo);
        abort_unless($ruta, 404);

        return response()->file($ruta, ['Cache-Control' => 'private, max-age=300']);
    }
}
