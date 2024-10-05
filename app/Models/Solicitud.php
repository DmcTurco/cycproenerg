<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Solicitud extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'solicituds';

    protected $fillable = [
        'numero_solicitud',
        'codigo_identificacion_predio',
        'numero_suministro',
        'numero_contrato_suministro',
        'fecha_registro_aprobacion_portal',
        'fecha_aprobacion_contrato',
        'fecha_registro_solicitud_portal',
        'fecha_programada_instalacion_interna',
        'fecha_inicio_instalacion_interna',
        'fecha_finalizacion_instalacion_interna',
        'fecha_finalizacion_instalacion_acometida',
        'fecha_programacion_habilitacion',
        'fecha_entrega_documentos_concesionario',
        'estado_solicitud',
        'ultima_accion_realizada',
        'solicitante_id',
    ];

    // Relación con solicitante
    public function solicitante()
    {
        return $this->belongsTo(Solicitante::class);
    }

    public function tecnico() {
        return $this->belongsToMany(Tecnico::class, 'solicitud_tecnico')->withTimestamps();
    }
}
