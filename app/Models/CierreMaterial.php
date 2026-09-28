<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CM-11: un cierre de mes de Control de Materiales (equivalente a la
 * macro CerrarMes). Ver la migración create_cierres_materiales_table y
 * App\Services\ControlMaterialesCierre para el detalle de qué hace cada
 * cierre.
 */
class CierreMaterial extends Model
{
    protected $table = 'cierres_materiales';

    protected $fillable = [
        'etiqueta',
        'fecha_cierre',
        'employee_id',
        'resumen',
    ];

    protected $casts = [
        'fecha_cierre' => 'date',
        'resumen' => 'array',
    ];

    public function ingresosArchivados(): HasMany
    {
        return $this->hasMany(Ingreso::class)->withTrashed();
    }

    public function ejecutadosArchivados(): HasMany
    {
        return $this->hasMany(Ejecutado::class)->withTrashed();
    }

    public function cotizacionesArchivadas(): HasMany
    {
        return $this->hasMany(Cotizacion::class)->withTrashed();
    }
}
