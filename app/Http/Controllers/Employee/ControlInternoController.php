<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\FaseControlInterno;
use App\Models\Solicitud;
use App\Services\ControlInternoIndicadores;
use Illuminate\Http\Request;

/**
 * CI-9: listado de Control Interno — equivalente a las hojas GENERAL /
 * CONSTRUIDO / TC / PEND_ANULACION del Excel, pero en una sola pantalla con
 * pestañas por fase (en vez de una hoja por fase), más los filtros de
 * empresa (CYC/CLB) y categoría (RES/MULTI/COM) que en el Excel eran los
 * botones FiltrarCYC/FiltrarRES/etc. de la hoja INICIO.
 */
class ControlInternoController extends Controller
{
    public function index(Request $request)
    {
        $fase = $request->query('fase');
        if (!in_array($fase, FaseControlInterno::FASES, true)) {
            $fase = null; // sin pestaña seleccionada = "Todas"
        }

        $empresaCodigo = $request->query('empresa') ?: null;
        $categoriaCodigo = $request->query('categoria') ?: null;
        $categoriasMap = config('const.control_interno.categorias', []);
        $categoriaTextos = $categoriaCodigo
            ? array_keys(array_filter($categoriasMap, fn ($codigo) => $codigo === $categoriaCodigo))
            : null;

        $baseQuery = Solicitud::query()
            ->when($empresaCodigo, function ($query) use ($empresaCodigo) {
                $query->whereHas('empresa', fn ($q) => $q->where('codigo', $empresaCodigo));
            })
            ->when($categoriaTextos, function ($query) use ($categoriaTextos) {
                $query->whereHas('proyecto', fn ($q) => $q->whereIn('categoria_proyecto', $categoriaTextos));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('numero_solicitud', 'ilike', '%' . $request->query('search') . '%');
            });

        // Conteo por fase para las pestañas — respeta empresa/categoría/
        // búsqueda ya aplicados, pero no el filtro de fase (para que las
        // pestañas siempre muestren cuántas hay en cada una).
        $conteos = (clone $baseQuery)
            ->join('fase_control_internos as fci', function ($join) {
                $join->on('fci.solicitud_id', '=', 'solicituds.id')->whereNull('fci.deleted_at');
            })
            ->selectRaw('fci.fase as fase, count(*) as total')
            ->groupBy('fci.fase')
            ->pluck('total', 'fase');

        $solicitudes = (clone $baseQuery)
            ->with(['faseControlInterno', 'instalacion', 'empresa', 'proyecto', 'asesor', 'tecnico'])
            ->when($fase, function ($query) use ($fase) {
                $query->whereHas('faseControlInterno', fn ($q) => $q->where('fase', $fase));
            })
            ->orderByDesc('numero_solicitud')
            ->paginate(20)
            ->appends($request->query());

        // CI-5 (13/09/2026): paraAlmacenado() en vez de para() — este listado
        // solo necesita el último caché guardado (se refresca solo con cada
        // carga/edición y una vez al día), no recalcular en vivo fila por
        // fila (ver docblock de ControlInternoIndicadores).
        $solicitudes->getCollection()->transform(function (Solicitud $solicitud) {
            $solicitud->ci_indicadores = ControlInternoIndicadores::paraAlmacenado($solicitud);

            return $solicitud;
        });

        return view('employee.pages.control-interno.index', [
            'solicitudes' => $solicitudes,
            'fases' => FaseControlInterno::FASES,
            'faseActual' => $fase,
            'conteos' => $conteos,
            'empresas' => Empresa::whereNotNull('codigo')->orderBy('codigo')->get(),
            'categorias' => $categoriasMap,
            'empresaActual' => $empresaCodigo,
            'categoriaActual' => $categoriaCodigo,
        ]);
    }
}
