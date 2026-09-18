<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-1: catálogo de herramientas (hoja CATALOGO, bloque de herramientas,
 * filas 164+ del Excel). El código HER-### se autogenera (nunca lo llena
 * el staff); responsable actual y ubicación son fórmulas en el Excel que
 * dependen de `entregas` (CM-7, todavía no existe) — hasta entonces se
 * asume que toda herramienta está en ALMACEN (nadie se la llevó todavía).
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

    /**
     * CATALOGO!I164+ — RESPONSABLE ACTUAL. Se calcula desde `entregas`
     * (CM-7, todavía no existe): mientras tanto, ninguna herramienta tiene
     * responsable (todas están en almacén).
     */
    public function responsableActual(): ?string
    {
        return null;
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
