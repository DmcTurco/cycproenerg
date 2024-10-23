<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EstadoSolicitud extends Pivot
{
    use SoftDeletes;
    protected $table = 'estado_solicitud';
    protected $fillable = [
        'estado_id',
        'solicitud_id'
    ];
}
