<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\Request;

/**
 * Consulta de la auditoría (solo lectura): quién hizo qué y qué cambió,
 * para rastrear de quién fue un problema.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->only(['usuario', 'modulo', 'accion', 'desde', 'hasta', 'buscar']);

        $auditorias = Auditoria::query()
            ->when($filtros['usuario'] ?? null, fn ($q, $v) => $q->where('usuario_nombre', $v))
            ->when($filtros['modulo'] ?? null, fn ($q, $v) => $q->where('modulo', $v))
            ->when($filtros['accion'] ?? null, fn ($q, $v) => $q->where('accion', $v))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v . ' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'))
            ->when($filtros['buscar'] ?? null, function ($q, $v) {
                $q->where(function ($q) use ($v) {
                    $q->whereLike('descripcion', "%{$v}%");
                    if (ctype_digit($v)) {
                        $q->orWhere('auditable_id', (int) $v);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        return view('employee.pages.auditoria.index', [
            'auditorias' => $auditorias,
            'filtros' => $filtros,
            'usuarios' => Auditoria::whereNotNull('usuario_nombre')->distinct()->orderBy('usuario_nombre')->pluck('usuario_nombre'),
            'modulos' => Auditoria::whereNotNull('modulo')->distinct()->orderBy('modulo')->pluck('modulo'),
            'acciones' => Auditoria::ACCIONES,
        ]);
    }
}
