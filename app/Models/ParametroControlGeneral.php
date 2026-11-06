<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Parámetros de Control General (una sola fila): días límite desde la
 * aprobación y provincias que entran al control. Se lee con ::actual().
 */
class ParametroControlGeneral extends Model
{
    use Auditable;

    protected string $auditModuloNombre = 'Parámetros Control General';

    protected $table = 'parametros_control_generales';

    protected $fillable = ['dias_limite_aprobacion', 'ambito_departamentos'];

    protected $casts = [
        'dias_limite_aprobacion' => 'integer',
        'ambito_departamentos' => 'array',
    ];

    public static function actual(): self
    {
        // Ojo: no usar firstOrCreate(['id' => 1]): `id` no es fillable y se
        // descarta, así que se crearía una fila nueva cada vez que la tabla
        // no empieza en 1.
        return static::query()->orderBy('id')->first() ?? static::create([
            'dias_limite_aprobacion' => 20,
            'ambito_departamentos' => ['LIMA', 'CALLAO'],
        ]);
    }
}
