<?php

namespace App\Services;

use App\Models\FaseControlInterno;
use App\Models\Feriado;
use App\Models\ParametroControlInterno;
use App\Models\Solicitud;
use Illuminate\Support\Carbon;

/**
 * CI-5: indicadores calculados (equivalente a las columnas de fórmulas del
 * Excel: DESFACE, SEMÁFORO, DÍAS HÁBILES (portal), FUERA DE PLAZO (portal),
 * TRIMESTRE (portal), SEMANA (portal), NUEVO). Se calculan al vuelo, nunca se
 * guardan en columnas — si se guardaran se desactualizarían solos.
 *
 * Fórmulas reversa-ingenierizadas del VBA original (Modulo_Internas.bas, fila
 * 3 de las hojas GENERAL/CONSTRUIDO/TC/PEND_ANULACION — columnas F/X/Y/AB/AC/AD/AE),
 * decompilado con oletools para tener el detalle exacto en vez de adivinar.
 *
 * OJO: `Solicitud.fecha_aprobacion_contrato` guarda en realidad la columna del
 * portal "Fecha de suscripción de contrato" (la "E" del Excel) — ver la nota
 * en ProcessExcelJob::processSolicitud(). Es la fecha base de todos los
 * indicadores de plazo.
 */
class ControlInternoIndicadores
{
    /**
     * Indicadores de una solicitud puntual (para la pestaña "Control Interno"
     * del Detalle de Solicitud). CI-9 reutilizará este mismo cálculo para el
     * listado por fase.
     */
    public static function para(Solicitud $solicitud): array
    {
        $param = ParametroControlInterno::actual();
        $feriados = Feriado::fechas();

        $fase = $solicitud->faseControlInterno;
        $instalacion = $solicitud->instalacion;

        $faseActual = $fase->fase ?? FaseControlInterno::GENERAL;
        $suscripcion = $solicitud->fecha_aprobacion_contrato; // ver nota de la clase
        $finInterna = $instalacion?->fecha_finalizacion_instalacion_interna;

        [$desfaceDias, $desfaceLabel] = self::desface($faseActual, $suscripcion, $finInterna, $fase);
        $semaforo = self::semaforo($faseActual, $suscripcion, $desfaceDias, $param, $feriados);

        $diasHabiles = null;
        $fueraDePlazo = null;
        if ($suscripcion && $finInterna) {
            $diasHabiles = self::networkDays($suscripcion, $finInterna, $feriados) - 1;
            $fueraDePlazo = $diasHabiles > $param->plazo_construccion_dias_habiles;
        }

        return [
            'fase' => $faseActual,
            'desface_dias' => $desfaceDias,
            'desface_label' => $desfaceLabel,
            'semaforo' => $semaforo,
            'dias_habiles' => $diasHabiles,
            'fuera_de_plazo' => $fueraDePlazo,
            'trimestre' => $finInterna ? 'T' . (int) ceil($finInterna->month / 3) . '-' . $finInterna->year : null,
            'semana_inicio' => $finInterna ? $finInterna->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d') : null,
            'nuevo' => (bool) ($fase && $fase->fecha_ingreso_general && $fase->fecha_ingreso_general->isToday()),
        ];
    }

    /**
     * DESFACE: cambia de significado según la fase, igual que en el Excel
     * (cada hoja tiene su propia fórmula en la columna F).
     */
    private static function desface(string $faseActual, ?Carbon $suscripcion, ?Carbon $finInterna, ?FaseControlInterno $fase): array
    {
        return match ($faseActual) {
            FaseControlInterno::CONSTRUIDO => [
                self::diasCalendario($finInterna ?? $fase?->fecha_construccion_control, Carbon::today()),
                'días esperando TC',
            ],
            FaseControlInterno::TC => [
                self::diasCalendario($suscripcion, $fase?->fecha_tc),
                'ciclo total (suscripción → TC)',
            ],
            default => [ // GENERAL, PEND_ANULACION
                self::diasCalendario($suscripcion, Carbon::today()),
                'días desde suscripción',
            ],
        };
    }

    /**
     * SEMÁFORO: también depende de la fase — en GENERAL compara días hábiles
     * contra el plazo de construcción; en CONSTRUIDO compara el DESFACE (días
     * calendario esperando TC) contra la espera de TC; TC y PEND_ANULACION son
     * literales fijos, igual que en el Excel.
     */
    private static function semaforo(string $faseActual, ?Carbon $suscripcion, ?int $desfaceDias, ParametroControlInterno $param, array $feriados): string
    {
        if ($faseActual === FaseControlInterno::TC) {
            return 'TC';
        }

        if ($faseActual === FaseControlInterno::PEND_ANULACION) {
            return 'ANULAR';
        }

        if ($faseActual === FaseControlInterno::CONSTRUIDO) {
            if ($desfaceDias === null) {
                return '';
            }

            return match (true) {
                $desfaceDias <= $param->espera_tc_verde_dias => 'VERDE',
                $desfaceDias <= $param->espera_tc_ambar_dias => 'AMBAR',
                default => 'ROJO',
            };
        }

        // fase === GENERAL
        if (!$suscripcion) {
            return 'SIN RED';
        }

        $diasHabiles = self::networkDays($suscripcion, Carbon::today(), $feriados) - 1;

        return match (true) {
            $diasHabiles <= $param->semaforo_verde_dias => 'VERDE',
            $diasHabiles <= $param->semaforo_ambar_dias => 'AMBAR',
            default => 'ROJO',
        };
    }

    /**
     * Días calendario entre dos fechas (equivalente a TODAY()-INT(fecha) o
     * F.TC-suscripción del Excel). Null si falta cualquiera de las dos.
     */
    private static function diasCalendario(?Carbon $desde, ?Carbon $hasta): ?int
    {
        if (!$desde || !$hasta) {
            return null;
        }

        return (int) $desde->diffInDays($hasta);
    }

    /**
     * Equivalente a NETWORKDAYS(desde, hasta, feriados) de Excel: cuenta los
     * días hábiles entre dos fechas (inclusive), sin contar sábados,
     * domingos ni feriados. El VBA siempre le resta 1 después de llamarla
     * (NETWORKDAYS cuenta ambos extremos); esa resta la hace quien llama a
     * este método, no este método.
     */
    private static function networkDays(Carbon $desde, Carbon $hasta, array $feriados): int
    {
        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $dias = 0;
        $cursor = $desde->copy()->startOfDay();
        $fin = $hasta->copy()->startOfDay();

        while ($cursor->lte($fin)) {
            if (!$cursor->isWeekend() && !in_array($cursor->format('Y-m-d'), $feriados, true)) {
                $dias++;
            }
            $cursor->addDay();
        }

        return $dias;
    }
}
