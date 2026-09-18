<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-1: catálogo de materiales (hoja CATALOGO, bloque de materiales,
 * filas 5-161 del Excel). Guarda solo las columnas azules (editables a
 * mano); el resto (ingresos, salidas, stock actual, precio vigente,
 * alerta de precio, estado) son fórmulas en el Excel y se calculan aquí
 * como accessors, no como columnas — así nunca quedan desactualizados.
 *
 * IMPORTANTE (roadmap, ver docs/modulos/control-materiales.md): estos
 * accessors se completan por partes a medida que avanzan los submódulos
 * que alimentan el kardex:
 *   - ingresos()/ultimoPrecioIngresos()/alertaPrecio() -> CM-3 (tabla
 *                        `ingresos`) — ✅ implementado el 18/09/2026.
 *   - salidas()       -> CM-4 (cotizaciones a contratistas, tabla
 *                        `cotiz_detalles`) + CM-6 (tabla `ejecutados`).
 *                        Todavía pendiente.
 * Hasta que esas tablas existan, estos métodos devuelven el valor base
 * (stock_inicial) sin tocar nada que todavía no existe.
 */
class Material extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'materiales';

    protected $fillable = [
        'codigo',
        'descripcion',
        'unidad',
        'precio_base',
        'margen_pct',
        'stock_inicial',
        'stock_minimo',
        'factor_metros_por_unidad',
    ];

    protected $casts = [
        'precio_base' => 'float',
        'margen_pct' => 'float',
        'stock_inicial' => 'float',
        'stock_minimo' => 'float',
        'factor_metros_por_unidad' => 'float',
    ];

    /**
     * Relación con el kardex de entradas (CM-3). Nombre distinto del
     * accessor ingresos() (que devuelve el TOTAL, como CATALOGO!J) para no
     * chocar con él.
     */
    public function ingresosMovimientos(): HasMany
    {
        return $this->hasMany(Ingreso::class);
    }

    /**
     * CATALOGO!J — total ingresado, suma de `ingresos.cantidad` (CM-3).
     */
    public function ingresos(): float
    {
        return (float) $this->ingresosMovimientos()->sum('cantidad');
    }

    /**
     * Relación con las líneas de cotización/vale que salieron de este
     * material (CM-4).
     */
    public function cotizacionDetalles(): HasMany
    {
        return $this->hasMany(CotizacionDetalle::class);
    }

    /**
     * CATALOGO!K — total de salidas:
     *   - CONTRATISTA (cotización): descuenta ya, al emitir (CM-4) — ✅.
     *   - PERSONAL DIRECTO (vale): NO descuenta al emitir, recién cuando
     *     se reporta lo EJECUTADO (CM-6, todavía no existe) — sigue en 0.
     */
    public function salidas(): float
    {
        $salidasContratistas = (float) $this->cotizacionDetalles()
            ->whereHas('cotizacion', fn ($query) => $query->where('es_vale', false))
            ->sum('cantidad');

        // TODO CM-6: sumar lo ejecutado por personal directo (dividido por
        // factor_metros_por_unidad para tuberías), ver EJECUTADO en el Excel.
        $salidasPersonalDirecto = 0.0;

        return $salidasContratistas + $salidasPersonalDirecto;
    }

    /**
     * CATALOGO!L — STOCK ACTUAL = inicial + ingresos - salidas.
     */
    public function stockActual(): float
    {
        return $this->stock_inicial + $this->ingresos() - $this->salidas();
    }

    /**
     * CATALOGO!N — estado según stock actual vs. stock mínimo.
     */
    public function estado(): string
    {
        $stock = $this->stockActual();

        if ($stock <= 0) {
            return 'SIN STOCK';
        }

        return $stock <= $this->stock_minimo ? 'REPONER' : 'OK';
    }

    /**
     * CATALOGO!P — último precio de compra registrado en INGRESOS (CM-3).
     * El Excel usa LOOKUP(2,1/(...)) para tomar el ÚLTIMO valor no vacío
     * en el orden en que se cargaron las filas; acá se traduce como "la
     * fila de ingreso más reciente (por fecha, y por id como desempate)
     * que tenga precio_compra".
     */
    public function ultimoPrecioIngresos(): ?float
    {
        $ultimo = $this->ingresosMovimientos()
            ->whereNotNull('precio_compra')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        return $ultimo?->precio_compra;
    }

    /**
     * CATALOGO!E — el precio vigente solo SUBE: si el último precio de
     * INGRESOS es mayor al precio base, ese es el vigente; si no, se queda
     * en el precio base (decisión manual de Turco, ver control-interno.md
     * y ahora control-materiales.md: nunca baja solo).
     */
    public function precioVigente(): float
    {
        $ultimo = $this->ultimoPrecioIngresos();

        if ($ultimo !== null && $ultimo > $this->precio_base) {
            return $ultimo;
        }

        return $this->precio_base;
    }

    /**
     * CATALOGO!Q — alerta de precio comparando el ÚLTIMO ingreso contra el
     * precio base (CM-3). Distinta de Ingreso::alertaPrecio() (columna I de
     * INGRESOS, por fila y con umbral): esta es simple mayor/menor/igual.
     *
     *   =IF(OR(ultimo="",base=""),"",
     *       IF(ultimo>base,"SUBIO: vigente actualizado",
     *       IF(ultimo<base,"BAJO: decidir si actualizar BASE","ESTABLE")))
     */
    public function alertaPrecio(): ?string
    {
        $ultimo = $this->ultimoPrecioIngresos();

        if ($ultimo === null) {
            return null;
        }

        if ($ultimo > $this->precio_base) {
            return 'SUBIO: vigente actualizado';
        }

        if ($ultimo < $this->precio_base) {
            return 'BAJO: decidir si actualizar BASE';
        }

        return 'ESTABLE';
    }

    /**
     * CATALOGO!F/G — margen efectivo (propio del ítem si lo tiene, si no
     * el general de parametros_control_materiales) y precio de venta.
     */
    public function margenEfectivo(): float
    {
        return $this->margen_pct ?? ParametroControlMaterial::actual()->margen_general;
    }

    public function precioVentaSinIgv(): float
    {
        return round($this->precioVigente() * (1 + $this->margenEfectivo()), 2);
    }

    public function precioVentaConIgv(): float
    {
        return round($this->precioVentaSinIgv() * (1 + ParametroControlMaterial::actual()->igv), 2);
    }
}
