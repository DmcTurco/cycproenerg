<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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

    public function ejecutados(): HasMany
    {
        return $this->hasMany(Ejecutado::class);
    }

    /**
     * "EN SU PODER (por vales)" de REGISTRO RAPIDO, para TODOS los
     * materiales a la vez (una fila del formulario de CM-6 por material).
     * Solo tiene sentido para PERSONAL DIRECTO — un CONTRATISTA nunca
     * reporta EJECUTADO.
     *
     * saldo = (vale recibido, convertido a la unidad reportada con el
     *          factor_metros_por_unidad de cada material)
     *         − (todo lo EJECUTADO, sea SALIDA o DEVOLUCION — las dos
     *            reducen lo que tiene en su poder, por motivos distintos).
     *
     * Devuelve un Collection [material_id => saldo].
     */
    public function saldosEnPoder(): Collection
    {
        $vales = CotizacionDetalle::query()
            ->join('cotizaciones', 'cotizaciones.id', '=', 'cotizacion_detalles.cotizacion_id')
            ->join('materiales', 'materiales.id', '=', 'cotizacion_detalles.material_id')
            ->where('cotizaciones.cuadrilla_id', $this->id)
            ->where('cotizaciones.es_vale', true)
            ->whereNull('cotizaciones.deleted_at')
            ->selectRaw('cotizacion_detalles.material_id as material_id, SUM(cotizacion_detalles.cantidad * materiales.factor_metros_por_unidad) as total')
            ->groupBy('cotizacion_detalles.material_id')
            ->pluck('total', 'material_id');

        $ejecutado = $this->ejecutados()
            ->selectRaw('material_id, SUM(cantidad) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id');

        return Material::pluck('id')->mapWithKeys(function ($materialId) use ($vales, $ejecutado) {
            $saldo = (float) ($vales[$materialId] ?? 0) - (float) ($ejecutado[$materialId] ?? 0);

            return [$materialId => $saldo];
        });
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
