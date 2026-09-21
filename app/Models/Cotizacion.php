<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

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

    /**
     * Series del correlativo interno de 8 dígitos — una por tipo de
     * documento, igual criterio que el prefijo de `numero`.
     */
    public const SERIE_COTIZACION = 'COT';
    public const SERIE_VALE = 'VAL';

    protected $fillable = [
        'numero',
        'serie',
        'correlativo',
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
     *
     * lockForUpdate() bloquea las filas de ese prefijo hasta que la
     * transacción de emitir() termine, para que dos documentos emitidos
     * casi al mismo tiempo (mismo tipo+fecha) no calculen el mismo
     * correlativo. Solo sirve si ya existe al menos un documento con ese
     * prefijo — el primero del día no tiene fila que bloquear, por eso
     * emitir() reintenta una vez si el INSERT choca con el índice único.
     */
    public static function siguienteNumero(string $prefijo): string
    {
        $max = static::withTrashed()
            ->where('numero', 'like', $prefijo . '-%')
            ->lockForUpdate()
            ->pluck('numero')
            ->map(fn ($numero) => (int) substr($numero, strlen($prefijo) + 1))
            ->max();

        return $prefijo . '-' . str_pad((string) (($max ?? 0) + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Correlativo interno de 8 dígitos, único por serie (COT o VAL) y
     * aparte de `numero` (que es el documento visible para el staff, con
     * prefijo por tipo+fecha). Mismo criterio que
     * Material::siguienteCorrelativo() / Herramienta::siguienteCorrelativo():
     * se asigna una sola vez, al emitir, nunca al corregir.
     */
    public static function siguienteCorrelativo(string $serie): string
    {
        // MAX() vía SQL no es compatible con lockForUpdate() en Postgres
        // ("FOR UPDATE is not allowed with aggregate functions"), por eso
        // se trae todo y se calcula el máximo en PHP, igual que
        // siguienteNumero().
        $max = static::withTrashed()
            ->lockForUpdate()
            ->where('serie', $serie)
            ->pluck('correlativo')
            ->map(fn ($correlativo) => (int) $correlativo)
            ->max();

        return str_pad((string) (($max ?? 0) + 1), 8, '0', STR_PAD_LEFT);
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
     *
     * Todo corre dentro de una transacción (con lockForUpdate() en
     * siguienteNumero()) para que dos documentos emitidos casi al mismo
     * tiempo no terminen con el mismo número. El único caso que ese lock
     * no cubre es el primer documento de un prefijo (no hay fila previa
     * que bloquear): ahí el índice único de `numero` igual evita el
     * duplicado, y acá se reintenta una vez para que el segundo usuario
     * simplemente reciba el siguiente correlativo en vez de un error 500.
     */
    public static function emitir(Cuadrilla $cuadrilla, array $items, string $fecha, ?self $cotizacion = null): self
    {
        $reintentado = false;

        do {
            try {
                return DB::transaction(function () use ($cuadrilla, $items, $fecha, $cotizacion) {
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
                        $serie = $esVale ? self::SERIE_VALE : self::SERIE_COTIZACION;
                        $cotizacion = static::create([
                            'numero' => static::siguienteNumero($prefijo),
                            'serie' => $serie,
                            'correlativo' => static::siguienteCorrelativo($serie),
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
                });
            } catch (QueryException $e) {
                $esChoqueDeCorrelativo = $e->getCode() === '23505'
                    && (str_contains($e->getMessage(), 'cotizaciones_numero_unique')
                        || str_contains($e->getMessage(), 'cotizaciones_serie_correlativo_unique'))
                    && ! $cotizacion;

                if (! $esChoqueDeCorrelativo || $reintentado) {
                    throw $e;
                }

                $reintentado = true;
            }
        } while (true);
    }
}
