<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Logs extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre_archivo',
        'total_filas', 
        'tamano_archivo',
        'filas_procesadas',
        'filas_con_error',
        'errores',
        'estado',
        'employee_id',

    ];

    public function Empleado()
    {
        return $this->belongsTo(Employee::class);
    }
}
