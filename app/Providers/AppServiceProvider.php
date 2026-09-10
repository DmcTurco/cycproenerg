<?php

namespace App\Providers;

use App\MyApp;
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

        // Antes vivía en App\Providers\RouteServiceProvider (removido: el
        // registro de rutas web/api ahora se hace en bootstrap/app.php).
        RateLimiter::for('api', function (HttpRequest $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
