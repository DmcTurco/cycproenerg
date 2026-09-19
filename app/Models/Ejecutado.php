<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-6: una fila reportada en REGISTRO RAPIDO, guardada en EJECUTADO.
 * Exclusivo de PERSONAL DIRECTO — los contratistas nunca pasan por acá
 * (su descuento de stock ya ocurrió al emitir la cotización, CM-4).
 */
class Ejecutado extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ejecutados';

    public const MOVIMIENTO_SALIDA = 'SALIDA';
    public const MOVIMIENTO_DEVOLUCION = 'DEVOLUCION';

    protected $fillable = [
        'fecha',
        'tipo_trabajo',
        'cuadrilla_id',
        'material_id',
        'cantidad',
        'n_suministro',
        'observacion',
        'movimiento',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'float',
    ];

    public function cuadrilla(): BelongsTo
    {
        return $this->belongsTo(Cuadrilla::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * EJECUTADO!J — precio costo S/IGV, EN LA UNIDAD REPORTADA (metros
     * para tuberías): precio vigente del material (por rollo/unidad de
     * catálogo) dividido por el factor metros-por-unidad. Es un cálculo
     * en vivo con el precio ACTUAL, igual que la fórmula del Excel — no
     * una foto guardada al momento del reporte.
     */
    public function precioCosto(): float
    {
        if (!$this->material) {
            return 0.0;
        }

        $factor = max(1.0, (float) $this->material->factor_metros_por_unidad);

        return $this->material->precioVigente() / $factor;
    }

    /**
     * EJECUTADO!K — total S/IGV: cantidad × precio costo, en NEGATIVO si
     * es una DEVOLUCION (para que sumado con las salidas dé el neto).
     */
    public function total(): float
    {
        $total = round($this->cantidad * $this->precioCosto(), 2);

        return $this->movimiento === self::MOVIMIENTO_DEVOLUCION ? -$total : $total;
    }
}
