<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudTecnico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'solicitudes_tecnico';

    protected $fillable = [
        'tecnico_id',
        'solicitud_id',
        'numero_solicitud',
        'numero_documento',
        'tipo_cliente',
    ];

}
