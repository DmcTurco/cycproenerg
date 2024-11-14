<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Historial extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'solicitud_id',
        'tecnico_id',
        'estado_const_id',
        'descripcion',
        'employee_id'
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    // public function estadoInterno()
    // {
    //     return $this->belongsTo(EstadoInterno::class);
    // }

    public function Empleado()
    {
        return $this->belongsTo(Employee::class);
    }
}
