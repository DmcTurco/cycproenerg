<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Herramienta;
use App\Models\InventarioFisico;
use App\Models\InventarioFisicoDetalle;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CM-10: Inventario físico de fin de mes (hoja "INVENTARIO FISICO"). Con
 * historial (a diferencia del Excel, que borra los conteos al cerrar el
 * mes — ver App\Services\ControlMaterialesCierre): cada conteo guardado
 * queda disponible para siempre en `inventarios_fisicos`.
 *
 * A diferencia de CM-4/CM-6 (formulario de página completa con lista
 * DINÁMICA de ítems), acá la lista es FIJA: todo el catálogo actual, con
 * el stock del sistema ya pre-llenado — no hace falta ningún JS/Alpine
 * nuevo, es un formulario plano.
 */
class InventarioFisicoController extends Controller
{
    public function index()
    {
        $inventarios = InventarioFisico::withCount('detalles')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20);

        return view('employee.pages.materiales.inventario-fisico.index', [
            'inventarios' => $inventarios,
        ]);
    }

    /**
     * Formulario en blanco: una fila por cada material y por cada
     * herramienta del catálogo actual, con el stock del sistema ya
     * calculado (INVENTARIO FISICO!D6:D162 y D166:D265) para que solo
     * falte anotar el conteo físico.
     */
    public function create()
    {
        $materiales = Material::orderBy('codigo')->get()->map(fn (Material $m) => [
            'material_id' => $m->id,
            'codigo' => $m->codigo,
            'descripcion' => $m->descripcion,
            'unidad' => $m->unidad,
            'stock_sistema' => $m->stockActual(),
        ]);

        $herramientas = Herramienta::orderBy('codigo')->get()->map(fn (Herramienta $h) => [
            'herramienta_id' => $h->id,
            'codigo' => $h->codigo,
            'descripcion' => $h->descripcion,
            'ubicacion_sistema' => $h->ubicacion(),
            // INVENTARIO FISICO!D166+ = 1 si el sistema espera que esté en
            // ALMACEN, 0 si espera que esté EN CAMPO (Herramienta::ubicacion()).
            'stock_sistema' => $h->ubicacion() === 'ALMACEN' ? 1 : 0,
        ]);

        return view('employee.pages.materiales.inventario-fisico.create', [
            'materiales' => $materiales,
            'herramientas' => $herramientas,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => 'required|date',
            'realizado_por' => 'required|string|max:120',
            'nombre_almacenero' => 'nullable|string|max:120',
            'nombre_supervisor' => 'nullable|string|max:120',
            'observacion_general' => 'nullable|string',
            'materiales' => 'array',
            'materiales.*.material_id' => 'required|exists:materiales,id',
            'materiales.*.conteo_fisico' => 'nullable|numeric|min:0',
            'materiales.*.observacion_ubicacion' => 'nullable|string|max:255',
            'herramientas' => 'array',
            'herramientas.*.herramienta_id' => 'required|exists:herramientas,id',
            // La vista usa un <select> (no un checkbox) con tres opciones:
            // vacío ("no se contó esta herramienta"), "1" ("sí está en el
            // almacén") o "0" ("no está en el almacén") — así se distingue
            // sin ambigüedad "no contada" de "contada, no está".
            'herramientas.*.conteo_fisico' => 'nullable|numeric|in:0,1',
        ]);

        $inventario = DB::transaction(function () use ($data) {
            $inventario = InventarioFisico::create([
                'fecha' => $data['fecha'],
                'realizado_por' => $data['realizado_por'],
                'nombre_almacenero' => $data['nombre_almacenero'] ?? null,
                'nombre_supervisor' => $data['nombre_supervisor'] ?? null,
                'observacion_general' => $data['observacion_general'] ?? null,
                'employee_id' => Auth::id(),
            ]);

            foreach ($data['materiales'] ?? [] as $fila) {
                if (! isset($fila['conteo_fisico']) || $fila['conteo_fisico'] === '') {
                    continue;
                }

                $material = Material::findOrFail($fila['material_id']);
                $stockSistema = $material->stockActual();
                $conteo = (float) $fila['conteo_fisico'];

                InventarioFisicoDetalle::create([
                    'inventario_fisico_id' => $inventario->id,
                    'tipo' => InventarioFisicoDetalle::TIPO_MATERIAL,
                    'material_id' => $material->id,
                    'codigo' => $material->codigo,
                    'descripcion' => $material->descripcion,
                    'unidad' => $material->unidad,
                    'stock_sistema' => $stockSistema,
                    'conteo_fisico' => $conteo,
                    'diferencia' => $conteo - $stockSistema,
                    'observacion_ubicacion' => $fila['observacion_ubicacion'] ?? null,
                ]);
            }

            foreach ($data['herramientas'] ?? [] as $fila) {
                if (! isset($fila['conteo_fisico']) || $fila['conteo_fisico'] === '') {
                    continue;
                }

                $herramienta = Herramienta::findOrFail($fila['herramienta_id']);
                $ubicacionSistema = $herramienta->ubicacion();
                $stockSistema = $ubicacionSistema === 'ALMACEN' ? 1 : 0;
                $conteo = (float) $fila['conteo_fisico'];

                InventarioFisicoDetalle::create([
                    'inventario_fisico_id' => $inventario->id,
                    'tipo' => InventarioFisicoDetalle::TIPO_HERRAMIENTA,
                    'herramienta_id' => $herramienta->id,
                    'codigo' => $herramienta->codigo,
                    'descripcion' => $herramienta->descripcion,
                    'unidad' => 'UND',
                    'stock_sistema' => $stockSistema,
                    'conteo_fisico' => $conteo,
                    'diferencia' => $conteo - $stockSistema,
                    // Para herramientas este campo es automático (la
                    // ubicación que el sistema tenía registrada), no texto
                    // libre — a diferencia de los materiales.
                    'observacion_ubicacion' => $ubicacionSistema,
                ]);
            }

            return $inventario;
        });

        session()->flash('message', 'Inventario físico del ' . $inventario->fecha->format('d/m/Y') . ' guardado (' . $inventario->detalles()->count() . ' ítem(s) contados).');

        return redirect()->route('employee.materiales.inventario-fisico.show', $inventario);
    }

    public function show(InventarioFisico $inventarioFisico)
    {
        $inventarioFisico->load('detalles');

        return view('employee.pages.materiales.inventario-fisico.show', [
            'inventario' => $inventarioFisico,
            'materialesConDiferencia' => $inventarioFisico->detalles->where('tipo', InventarioFisicoDetalle::TIPO_MATERIAL)->where('diferencia', '<>', 0),
            'herramientasConDiferencia' => $inventarioFisico->detalles->where('tipo', InventarioFisicoDetalle::TIPO_HERRAMIENTA)->where('diferencia', '<>', 0),
        ]);
    }

    public function destroy(InventarioFisico $inventarioFisico)
    {
        $inventarioFisico->delete();

        session()->flash('message', 'Inventario físico eliminado (queda en la papelera, no se pierde el dato).');

        return redirect()->route('employee.materiales.inventario-fisico.index');
    }
}
