<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración de Control de Materiales — equivalente al bloque
 * "PARAMETROS GENERALES" de la hoja INICIO del Excel. Es una tabla de una
 * sola fila: se lee con ParametroControlMaterial::actual().
 */
class ParametroControlMaterial extends Model
{
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
     * Devuelve la fila única de parámetros, creándola con los valores por
     * defecto del Excel (INICIO!G6:G8) si todavía no existe.
     */
    public static function actual(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'igv' => 0.18,
            'margen_general' => 0.10,
            'umbral_alarma_precio' => 0.05,
        ]);
    }
}
