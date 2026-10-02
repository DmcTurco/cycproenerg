<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Registro de auditoría: quién hizo qué, sobre qué registro y qué cambió
 * (antes / después). Se llena solo con el trait App\Models\Concerns\Auditable
 * (crear / editar / eliminar / restaurar de los modelos) y a mano con
 * Auditoria::registrar() para las acciones que no pasan por un modelo
 * (asignaciones masivas, carga del Excel, cierre de mes, login…).
 *
 * No registra visitas a pantallas, solo acciones. Es de solo escritura: el
 * sistema no la edita ni la borra.
 */
class Auditoria extends Model
{
    protected $table = 'auditorias';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'antes' => 'array',
        'despues' => 'array',
        'created_at' => 'datetime',
    ];

    /** Etiquetas legibles de cada acción (para el listado y los filtros). */
    public const ACCIONES = [
        'creado' => 'Creó',
        'actualizado' => 'Editó',
        'eliminado' => 'Eliminó',
        'restaurado' => 'Restauró',
        'eliminado_definitivo' => 'Eliminó definitivamente',
        'asignado' => 'Asignó',
        'desasignado' => 'Quitó asignación',
        'cambio_estado' => 'Cambió estado',
        'importado' => 'Importó',
        'cierre_mes' => 'Cerró mes',
        'login' => 'Inició sesión',
        'logout' => 'Cerró sesión',
        'login_fallido' => 'Intento de acceso fallido',
    ];

    /** Mientras sea > 0 no se registra nada (ver sinRegistrar()). */
    private static int $pausas = 0;

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAccionLabelAttribute(): string
    {
        return self::ACCIONES[$this->accion] ?? $this->accion;
    }

    /**
     * Ejecuta $callback sin registrar auditoría (p. ej. la carga del Excel
     * del portal, que toca miles de filas: se registra UNA entrada resumen).
     */
    public static function sinRegistrar(callable $callback): mixed
    {
        self::$pausas++;
        try {
            return $callback();
        } finally {
            self::$pausas--;
        }
    }

    public static function pausada(): bool
    {
        return self::$pausas > 0;
    }

    /**
     * Registra una acción. $usuario permite indicar el autor a mano (p. ej.
     * en un job en cola, donde no hay sesión); si no, se toma del guard
     * autenticado de la petición actual.
     */
    public static function registrar(
        string $accion,
        ?string $descripcion = null,
        ?Model $registro = null,
        ?array $antes = null,
        ?array $despues = null,
        ?string $modulo = null,
        ?Model $usuario = null,
    ): ?self {
        if (self::pausada()) {
            return null;
        }

        // La auditoría nunca debe romper la acción del usuario.
        try {
            $usuario ??= self::usuarioActual();
            $request = app()->runningInConsole() && !app()->runningUnitTests() ? null : request();

            return self::create([
                'usuario_type' => $usuario?->getMorphClass(),
                'usuario_id' => $usuario?->getKey(),
                'usuario_nombre' => $usuario ? ($usuario->name ?? $usuario->nombre ?? $usuario->email ?? null) : 'Sistema',
                'accion' => $accion,
                'modulo' => $modulo ?? ($registro ? self::moduloDe($registro) : null),
                'auditable_type' => $registro?->getMorphClass(),
                'auditable_id' => $registro?->getKey(),
                'descripcion' => $descripcion ? mb_substr($descripcion, 0, 500) : null,
                'antes' => $antes ?: null,
                'despues' => $despues ?: null,
                'ip' => $request?->ip(),
                'metodo' => $request?->method(),
                'url' => $request ? mb_substr($request->fullUrl(), 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar la auditoría: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Usuario autenticado de la petición: el guard por defecto (lo fija el
     * middleware auth:employee / auth:sanctum de la ruta) y, si no, los
     * guards del sistema uno por uno.
     */
    public static function usuarioActual(): ?Model
    {
        foreach ([null, 'employee', 'company', 'admin'] as $guard) {
            try {
                $user = Auth::guard($guard)->user();
            } catch (\Throwable) {
                $user = null;
            }
            if ($user instanceof Model) {
                return $user;
            }
        }

        return null;
    }

    public static function moduloDe(Model $registro): string
    {
        return method_exists($registro, 'auditModulo') ? $registro->auditModulo() : class_basename($registro);
    }
}
