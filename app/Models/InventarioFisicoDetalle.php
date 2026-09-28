<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CM-10: una fila de un InventarioFisico — un material o una herramienta
 * contados en ese inventario. Ver la migración create_inventario_fisico_detalles_table
 * para el detalle de qué significa cada columna según el `tipo`.
 */
class InventarioFisicoDetalle extends Model
{
    protected $table = 'inventario_fisico_detalles';

    public const TIPO_MATERIAL = 'MATERIAL';
    public const TIPO_HERRAMIENTA = 'HERRAMIENTA';

    protected $fillable = [
        'inventario_fisico_id',
        'tipo',
        'material_id',
        'herramienta_id',
        'codigo',
        'descripcion',
        'unidad',
        'stock_sistema',
        'conteo_fisico',
        'diferencia',
        'observacion_ubicacion',
    ];

    protected $casts = [
        'stock_sistema' => 'float',
        'conteo_fisico' => 'float',
        'diferencia' => 'float',
    ];

    public function inventarioFisico(): BelongsTo
    {
        return $this->belongsTo(InventarioFisico::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function herramienta(): BelongsTo
    {
        return $this->belongsTo(Herramienta::class);
    }
}
