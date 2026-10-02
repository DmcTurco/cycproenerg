<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Los guards personalizados (admin/company/employee) se siguen
        // aplicando directamente en las rutas con 'auth:employee', etc.
        // Aquí solo registramos los alias 'auth' y 'guest' con nuestras
        // clases propias (redirect a login con soporte JSON / multi-guard).
        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            // Roles y permisos del panel de empleados (config/permisos.php).
            'permiso' => \App\Http\Middleware\VerificarPermiso::class,
        ]);

        // El resto (TrustProxies, TrimStrings, VerifyCsrfToken, EncryptCookies,
        // HandleCors, PreventRequestsDuringMaintenance, ValidateSignature) usa
        // el comportamiento por defecto de Laravel 12, que es idéntico al que
        // tenía este proyecto en app/Http/Kernel.php (grupos 'web' y 'api'
        // sin personalizar). config/cors.php se sigue leyendo igual.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        // Sesión vencida: el token CSRF ya no vale y Laravel mostraría
        // "419 Page Expired" (p. ej. al pulsar "Cerrar sesión" o enviar un
        // formulario después de SESSION_LIFETIME). En vez de eso se manda
        // al login del panel correspondiente (/employee, /company, /admin).
        // Laravel ya convirtió el TokenMismatchException en un HttpException
        // 419 antes de llegar acá, por eso se filtra por código.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'La sesión expiró. Vuelve a iniciar sesión.'], 419);
            }

            $routeType = $request->routeType();
            $login = $routeType ? '/' . $routeType . '/login' : '/' . \App\MyApp::EMPLOYEE_SUBDIR . '/login';

            return redirect($login)->with('status', 'Tu sesión expiró. Vuelve a iniciar sesión.');
        });
    })
    ->create();
