<?php

namespace App\Http\Middleware;

use App\Models\PersonaCampo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * App móvil: solo el personal de campo entra, y solo mientras siga
 * habilitado. auth:sanctum únicamente valida que el token exista; sin esto,
 * una persona pasada a INACTIVO (o a la que se le quitó el email) seguiría
 * usando la app con el token que ya tenía hasta que cerrara sesión.
 *
 * Si ya no corresponde, se revoca el token actual y se responde 401 para
 * que la app vuelva sola a la pantalla de login.
 */
class PersonaCampoActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario instanceof PersonaCampo) {
            return response()->json(['message' => 'Acceso solo para personal de campo.'], 401);
        }

        if (!$usuario->estaActivo() || !$usuario->usaApp()) {
            $usuario->currentAccessToken()?->delete();

            return response()->json(['message' => 'Tu acceso a la app fue deshabilitado. Contacta al administrador.'], 401);
        }

        return $next($request);
    }
}
