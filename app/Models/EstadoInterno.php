<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EstadoInterno extends Pivot
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'estado_internos';
    protected $fillable = [
        'estado_const_id',
        'solicitud_id'
    ];
}
