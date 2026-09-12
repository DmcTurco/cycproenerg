<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * En qué fase de Control Interno está una solicitud (equivalente a "en qué
 * hoja vive la fila" en el Excel: GENERAL / CONSTRUIDO / TC / PEND_ANULACION).
 *
 * No confundir con EstadoInterno: esa tabla es el estado de asignación a
 * técnico de campo (pendiente/asignado/Iniciado/...), un eje independiente
 * de esta fase.
 */
class FaseControlInterno extends Model
{
    use SoftDeletes;

    protected $table = 'fase_control_internos';

    const GENERAL = 'GENERAL';
    const CONSTRUIDO = 'CONSTRUIDO';
    const TC = 'TC';
    const PEND_ANULACION = 'PEND_ANULACION';

    const FASES = [
        self::GENERAL,
        self::CONSTRUIDO,
        self::TC,
        self::PEND_ANULACION,
    ];

    protected $fillable = [
        'solicitud_id',
        'fase',
        'fecha_ingreso_general',
        'fecha_construccion_control',
        'fecha_tc',
        'observacion_control',
        'marcado_para_anular',
    ];

    protected $casts = [
        'fecha_ingreso_general' => 'date',
        'fecha_construccion_control' => 'date',
        'fecha_tc' => 'date',
        'marcado_para_anular' => 'boolean',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
}
