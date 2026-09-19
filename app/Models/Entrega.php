<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-7: entrega/devolución de herramientas a las cuadrillas (hoja
 * ENTREGAS). A diferencia de COTIZACION/REGISTRO RAPIDO, esta hoja no
 * tiene macro dedicada en el Excel — es un log manual, una fila por
 * movimiento. De acá salen Herramienta::responsableActual()/ubicacion().
 */
class Entrega extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'entregas';

    public const TIPO_ENTREGA = 'ENTREGA';
    public const TIPO_DEVOLUCION = 'DEVOLUCION';

    protected $fillable = [
        'fecha',
        'herramienta_id',
        'tipo',
        'cuadrilla_id',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function herramienta(): BelongsTo
    {
        return $this->belongsTo(Herramienta::class);
    }

    public function cuadrilla(): BelongsTo
    {
        return $this->belongsTo(Cuadrilla::class);
    }
}
