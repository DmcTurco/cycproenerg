<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

/**
 * Persona de campo: une lo que antes eran `Tecnico` (Gestión de Técnicos /
 * app móvil) y `Cuadrilla` (Control de Materiales, "REGISTRO DE
 * CUADRILLAS" del Excel) — es la misma gente (28/09/2026, ver
 * create_personas_campo_table.php).
 *
 * El TIPO decide qué puede hacer cada uno:
 *   - Los dos: recibir solicitudes asignadas y usar la app (si tienen
 *     email/contraseña), recibir herramientas (Entregas).
 *   - PERSONAL DIRECTO: retira con VALE a costo y reporta EJECUTADO.
 *   - CONTRATISTA: retira con COTIZACIÓN con IGV, nunca reporta EJECUTADO.
 *
 * En el resto del sistema se la sigue llamando por su rol: "cuadrilla" en
 * Control de Materiales (columna cuadrilla_id) y "técnico" en solicitudes
 * y app (columna tecnico_id).
 */
class PersonaCampo extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'personas_campo';

    public const TIPO_CONTRATISTA = 'CONTRATISTA';
    public const TIPO_PERSONAL_DIRECTO = 'PERSONAL DIRECTO';

    public const ESTADO_ACTIVO = 'ACTIVO';
    public const ESTADO_INACTIVO = 'INACTIVO';

    protected $fillable = [
        'nombre',
        'tipo',
        'estado',
        'tipo_documento',
        'numero_documento',
        'fecha_nacimiento',
        'celular',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'tipo_documento' => 'integer',
    ];

    public static function tipos(): array
    {
        return [self::TIPO_PERSONAL_DIRECTO, self::TIPO_CONTRATISTA];
    }

    public function esPersonalDirecto(): bool
    {
        return $this->tipo === self::TIPO_PERSONAL_DIRECTO;
    }

    public function esContratista(): bool
    {
        return $this->tipo === self::TIPO_CONTRATISTA;
    }

    /**
     * Tiene credenciales para la app móvil.
     */
    public function usaApp(): bool
    {
        return filled($this->email) && filled($this->password);
    }

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'empresa_persona_campo', 'persona_campo_id', 'empresa_id')
            ->withTimestamps();
    }

    // ── Solicitudes / app (antes Tecnico) ──────────────────────────────

    public function solicitudes(): BelongsToMany
    {
        return $this->belongsToMany(Solicitud::class, 'solicitud_tecnico', 'tecnico_id', 'solicitud_id')
            ->withTimestamps()
            ->whereNull('solicitud_tecnico.deleted_at');
    }

    public function historiales(): HasMany
    {
        return $this->hasMany(Historial::class, 'tecnico_id');
    }

    // ── Control de Materiales (antes Cuadrilla) ────────────────────────

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'cuadrilla_id');
    }

    public function ejecutados(): HasMany
    {
        return $this->hasMany(Ejecutado::class, 'cuadrilla_id');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class, 'cuadrilla_id');
    }

    /**
     * "EN SU PODER (por vales)" de REGISTRO RAPIDO, para TODOS los
     * materiales a la vez (una fila del formulario de CM-6 por material).
     * Solo tiene sentido para PERSONAL DIRECTO — un CONTRATISTA nunca
     * reporta EJECUTADO.
     *
     * saldo = (vale recibido, convertido a la unidad reportada con el
     *          factor_metros_por_unidad de cada material)
     *         − (todo lo EJECUTADO, sea SALIDA o DEVOLUCION — las dos
     *            reducen lo que tiene en su poder, por motivos distintos).
     *
     * Devuelve un Collection [material_id => saldo].
     */
    public function saldosEnPoder(): Collection
    {
        $vales = CotizacionDetalle::query()
            ->join('cotizaciones', 'cotizaciones.id', '=', 'cotizacion_detalles.cotizacion_id')
            ->join('materiales', 'materiales.id', '=', 'cotizacion_detalles.material_id')
            ->where('cotizaciones.cuadrilla_id', $this->id)
            ->where('cotizaciones.es_vale', true)
            ->whereNull('cotizaciones.deleted_at')
            ->selectRaw('cotizacion_detalles.material_id as material_id, SUM(cotizacion_detalles.cantidad * materiales.factor_metros_por_unidad) as total')
            ->groupBy('cotizacion_detalles.material_id')
            ->pluck('total', 'material_id');

        $ejecutado = $this->ejecutados()
            ->selectRaw('material_id, SUM(cantidad) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id');

        return Material::pluck('id')->mapWithKeys(function ($materialId) use ($vales, $ejecutado) {
            $saldo = (float) ($vales[$materialId] ?? 0) - (float) ($ejecutado[$materialId] ?? 0);

            return [$materialId => $saldo];
        });
    }

    /**
     * N° de retiros (cotizaciones y vales) — columna I de RESUMEN.
     */
    public function numRetiros(): int
    {
        return $this->cotizaciones()->count();
    }

    /**
     * Total valorizado S/IGV — columna J de RESUMEN.
     */
    public function totalValorizado(): float
    {
        return (float) $this->cotizaciones()->sum('monto_sin_igv');
    }

    /**
     * Pendiente de descuento C/IGV — columna K de RESUMEN. Solo cuenta lo
     * PENDIENTE (cotizaciones a contratistas sin descontar todavía en una
     * valorización real); los vales de personal directo nunca quedan
     * PENDIENTE (su estado es "VALE - USO INTERNO").
     */
    public function pendienteDescuento(): float
    {
        return (float) $this->cotizaciones()
            ->where('estado', Cotizacion::ESTADO_PENDIENTE)
            ->sum('total_con_igv');
    }
}
