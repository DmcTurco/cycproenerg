<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Lee config/permisos.php: qué permisos existen y qué permiso exige cada
 * ruta. Lo usan el middleware VerificarPermiso, el menú lateral y la
 * pantalla de Roles.
 */
class Permisos
{
    public const GUARD = 'employee';

    /** Todos los nombres de permiso ("modulo.accion"). */
    public static function todos(): array
    {
        $permisos = [];
        foreach (config('permisos.modulos', []) as $modulo => $datos) {
            foreach (array_keys($datos['acciones']) as $accion) {
                $permisos[] = "{$modulo}.{$accion}";
            }
        }

        return $permisos;
    }

    /**
     * Permiso que exige la ruta (null = libre). "{modulo}.guardar" se
     * resuelve a crear/editar según venga o no un id en el request; sin
     * request (menú) se devuelve tal cual.
     */
    public static function deRuta(?string $nombreRuta, ?Request $request = null): ?string
    {
        if (!$nombreRuta) {
            return null;
        }

        foreach (config('permisos.rutas', []) as $patron => $permiso) {
            if (Str::is($patron, $nombreRuta)) {
                if ($request && Str::endsWith($permiso, '.guardar')) {
                    $modulo = Str::beforeLast($permiso, '.guardar');

                    return $request->filled('id') ? "{$modulo}.editar" : "{$modulo}.crear";
                }

                return $permiso;
            }
        }

        return null;
    }

    public static function puede(?Authenticatable $usuario, ?string $permiso): bool
    {
        if ($permiso === null) {
            return true;
        }
        if (!$usuario || !method_exists($usuario, 'can')) {
            return false;
        }
        if (Str::endsWith($permiso, '.guardar')) {
            $modulo = Str::beforeLast($permiso, '.guardar');

            return $usuario->can("{$modulo}.crear") || $usuario->can("{$modulo}.editar");
        }

        return $usuario->can($permiso);
    }

    /** ¿El empleado logueado puede abrir esta ruta? (para el menú). */
    public static function puedeRuta(string $nombreRuta): bool
    {
        $permiso = self::deRuta($nombreRuta);
        if ($permiso === null) {
            return true;
        }

        return self::puede(Auth::guard(self::GUARD)->user(), $permiso);
    }

    /** Texto legible de un permiso ("Materiales: ingresos → Crear"). */
    public static function etiqueta(string $permiso): string
    {
        [$modulo, $accion] = array_pad(explode('.', $permiso, 2), 2, '');
        $datos = config("permisos.modulos.{$modulo}");

        return $datos ? $datos['label'] . ' → ' . ($datos['acciones'][$accion] ?? $accion) : $permiso;
    }
}
