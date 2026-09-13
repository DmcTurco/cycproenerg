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
        'solicitante_id',
        'ubicacion_id',
        'proyecto_id',
        'numero_solicitud',
        'numero_suministro',
        'numero_contrato_suministro',
        'fecha_aprobacion_contrato',
        'fecha_registro_portal',
        'estado_portal_id',
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

    public function estadoPortal()
    {
        return $this->belongsTo(EstadoPortal::class, 'estado_portal_id');
    }

    // Modelo Solicitud
    public function estadoSolicitud()
    {
        return $this->hasOne(EstadoInterno::class, 'solicitud_id');
    }

    /**
     * Fase de Control Interno (GENERAL / CONSTRUIDO / TC / PEND_ANULACION).
     * Independiente de estadoSolicitud(), que es el estado de asignación a
     * técnico de campo.
     */
    public function faseControlInterno()
    {
        return $this->hasOne(FaseControlInterno::class, 'solicitud_id');
    }


    /**
     * Get the asesor associated with the Solicitud.
     */
    public function asesor()
    {
        return $this->belongsTo(Asesor::class, 'asesor_id');
    }

    public function tecnico()
    {
        // Nota: no usar withPivot('categoria') — esa columna nunca existió en
        // la tabla solicitud_tecnico (bug preexistente encontrado el 13/09/2026
        // al usar esta relación por primera vez desde Control Interno; el
        // módulo de Asignación a Técnicos nunca pasaba por acá, siempre usaba
        // Tecnico::solicitudes() o el modelo SolicitudTecnico directamente).
        // Se excluyen asignaciones eliminadas (soft delete), igual que hace
        // Tecnico::solicitudes(), para que ambos lados de la relación coincidan.
        return $this->belongsToMany(Tecnico::class, 'solicitud_tecnico', 'solicitud_id', 'tecnico_id')
            ->withTimestamps()
            ->whereNull('solicitud_tecnico.deleted_at');
    }

    public function ubicacion()
    {
        return $this->hasOne(Ubicacion::class);
    }

    public function instalacion()
    {
        return $this->hasOne(Instalacion::class);
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }
}
