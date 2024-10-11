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
        'solicitante_id',
        'direccion',
        'departamento',
        'provincia',
        'distrito',
        'ubicacion',
        'codigo_manzana',
        'nombre_malla',
    ];

    // Relación con solicitante
    public function solicitante()
    {
        return $this->belongsTo(Solicitante::class);
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

}
