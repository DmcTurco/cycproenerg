<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instalacion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'instalacions';

    protected $fillable = [
        'tipo_instalacion',
        'tipo_acometida',
        'numero_puntos_instalacion',
        'fecha_finalizacion_instalacion_interna',
        'fecha_finalizacion_instalacion_acometida',
        'resultado_instalacion_tc',
        'fecha_programacion_habilitacion',
        'solicitud_id',
        // CI-6 (parte segura): solo se capturan, no disparan ninguna regla de
        // eliminación/archivado todavía — ver la migración
        // add_anulacion_columns_to_instalacions_table y control-interno.md.
        'rechazada',
        'anulada',
        'motivo_anulacion',
    ];

    protected $casts = [
        'fecha_finalizacion_instalacion_interna' => 'date',
        'fecha_finalizacion_instalacion_acometida' => 'date',
        'fecha_programacion_habilitacion' => 'date',
        'rechazada' => 'boolean',
        'anulada' => 'boolean',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

}
