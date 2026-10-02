<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;

/**
 * Registra en `auditorias` cada alta, edición, eliminación y restauración
 * del modelo, con el usuario que la hizo y SOLO los campos que cambiaron
 * (antes / después).
 *
 * Opcional en el modelo que lo usa:
 *   protected string $auditModuloNombre = 'Material';   // nombre legible
 *   protected array $auditExcluir = ['campo_calculado']; // no registrar
 *
 * OJO: las escrituras masivas por query (Modelo::where()->update()) no
 * disparan eventos de Eloquent; esas se registran a mano con
 * Auditoria::registrar() donde ocurren.
 */
trait Auditable
{
    /** Campos que nunca se registran (ruido o datos sensibles). */
    private static array $auditExcluirSiempre = [
        'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
    ];

    public static function bootAuditable(): void
    {
        static::created(function ($modelo) {
            Auditoria::registrar(
                'creado',
                $modelo->auditModulo() . ' ' . $modelo->auditIdentificador(),
                $modelo,
                null,
                $modelo->auditFiltrar($modelo->getAttributes()),
            );
        });

        static::updated(function ($modelo) {
            $despues = $modelo->auditFiltrar($modelo->getChanges());
            if (!$despues) {
                return; // solo cambiaron campos excluidos (p. ej. cachés)
            }
            $antes = array_intersect_key($modelo->getRawOriginal(), $despues);

            Auditoria::registrar(
                'actualizado',
                $modelo->auditModulo() . ' ' . $modelo->auditIdentificador(),
                $modelo,
                $antes,
                $despues,
            );
        });

        static::deleted(function ($modelo) {
            $definitivo = !method_exists($modelo, 'isForceDeleting') || $modelo->isForceDeleting();

            Auditoria::registrar(
                $definitivo ? 'eliminado_definitivo' : 'eliminado',
                $modelo->auditModulo() . ' ' . $modelo->auditIdentificador(),
                $modelo,
                $modelo->auditFiltrar($modelo->getRawOriginal()),
                null,
            );
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($modelo) {
                Auditoria::registrar(
                    'restaurado',
                    $modelo->auditModulo() . ' ' . $modelo->auditIdentificador(),
                    $modelo,
                );
            });
        }
    }

    public function auditModulo(): string
    {
        return property_exists($this, 'auditModuloNombre') ? $this->auditModuloNombre : class_basename($this);
    }

    /** Cómo se nombra el registro en la descripción ("Material TUB-001"). */
    public function auditIdentificador(): string
    {
        foreach (['codigo', 'numero', 'numero_solicitud', 'etiqueta', 'nombre', 'name', 'descripcion'] as $campo) {
            $valor = $this->getAttribute($campo);
            if (is_scalar($valor) && trim((string) $valor) !== '') {
                return mb_substr((string) $valor, 0, 80);
            }
        }

        return '#' . $this->getKey();
    }

    public function auditFiltrar(array $atributos): array
    {
        $excluir = array_merge(
            self::$auditExcluirSiempre,
            property_exists($this, 'auditExcluir') ? $this->auditExcluir : []
        );

        return array_diff_key($atributos, array_flip($excluir));
    }
}
