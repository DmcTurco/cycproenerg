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
    })
    ->create();
