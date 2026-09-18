<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-2: personas que retiran materiales/herramientas (equivalente a
 * "REGISTRO DE CUADRILLAS" dentro de RESUMEN, en el Excel de Control de
 * Materiales). Tabla nueva, sin relación con `Tecnico`.
 */
class Cuadrilla extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cuadrillas';

    public const TIPO_CONTRATISTA = 'CONTRATISTA';
    public const TIPO_PERSONAL_DIRECTO = 'PERSONAL DIRECTO';

    protected $fillable = [
        'nombre',
        'tipo',
        'estado',
        'dni',
        'fecha_nacimiento',
        'celular',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class);
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    /**
     * N° de retiros (cotizaciones y vales) — columna I de RESUMEN.
     * ✅ CM-4/CM-5 (18/09/2026).
     */
    public function numRetiros(): int
    {
        return $this->cotizaciones()->count();
    }

    /**
     * Total valorizado S/IGV — columna J de RESUMEN.
     * ✅ CM-4/CM-5.
     */
    public function totalValorizado(): float
    {
        return (float) $this->cotizaciones()->sum('monto_sin_igv');
    }

    /**
     * Pendiente de descuento C/IGV — columna K de RESUMEN. Solo cuenta lo
     * PENDIENTE (cotizaciones a contratistas sin descontar todavía en una
     * valorización real); los vales de personal directo nunca quedan
     * PENDIENTE (su estado es "VALE - USO INTERNO").
     * ✅ CM-4/CM-5.
     */
    public function pendienteDescuento(): float
    {
        return (float) $this->cotizaciones()
            ->where('estado', Cotizacion::ESTADO_PENDIENTE)
            ->sum('total_con_igv');
    }
}
