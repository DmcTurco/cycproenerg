<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Configuración de Control de Materiales — equivalente al bloque
 * "PARAMETROS GENERALES" de la hoja INICIO del Excel. Es una tabla de una
 * sola fila: se lee con ParametroControlMaterial::actual().
 */
class ParametroControlMaterial extends Model
{
    use Auditable;

    protected string $auditModuloNombre = 'Parámetros Materiales';

    protected $table = 'parametros_control_materiales';

    protected $fillable = [
        'igv',
        'margen_general',
        'umbral_alarma_precio',
    ];

    protected $casts = [
        'igv' => 'float',
        'margen_general' => 'float',
        'umbral_alarma_precio' => 'float',
    ];

    /**
     * IGV y margen general entran en el precio de venta guardado en cada
     * material: si cambian, se recalcula todo el catálogo.
     */
    protected static function booted(): void
    {
        static::updated(function (ParametroControlMaterial $parametros) {
            if ($parametros->wasChanged(['igv', 'margen_general'])) {
                Material::all()->each->recalcularPreciosVenta();
            }
        });
    }

    /**
     * Devuelve la fila única de parámetros, creándola con los valores por
     * defecto del Excel (INICIO!G6:G8) si todavía no existe.
     *
     * No se busca por id = 1: `id` no es fillable, así que la fila se crea
     * con el autoincremento (que no siempre es 1) y un firstOrCreate(['id'
     * => 1]) volvía a crear otra fila con los valores por defecto en cada
     * llamada, ignorando los parámetros guardados.
     */
    public static function actual(): self
    {
        return static::query()->orderBy('id')->first() ?? static::create([
            'igv' => 0.18,
            'margen_general' => 0.10,
            'umbral_alarma_precio' => 0.05,
        ]);
    }
}
