<?php

namespace App\Http\Middleware;

use App\Support\Permisos;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige el permiso que config/permisos.php asigna a la ruta actual. Se
 * aplica a todo el panel de empleados; las rutas que no están en el mapa
 * quedan libres (ej. Mi Perfil).
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next): Response
    {
        $permiso = Permisos::deRuta($request->route()?->getName(), $request);

        if (!Permisos::puede($request->user(Permisos::GUARD), $permiso)) {
            $mensaje = 'No tiene permiso para esta acción (' . Permisos::etiqueta($permiso) . '). Pídale a un administrador que se lo asigne.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 403);
            }

            abort(403, $mensaje);
        }

        return $next($request);
    }
}
