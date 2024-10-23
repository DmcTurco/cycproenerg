<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EstadoSolicitud extends Pivot
{
    use SoftDeletes;

    protected $fillable = [
        'estado_id',
        'solicitud_id'
    ];
}
