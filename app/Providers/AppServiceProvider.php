<?php

namespace App\Providers;

use App\Models\Auditoria;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use App\MyApp;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $requestUri = $this->app->request->getRequestUri();
        Request::macro('routeType', function () use ($requestUri) {
            if (preg_match("#^/" . MyApp::ADMINS_SUBDIR . "/#", $requestUri)) {
                return MyApp::ADMINS_SUBDIR;
            } elseif (preg_match("#^/" . MyApp::COMPANIES_SUBDIR . "/#", $requestUri)) {
                return MyApp::COMPANIES_SUBDIR;
            } elseif (preg_match("#^/" . MyApp::EMPLOYEE_SUBDIR . "/#", $requestUri)) {
                return MyApp::EMPLOYEE_SUBDIR;
            } else {
                return null;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        // Antes vivía en App\Providers\EventServiceProvider (removido en la
        // migración a Laravel 12: el auto-discovery de eventos ya estaba
        // desactivado ahí, así que este listener se registra a mano).
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        // El rol Administrador tiene todos los permisos, aunque se agreguen
        // permisos nuevos y nadie se los marque.
        Gate::before(function ($usuario) {
            return $usuario instanceof Employee && $usuario->esAdministrador() ? true : null;
        });

        // Auditoría de accesos al panel web (la app móvil se registra en
        // ApiTecnicoController). Del intento fallido solo se guarda el
        // correo, nunca la contraseña.
        // Los guards del sistema son modelos Eloquent; se verifica igual.
        $modelo = fn ($u) => $u instanceof Model ? $u : null;
        Event::listen(Login::class, function (Login $event) use ($modelo) {
            $u = $modelo($event->user);
            Auditoria::registrar('login', ($u->name ?? 'Usuario') . " inició sesión (panel {$event->guard})", $u, modulo: 'Acceso', usuario: $u);
        });
        Event::listen(Logout::class, function (Logout $event) use ($modelo) {
            if ($u = $modelo($event->user)) {
                Auditoria::registrar('logout', ($u->name ?? 'Usuario') . " cerró sesión (panel {$event->guard})", $u, modulo: 'Acceso', usuario: $u);
            }
        });
        Event::listen(Failed::class, function (Failed $event) use ($modelo) {
            Auditoria::registrar('login_fallido', 'Intento de acceso fallido con ' . ($event->credentials['email'] ?? '¿sin correo?') . " (panel {$event->guard})", $modelo($event->user), modulo: 'Acceso');
        });

        // Antes vivía en App\Providers\RouteServiceProvider (removido: el
        // registro de rutas web/api ahora se hace en bootstrap/app.php).
        RateLimiter::for('api', function (HttpRequest $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
