<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-4/CM-5: Cotización (a contratistas, con IGV) o Vale de entrega (a
 * personal directo, a costo, sin IGV) — equivalente a las hojas
 * COTIZACION (formulario) + COTIZACIONES (registro) del Excel. Turco pidió
 * hacerlas juntas ("van juntos, como CI-3/CI-4").
 */
class Cotizacion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cotizaciones';

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_VALE = 'VALE - USO INTERNO';
    public const ESTADO_DESCONTADO = 'DESCONTADO EN VALORIZACION';

    protected $fillable = [
        'numero',
        'fecha',
        'cuadrilla_id',
        'es_vale',
        'monto_sin_igv',
        'igv',
        'total_con_igv',
        'estado',
        'n_valorizacion',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'es_vale' => 'boolean',
        'monto_sin_igv' => 'float',
        'igv' => 'float',
        'total_con_igv' => 'float',
    ];

    public function cuadrilla(): BelongsTo
    {
        return $this->belongsTo(Cuadrilla::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CotizacionDetalle::class);
    }

    /**
     * Numeración correlativa por prefijo+fecha (SiguienteNumero de la
     * macro): $prefijo ya trae el tipo y la fecha, ej. "C&C-260826" o
     * "VALE-260716" — acá se le agrega el correlativo "-01", "-02"...
     * withTrashed() para no repetir un número que ya se usó y se anuló.
     */
    public static function siguienteNumero(string $prefijo): string
    {
        $max = static::withTrashed()
            ->where('numero', 'like', $prefijo . '-%')
            ->pluck('numero')
            ->map(fn ($numero) => (int) substr($numero, strlen($prefijo) + 1))
            ->max();

        return $prefijo . '-' . str_pad((string) (($max ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Emite (crea) o corrige (actualiza) un documento — mismo código que
     * la macro GenerarPDF: calcula precios desde el catálogo según el tipo
     * de cuadrilla, recalcula montos, y reemplaza el detalle ENTERO.
     *
     * Al corregir (se pasa $cotizacion), el número NO cambia y el ESTADO
     * tampoco se toca (igual que el Excel) — solo se anota en la
     * observación que hubo un cambio, sin borrar la anterior (a diferencia
     * del Excel, que la pisaba; ver docs/modulos/control-materiales.md).
     *
     * $items: [['material_id' => int, 'cantidad' => float], ...]
     */
    public static function emitir(Cuadrilla $cuadrilla, array $items, string $fecha, ?self $cotizacion = null): self
    {
        $esVale = $cuadrilla->tipo === Cuadrilla::TIPO_PERSONAL_DIRECTO;
        $parametros = ParametroControlMaterial::actual();

        $lineas = [];
        $montoSinIgv = 0.0;

        foreach ($items as $item) {
            $material = Material::findOrFail($item['material_id']);
            $cantidad = (float) $item['cantidad'];
            $precioUnitario = $esVale ? $material->precioVigente() : $material->precioVentaSinIgv();
            $total = round($cantidad * $precioUnitario, 2);
            $montoSinIgv += $total;

            $lineas[] = [
                'material_id' => $material->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'total' => $total,
            ];
        }

        $igv = $esVale ? 0.0 : round($montoSinIgv * $parametros->igv, 2);
        $totalConIgv = round($montoSinIgv + $igv, 2);

        if ($cotizacion) {
            $cotizacion->update([
                'cuadrilla_id' => $cuadrilla->id,
                'fecha' => $fecha,
                'monto_sin_igv' => $montoSinIgv,
                'igv' => $igv,
                'total_con_igv' => $totalConIgv,
                'observacion' => trim(($cotizacion->observacion ? $cotizacion->observacion . "\n" : '') . 'MODIFICADA ' . now()->format('d/m/Y')),
            ]);
            $cotizacion->detalles()->delete();
        } else {
            $prefijo = ($esVale ? 'VALE' : 'C&C') . '-' . Carbon::parse($fecha)->format('ymd');
            $cotizacion = static::create([
                'numero' => static::siguienteNumero($prefijo),
                'fecha' => $fecha,
                'cuadrilla_id' => $cuadrilla->id,
                'es_vale' => $esVale,
                'monto_sin_igv' => $montoSinIgv,
                'igv' => $igv,
                'total_con_igv' => $totalConIgv,
                'estado' => $esVale ? self::ESTADO_VALE : self::ESTADO_PENDIENTE,
            ]);
        }

        foreach ($lineas as $linea) {
            $cotizacion->detalles()->create($linea);
        }

        return $cotizacion->fresh(['detalles.material', 'cuadrilla']);
    }
}
