<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'proyectos';

    protected $fillable = [
        'tipo_proyecto',
        'codigo_proyecto',
        'categoria_proyecto',
        'sub_categoria_proyecto',
        'codigo_objeto_conexion',
        'solicitud_id',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }
}
