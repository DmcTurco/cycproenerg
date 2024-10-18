<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asesor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asesores';
    
    protected $fillable = [
        'nombre',
        'tipo_documento',
        'numero_documento_identificacion',
        'telefono',
        'email',
        'direccion',
        'fecha_contratacion',
        'comision',
        'total_ventas',
        'numero_ventas',
        'estado',
        'observaciones',
    ];
}
