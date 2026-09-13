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
        // OJO: la columna real (migración create_logs_table) es "tamaño_archivo"
        // (con ñ) — el fillable original decía "tamano_archivo" (sin ñ) y
        // nunca se había notado porque nada creaba filas en `logs` todavía
        // (ver CI-8). Se corrige acá para que el mass-assignment no falle.
        'tamaño_archivo',
        'filas_procesadas',
        'filas_con_error',
        'errores',
        'estado',
        'employee_id',
        // CI-8: contadores de Control Interno de esta carga (ver migración
        // add_control_interno_columns_to_logs_table).
        'resumen_control_interno',
    ];

    protected $casts = [
        'resumen_control_interno' => 'array',
    ];

    public function Empleado()
    {
        return $this->belongsTo(Employee::class);
    }
}
