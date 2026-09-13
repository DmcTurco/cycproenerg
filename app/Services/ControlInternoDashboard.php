<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\FaseControlInterno;
use App\Models\Logs;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Cache;

/**
 * CI-10: dashboard / resumen ejecutivo — equivalente al bloque RESUMEN
 * (INICIO!B34:D45) + BITÁCORA DE LA ÚLTIMA CARGA (INICIO!B5:B15) de la hoja
 * INICIO del Excel original. Igual que PUNTAJE (CI-7), ese bloque es 100%
 * fórmulas de hoja — se leyó directamente del archivo real en vez de
 * adivinar qué contaba cada indicador. Depende de CI-2 (fase), CI-5
 * (indicadores), CI-7 (IND2/puntaje) y CI-8 (bitácora), todos ya
 * implementados. Sin tabla propia; se cachea 5 minutos porque el desglose de
 * GENERAL recorre cada solicitud para calcular su semáforo (días hábiles),
 * igual que sugería el roadmap para CI-7.
 */
class ControlInternoDashboard
{
    public static function resumen(?int $anio = null, ?int $trimestre = null): array
    {
        $anio = $anio ?? (int) now()->year;
        $trimestre = $trimestre ?? (int) ceil(now()->month / 3);

        return Cache::remember(
            "control_interno_dashboard_{$anio}_{$trimestre}",
            now()->addMinutes(5),
            function () use ($anio, $trimestre) {
                $empresas = Empresa::whereNotNull('codigo')->orderBy('codigo')->get();
                $porEmpresa = [];

                foreach ($empresas as $empresa) {
                    $porEmpresa[$empresa->codigo] = [
                        'nombre' => $empresa->nombre,
                        'general' => self::resumenGeneral($empresa->id),
                        'construido' => self::resumenConstruido($empresa->id),
                        'tc' => self::contarFase($empresa->id, FaseControlInterno::TC),
                        'pend_anulacion' => self::contarFase($empresa->id, FaseControlInterno::PEND_ANULACION),
                        'ind2' => null,
                        'puntaje' => null,
                    ];
                }

                $puntaje = ControlInternoPuntaje::trimestre($anio, $trimestre);
                foreach ($puntaje['empresas'] as $codigo => $datos) {
                    if (isset($porEmpresa[$codigo])) {
                        $porEmpresa[$codigo]['ind2'] = $datos['ind2'];
                        $porEmpresa[$codigo]['puntaje'] = $datos['puntaje'];
                    }
                }

                return [
                    'anio' => $anio,
                    'trimestre' => "T{$trimestre}",
                    'meta_ind2' => $puntaje['meta_ind2'],
                    'empresas' => $porEmpresa,
                    'ultima_carga' => self::ultimaCarga(),
                    'generado_en' => now()->toDateTimeString(),
                ];
            }
        );
    }

    private static function contarFase(int $empresaId, string $fase): int
    {
        return Solicitud::where('empresa_id', $empresaId)
            ->whereHas('faseControlInterno', fn ($q) => $q->where('fase', $fase))
            ->count();
    }

    /**
     * GENERAL necesita, además del total, el desglose NUEVAS/SIN RED/ROJO
     * (INICIO!C37:C39). Hasta el 13/09/2026 esto cargaba TODAS las
     * solicitudes de la fase en PHP y llamaba a para() una por una porque el
     * semáforo no vivía en ninguna columna; ahora que CI-5 lo cachea en
     * fase_control_internos.ind_semaforo, se puede contar directo en SQL
     * (4 COUNT en vez de traer miles de filas a PHP). "nuevas" tampoco
     * necesita el caché: es simplemente fecha_ingreso_general = hoy.
     */
    private static function resumenGeneral(int $empresaId): array
    {
        $base = FaseControlInterno::query()
            ->where('fase', FaseControlInterno::GENERAL)
            ->whereHas('solicitud', fn ($q) => $q->where('empresa_id', $empresaId));

        return [
            'total' => (clone $base)->count(),
            'nuevas' => (clone $base)->whereDate('fecha_ingreso_general', now()->toDateString())->count(),
            'sin_red' => (clone $base)->where('ind_semaforo', 'SIN RED')->count(),
            'rojo' => (clone $base)->where('ind_semaforo', 'ROJO')->count(),
        ];
    }

    /**
     * CONSTRUIDO solo necesita el total y cuántas no tienen fecha de fin de
     * instalación interna todavía (INICIO!C41: "no cuentan en IND 2").
     */
    private static function resumenConstruido(int $empresaId): array
    {
        $solicitudes = Solicitud::where('empresa_id', $empresaId)
            ->whereHas('faseControlInterno', fn ($q) => $q->where('fase', FaseControlInterno::CONSTRUIDO))
            ->with('instalacion')
            ->get();

        $sinFechaFin = $solicitudes->filter(
            fn (Solicitud $s) => is_null($s->instalacion?->fecha_finalizacion_instalacion_interna)
        )->count();

        return ['total' => $solicitudes->count(), 'sin_fecha_fin_interna' => $sinFechaFin];
    }

    /**
     * "BITÁCORA DE LA ÚLTIMA CARGA" del Excel (INICIO!B5:B15) — la última
     * fila de `logs` (CI-8). Dos conceptos del Excel no tienen equivalente
     * todavía y se devuelven como `null` (no se inventa un número): "Eliminadas:
     * anulación confirmada por el portal" (CI-6 no implementa ninguna
     * eliminación a propósito) e "Ignoradas: fuera de Lima/Callao" (el
     * filtro de ámbito de CI-1 no está enganchado en ProcessExcelJob).
     */
    private static function ultimaCarga(): ?array
    {
        $log = Logs::latest('id')->first();

        if (!$log) {
            return null;
        }

        $resumenCi = $log->resumen_control_interno ?? [];

        return [
            'fecha' => $log->created_at,
            'nombre_archivo' => $log->nombre_archivo,
            'estado' => $log->estado,
            'total_filas' => $log->total_filas,
            'filas_procesadas' => $log->filas_procesadas,
            'filas_con_error' => $log->filas_con_error,
            'nuevas_general' => $resumenCi['nuevas_general'] ?? null,
            'actualizadas' => $resumenCi['actualizadas'] ?? null,
            'movidas_general_construido' => $resumenCi['movidas_general_construido'] ?? null,
            'movidas_general_tc' => $resumenCi['movidas_general_tc'] ?? null,
            'movidas_construido_tc' => $resumenCi['movidas_construido_tc'] ?? null,
            'movidas_a_pend_anulacion' => $resumenCi['movidas_a_pend_anulacion'] ?? null,
            'filas_omitidas_validacion' => $resumenCi['filas_omitidas_validacion'] ?? null,
        ];
    }
}
