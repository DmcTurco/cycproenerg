<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Feriados usados para el cálculo de días hábiles de Control Interno
 * (equivalente a PARAM!J17:J40 en el Excel, sin límite de filas).
 */
class Feriado extends Model
{
    protected $table = 'feriados';

    protected $fillable = [
        'fecha',
        'descripcion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Fechas (Y-m-d) de todos los feriados registrados, listas para pasar
     * a un cálculo de días hábiles.
     */
    public static function fechas(): array
    {
        return static::pluck('fecha')
            ->map(fn ($fecha) => $fecha->format('Y-m-d'))
            ->all();
    }
}
