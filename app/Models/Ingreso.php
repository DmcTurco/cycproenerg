<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-3: kardex de entradas (hoja INGRESOS). Una fila por cada llegada de
 * material al almacén; alimenta Material::ingresos()/ultimoPrecioIngresos().
 */
class Ingreso extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ingresos';

    protected $fillable = [
        'material_id',
        'fecha',
        'cantidad',
        'proveedor',
        'guia_factura',
        'precio_compra',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'float',
        'precio_compra' => 'float',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * ALERTA PRECIO de la fila (columna I de INGRESOS) — distinta de
     * Material::alertaPrecio() (columna Q de CATALOGO): esta compara ESTE
     * precio de compra contra el precio base usando el umbral de alarma
     * (INICIO!$G$8 en el Excel), no un simple mayor/menor.
     *
     *   =IF(OR(codigo="",precio=""),"",
     *       IF((precio-base)/base > umbral, "SUBIO +X.X%",
     *       IF(precio<base, "BAJO", "ESTABLE")))
     */
    public function alertaPrecio(): ?string
    {
        if ($this->precio_compra === null || !$this->material) {
            return null;
        }

        $base = (float) $this->material->precio_base;

        if ($base <= 0) {
            return null;
        }

        $variacion = ($this->precio_compra - $base) / $base;
        $umbral = (float) ParametroControlMaterial::actual()->umbral_alarma_precio;

        if ($variacion > $umbral) {
            return 'SUBIO +' . number_format($variacion * 100, 1) . '%';
        }

        if ($this->precio_compra < $base) {
            return 'BAJO';
        }

        return 'ESTABLE';
    }
}
