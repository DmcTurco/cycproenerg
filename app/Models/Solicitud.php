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
        'numero_suministro',
        'numero_contrato_suministro',
        'fecha_aprobacion_contrato',
        'fecha_registro_portal',
        'estado_solicitud',
        'solicitante_id',
        'empresa_id',
        'concesionaria_id',
        'asesor_id',
    ];

    protected $casts = [
        'fecha_aprobacion_contrato' => 'date',
        'fecha_registro_portal' => 'date',
    ];

    /**
     * Get the solicitante associated with the Solicitud.
     */
    public function solicitante()
    {
        return $this->belongsTo(Solicitante::class, 'solicitante_id');
    }

    /**
     * Get the empresa associated with the Solicitud.
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Get the concesionaria associated with the Solicitud.
     */
    public function concesionaria()
    {
        return $this->belongsTo(Concesionaria::class, 'concesionaria_id');
    }

    /**
     * Get the asesor associated with the Solicitud.
     */
    // public function asesor()
    // {
    //     return $this->belongsTo(Asesor::class, 'asesor_id');
    // }

    public function tecnico() {
        return $this->belongsToMany(Tecnico::class, 'solicitud_tecnico', 'solicitud_id', 'tecnico_id')->withPivot('categoria')->withTimestamps();
    }
}
