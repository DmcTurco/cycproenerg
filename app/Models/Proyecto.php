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
        'solicitante_id',
        'tipo_proyecto',
        'codigo_proyecto',
        'categoria',
        'sub_categoria',
        'codigo_objeto_conexion',
    ];

    public function solicitante()
    {
        return $this->belongsTo(Solicitante::class);
    }
    
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }
}
