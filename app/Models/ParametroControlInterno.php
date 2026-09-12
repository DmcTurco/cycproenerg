<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración de Control Interno — equivalente a la hoja PARAM del Excel.
 * Es una tabla de una sola fila: se lee con ParametroControlInterno::actual().
 */
class ParametroControlInterno extends Model
{
    protected $table = 'parametros_control_internos';

    protected $fillable = [
        'plazo_construccion_dias_habiles',
        'semaforo_verde_dias',
        'semaforo_ambar_dias',
        'espera_tc_verde_dias',
        'espera_tc_ambar_dias',
        'meta_ind2',
        'ambito_departamentos',
    ];

    protected $casts = [
        'meta_ind2' => 'float',
        'ambito_departamentos' => 'array',
    ];

    /**
     * Devuelve la fila única de parámetros, creándola con los valores por
     * defecto del Excel (PARAM) si todavía no existe.
     */
    public static function actual(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'plazo_construccion_dias_habiles' => 20,
            'semaforo_verde_dias' => 10,
            'semaforo_ambar_dias' => 20,
            'espera_tc_verde_dias' => 15,
            'espera_tc_ambar_dias' => 30,
            'meta_ind2' => 0.95,
            'ambito_departamentos' => ['LIMA', 'CALLAO'],
        ]);
    }
}
