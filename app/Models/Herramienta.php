<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-1: catálogo de herramientas (hoja CATALOGO, bloque de herramientas,
 * filas 164+ del Excel). El código HER-### se autogenera (nunca lo llena
 * el staff); responsable actual y ubicación se calculan desde `entregas`
 * (CM-7).
 */
class Herramienta extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'herramientas';

    protected $fillable = [
        'codigo',
        'descripcion',
        'marca_modelo',
        'numero_serie',
        'fecha_compra',
        'precio',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'precio' => 'float',
    ];

    /**
     * CATALOGO!B164+ — "HER-" + correlativo de 3 dígitos, igual que la
     * fórmula del Excel ("HER-"&TEXT(ROW()-164,"000")), pero basado en el
     * máximo código ya usado en vez de la fila.
     */
    public static function siguienteCodigo(): string
    {
        $ultimo = static::withTrashed()
            ->selectRaw("MAX(CAST(SUBSTRING(codigo FROM 5) AS INTEGER)) as n")
            ->value('n');

        return 'HER-' . str_pad((int) $ultimo + 1, 3, '0', STR_PAD_LEFT);
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class);
    }

    /**
     * CATALOGO!I164+ — RESPONSABLE ACTUAL:
     *   =IFERROR(IF(LOOKUP(2,1/(ENTREGAS!$B$5:$B$304=$B165),ENTREGAS!$C$5:$C$304)="DEVOLUCION",
     *       "ALMACEN",
     *       LOOKUP(2,1/(ENTREGAS!$B$5:$B$304=$B165),ENTREGAS!$D$5:$D$304)),
     *       "ALMACEN")
     * Es decir: se busca el ÚLTIMO movimiento (por fecha, luego id) de esta
     * herramienta en `entregas`; si ese último movimiento es DEVOLUCION,
     * vuelve a estar en ALMACEN; si es ENTREGA, el responsable es la
     * cuadrilla de esa fila; si nunca tuvo movimientos, ALMACEN.
     */
    public function responsableActual(): ?string
    {
        $ultima = $this->entregas()
            ->with('cuadrilla')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        if (!$ultima || $ultima->tipo === Entrega::TIPO_DEVOLUCION) {
            return null;
        }

        return $ultima->cuadrilla->nombre ?? null;
    }

    /**
     * CATALOGO!J164+ — UBICACION: PERDIDA si el estado lo dice, EN CAMPO
     * si tiene responsable (CM-7), si no ALMACEN.
     */
    public function ubicacion(): string
    {
        if ($this->estado === 'PERDIDA') {
            return 'PERDIDA';
        }

        return $this->responsableActual() ? 'EN CAMPO' : 'ALMACEN';
    }
}
