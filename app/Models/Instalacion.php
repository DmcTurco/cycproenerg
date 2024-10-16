<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instalacion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'instalaciones';

    protected $fillable = [
        'tipo_instalacion',
        'tipo_acometida',
        'numero_puntos_instalacion',
        'fecha_finalizacion_instalacion_interna',
        'fecha_finalizacion_instalacion_acometida',
        'resultado_instalacion_tc',
        'fecha_programacion_habilitacion',
        'solicitud_id',
    ];

    protected $casts = [
        'fecha_finalizacion_instalacion_interna' => 'date',
        'fecha_finalizacion_instalacion_acometida' => 'date',
        'fecha_programacion_habilitacion' => 'date',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }
    
}
