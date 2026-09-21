<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-1: catálogo de herramientas (hoja CATALOGO, bloque de herramientas,
 * filas 164+ del Excel). `codigo` es texto libre, lo escribe el staff a
 * mano (igual que Material::codigo); `correlativo` es el identificador
 * interno estable "HER-###" que se autogenera al crear y nunca cambia.
 * Responsable actual y ubicación se calculan desde `entregas` (CM-7).
 */
class Herramienta extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'herramientas';

    /**
     * Serie fija del correlativo interno (ver siguienteCorrelativo()) —
     * no confundir con la columna `numero_serie` (N° de serie de fábrica).
     */
    public const SERIE = 'HER';

    protected $fillable = [
        'codigo',
        'serie',
        'correlativo',
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
     * Correlativo de 8 dígitos para la serie HER, igual criterio que
     * Material::siguienteCorrelativo() (MAX() sobre el string funciona
     * bien porque todos los correlativos tienen el mismo ancho).
     */
    public static function siguienteCorrelativo(): string
    {
        $max = static::withTrashed()->where('serie', self::SERIE)->max('correlativo');

        return str_pad((string) ((int) $max + 1), 8, '0', STR_PAD_LEFT);
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
