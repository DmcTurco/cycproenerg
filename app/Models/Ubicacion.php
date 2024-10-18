<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ubicacion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ubicacions';

    protected $fillable = [
        'ubicacion',
        'codigo_manzana',
        'codigo_identificacion_interna',
        'nombre_malla',
        'direccion',
        'departamento',
        'provincia',
        'distrito',
        'venta_zona_no_gasificada',
        'solicitud_id',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }
}
