<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Control General (Internas · TC · Habilitación): en qué hoja del Excel
 * "Control General CLB - C&C.xlsm" vive cada solicitud. Las reglas que la
 * mueven de etapa están en App\Services\ControlGeneralClasificador.
 *
 * Reemplaza al antiguo módulo Control Interno.
 */
class ControlGeneral extends Model
{
    use Auditable {
        auditFiltrar as auditFiltrarBase;
    }
    use SoftDeletes;

    protected string $auditModuloNombre = 'Control General';

    // Los guarda la carga del portal, no son acciones de nadie.
    protected array $auditExcluir = ['fecha_aprobacion', 'resultado_tc_portal', 'observado_portal'];

    protected $table = 'control_generales';

    // Etapas = hojas del Excel, en el orden de las pestañas.
    const APROBADA = 'APROBADA';
    const TC_CONSTRUIDA = 'TC_CONSTRUIDA';
    const HABILITACION = 'HABILITACION';
    const HABILITADA = 'HABILITADA';
    const ANULACION = 'ANULACION';

    const ETAPAS = [
        self::APROBADA => '1. Aprobadas',
        self::TC_CONSTRUIDA => '2. TC Construidas',
        self::HABILITACION => '3. Habilitación',
        self::ANULACION => '4. Anulaciones y reingresos',
        self::HABILITADA => '5. Habilitadas',
    ];

    const ACCIONES = ['Anular', 'Reingresar'];
    const ESTADOS_TRAMITE = ['Pendiente', 'En trámite', 'Reingresado', 'Anulado'];
    const RESULTADOS_TC = ['Sin registro', 'Pendiente', 'Rechazada', 'Concluida'];

    // "Control de plazo" de 1-Aprobadas (columna H del Excel).
    const PLAZO_SIN_FECHA = 'Sin fecha';
    const PLAZO_AVANZO = 'Avanzó en portal';
    const PLAZO_DESFASE = 'EN DESFASE';
    const PLAZO_EN_PLAZO = 'En plazo';
    const CONTROLES_PLAZO = [self::PLAZO_EN_PLAZO, self::PLAZO_DESFASE, self::PLAZO_SIN_FECHA, self::PLAZO_AVANZO];

    // "Situación" de 3-Habilitación (columna U del Excel).
    const SITUACIONES = ['Por programar', 'Programado', 'Observado'];

    protected $fillable = [
        'solicitud_id',
        'etapa',
        'fecha_ingreso_etapa',
        'fecha_aprobacion',
        'construida',
        'asesor_id',
        'cuadrilla_id',
        'contratista_id',
        'observacion',
        'fecha_observacion',
        'resultado_tc',
        'resultado_tc_portal',
        'accion',
        'fecha_marca',
        'estado_tramite',
        'fecha_programacion',
        'tecnico_id',
        'fecha_ultima_visita',
        'observado',
        'observado_portal',
        'habilitado',
        'fecha_habilitacion',
        'datos_documentos',
    ];

    protected $casts = [
        'fecha_ingreso_etapa' => 'date',
        'fecha_aprobacion' => 'date',
        'fecha_observacion' => 'date',
        'fecha_marca' => 'date',
        'fecha_programacion' => 'date',
        'fecha_ultima_visita' => 'date',
        'fecha_habilitacion' => 'date',
        'construida' => 'integer',
        'observado' => 'integer',
        'observado_portal' => 'integer',
        'habilitado' => 'integer',
        'datos_documentos' => 'array',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    // Se elige de las listas registradas. Cuadrilla, técnico y contratista
    // son la misma tabla (personas_campo) según el rol.
    public function asesor()
    {
        return $this->belongsTo(Asesor::class, 'asesor_id')->withTrashed();
    }

    public function cuadrilla()
    {
        return $this->belongsTo(PersonaCampo::class, 'cuadrilla_id')->withTrashed();
    }

    public function tecnico()
    {
        return $this->belongsTo(PersonaCampo::class, 'tecnico_id')->withTrashed();
    }

    public function contratista()
    {
        return $this->belongsTo(PersonaCampo::class, 'contratista_id')->withTrashed();
    }

    /**
     * En el log se guarda el NOMBRE (no el id) de quién se eligió, para que
     * la auditoría se lea sola aunque después cambie o se borre la persona.
     */
    public function auditFiltrar(array $atributos): array
    {
        $atributos = $this->auditFiltrarBase($atributos);

        $roles = ['asesor_id' => Asesor::class, 'cuadrilla_id' => PersonaCampo::class, 'tecnico_id' => PersonaCampo::class, 'contratista_id' => PersonaCampo::class];
        foreach ($roles as $columna => $clase) {
            if (!array_key_exists($columna, $atributos)) {
                continue;
            }
            $id = $atributos[$columna];
            unset($atributos[$columna]);
            $atributos[substr($columna, 0, -3)] = $id ? ($clase::withTrashed()->find($id)?->nombre ?? "#{$id}") : null;
        }

        return $atributos;
    }

    /**
     * Días desde la aprobación (columna G de 1-Aprobadas): días calendario,
     * igual que `Resumen!C4 - E5` del Excel.
     */
    public function diasDesdeAprobacion(?Carbon $hoy = null): ?int
    {
        if (!$this->fecha_aprobacion) {
            return null;
        }

        return (int) $this->fecha_aprobacion->startOfDay()->diffInDays(($hoy ?? now())->copy()->startOfDay(), false);
    }

    /**
     * Columna H de 1-Aprobadas: Sin fecha / Avanzó en portal / EN DESFASE / En plazo.
     * ("Pasó a TC" no existe acá: al marcar Construida la fila se mueve al toque.)
     */
    public function controlPlazo(int $diasLimite, ?string $codigoEstadoPortal, ?Carbon $hoy = null): string
    {
        if (!$this->fecha_aprobacion) {
            return self::PLAZO_SIN_FECHA;
        }

        if (substr((string) $codigoEstadoPortal, 0, 2) !== '02') {
            return self::PLAZO_AVANZO;
        }

        return $this->diasDesdeAprobacion($hoy) > $diasLimite ? self::PLAZO_DESFASE : self::PLAZO_EN_PLAZO;
    }

    /**
     * Columna U de 3-Habilitación.
     */
    public function situacion(): string
    {
        if ($this->habilitado) {
            return 'Habilitado';
        }
        if ($this->observado) {
            return 'Observado';
        }

        return $this->fecha_programacion ? 'Programado' : 'Por programar';
    }

    /**
     * Etiqueta de empresa como en el Excel: "C&C" o "CLB".
     */
    public static function etiquetaEmpresa(?Empresa $empresa): string
    {
        if (!$empresa) {
            return '—';
        }

        if ($empresa->codigo) {
            return $empresa->codigo === 'CYC' ? 'C&C' : $empresa->codigo;
        }

        return self::esCyC((string) $empresa->nombre) ? 'C&C' : 'CLB';
    }

    /**
     * Misma regla del VBA (ReadPortal): es C&C si el nombre de la empresa
     * instaladora contiene "C & C" o "C&C"; si no, es CLB.
     */
    public static function esCyC(string $nombreEmpresa): bool
    {
        return stripos($nombreEmpresa, 'C & C') !== false || stripos($nombreEmpresa, 'C&C') !== false;
    }
}
