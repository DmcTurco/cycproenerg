<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Ruta a la que se redirige a un usuario ya autenticado.
     *
     * Antes vivía en App\Providers\RouteServiceProvider::HOME (esa clase
     * se eliminó al migrar a Laravel 12; el valor no cambió).
     */
    public const HOME = '/home';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect(self::HOME);
            }
        }

        return $next($request);
    }
}
