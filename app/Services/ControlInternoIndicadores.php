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
 * TRIMESTRE (portal), SEMANA (portal), NUEVO).
 *
 * Fórmulas reversa-ingenierizadas del VBA original (Modulo_Internas.bas, fila
 * 3 de las hojas GENERAL/CONSTRUIDO/TC/PEND_ANULACION — columnas F/X/Y/AB/AC/AD/AE),
 * decompilado con oletools para tener el detalle exacto en vez de adivinar.
 *
 * OJO: `Solicitud.fecha_aprobacion_contrato` guarda en realidad la columna del
 * portal "Fecha de suscripción de contrato" (la "E" del Excel) — ver la nota
 * en ProcessExcelJob::processSolicitud(). Es la fecha base de todos los
 * indicadores de plazo.
 *
 * Modelo híbrido (13/09/2026, a pedido de Turco — ver docs/modulos/control-interno.md,
 * sección 6): se probó primero 100% en vivo, nunca guardado. Cuando se vio que
 * Resumen/Puntaje además de calcular tenían que recorrer en PHP TODAS las
 * solicitudes de una fase (no había forma de usar COUNT/GROUP BY de SQL sobre
 * un valor que no vive en ninguna columna), se decidió agregar un caché:
 *   - `para()` (de abajo): el cálculo real, SIEMPRE en vivo, sin tocar caché
 *     ni base de datos más que para leer. Es la única fuente de verdad.
 *   - `calcularYGuardar()`: llama a para() y además persiste los 4 campos
 *     "pesados" (semaforo, desface_dias, dias_habiles, fuera_de_plazo) en
 *     fase_control_internos.ind_*. La usan ProcessExcelJob (cada fila, al
 *     terminar de procesarla), ControlInternoManualController (cada vez que
 *     el staff ve o edita el detalle CI-3, así esa pantalla es SIEMPRE
 *     fresca Y de paso refresca el caché) y el comando
 *     `control-interno:recalcular-indicadores`, programado a diario porque
 *     DESFACE/SEMÁFORO de GENERAL y CONSTRUIDO comparan contra "hoy" y se
 *     desactualizan solos aunque nada cambie en la solicitud.
 *   - `paraAlmacenado()`: NO calcula nada, solo lee lo último guardado por
 *     calcularYGuardar() (más lo que se puede reconstruir gratis desde
 *     relaciones ya cargadas, sin ir a la base de datos). La usan las
 *     pantallas de listado/reporte (CI-9, CI-10) que muestran muchas
 *     solicitudes a la vez, donde antes se llamaba a para() una por una.
 *     Puede estar desactualizado hasta 1 día (el peor caso: nada volvió a
 *     tocar esa solicitud desde la última carga y todavía no corrió el
 *     comando diario) — aceptado a propósito por Turco a cambio de no
 *     recorrer todas las solicitudes en PHP en cada request.
 */
class ControlInternoIndicadores
{
    /**
     * Memoizados durante el ciclo de vida de la petición (no entre
     * peticiones distintas): `para()` se llama una vez por solicitud, y en
     * el Resumen/Puntaje eso son miles de llamadas en una sola petición.
     * Sin esto, cada llamada disparaba 2 consultas más a la base de datos
     * (`ParametroControlInterno::actual()` y `Feriado::fechas()`) que
     * siempre devuelven lo mismo dentro de la misma petición — encontrado
     * el 13/09/2026 revisando por qué el Resumen seguía pesado después de
     * arreglar el cálculo de días hábiles (ver docs/modulos/control-interno.md,
     * sección 5, punto 10): ese fix resolvió el cálculo en sí, pero no esta
     * repetición de consultas, que para ~1200 solicitudes son ~2400
     * consultas de más.
     */
    private static ?ParametroControlInterno $parametroCache = null;
    private static ?array $feriadosCache = null;

    /**
     * Indicadores de una solicitud puntual (para la pestaña "Control Interno"
     * del Detalle de Solicitud). CI-9 reutilizará este mismo cálculo para el
     * listado por fase.
     */
    public static function para(Solicitud $solicitud): array
    {
        $param = self::$parametroCache ??= ParametroControlInterno::actual();
        $feriados = self::$feriadosCache ??= Feriado::fechas();

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
     * Calcula SIEMPRE en vivo y además persiste el resultado en
     * fase_control_internos.ind_* (ver el docblock de la clase). Devuelve lo
     * mismo que para() — quien ya llamaba a para() puede cambiar a este
     * método sin tocar nada más.
     */
    public static function calcularYGuardar(Solicitud $solicitud): array
    {
        $resultado = self::para($solicitud);

        $fase = $solicitud->faseControlInterno;
        if ($fase) {
            $fase->forceFill([
                'ind_semaforo' => $resultado['semaforo'],
                'ind_desface_dias' => $resultado['desface_dias'],
                'ind_dias_habiles' => $resultado['dias_habiles'],
                'ind_fuera_de_plazo' => $resultado['fuera_de_plazo'],
                'ind_actualizado_en' => now(),
            ])->save();
        }

        return $resultado;
    }

    /**
     * Lee el último caché guardado por calcularYGuardar() en vez de volver a
     * calcular — para listados/reportes que muestran muchas solicitudes a la
     * vez (ver el docblock de la clase para el trade-off de frescura). No
     * dispara consultas nuevas: espera que `faseControlInterno` e
     * `instalacion` ya vengan cargadas (eager load) en $solicitud.
     */
    public static function paraAlmacenado(Solicitud $solicitud): array
    {
        $fase = $solicitud->faseControlInterno;
        $instalacion = $solicitud->instalacion;
        $faseActual = $fase->fase ?? FaseControlInterno::GENERAL;
        $finInterna = $instalacion?->fecha_finalizacion_instalacion_interna;

        return [
            'fase' => $faseActual,
            'desface_dias' => $fase->ind_desface_dias ?? null,
            'desface_label' => self::desfaceLabel($faseActual),
            'semaforo' => $fase->ind_semaforo ?? '',
            'dias_habiles' => $fase->ind_dias_habiles ?? null,
            'fuera_de_plazo' => $fase->ind_fuera_de_plazo ?? null,
            'trimestre' => $finInterna ? 'T' . (int) ceil($finInterna->month / 3) . '-' . $finInterna->year : null,
            'semana_inicio' => $finInterna ? $finInterna->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d') : null,
            'nuevo' => (bool) ($fase && $fase->fecha_ingreso_general && $fase->fecha_ingreso_general->isToday()),
            'indicadores_actualizados_en' => optional($fase?->ind_actualizado_en)->toDateTimeString(),
        ];
    }

    /**
     * DESFACE: cambia de significado según la fase, igual que en el Excel
     * (cada hoja tiene su propia fórmula en la columna F).
     */
    private static function desface(string $faseActual, ?Carbon $suscripcion, ?Carbon $finInterna, ?FaseControlInterno $fase): array
    {
        $dias = match ($faseActual) {
            FaseControlInterno::CONSTRUIDO => self::diasCalendario($finInterna ?? $fase?->fecha_construccion_control, Carbon::today()),
            FaseControlInterno::TC => self::diasCalendario($suscripcion, $fase?->fecha_tc),
            default => self::diasCalendario($suscripcion, Carbon::today()), // GENERAL, PEND_ANULACION
        };

        return [$dias, self::desfaceLabel($faseActual)];
    }

    /**
     * Solo el texto explicativo de DESFACE, separado de desface() para que
     * paraAlmacenado() lo pueda reconstruir gratis (sin ir a la base de
     * datos) a partir únicamente de la fase.
     */
    private static function desfaceLabel(string $faseActual): string
    {
        return match ($faseActual) {
            FaseControlInterno::CONSTRUIDO => 'días esperando TC',
            FaseControlInterno::TC => 'ciclo total (suscripción → TC)',
            default => 'días desde suscripción', // GENERAL, PEND_ANULACION
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
     *
     * Bug de performance encontrado el 13/09/2026 (Turco tuvo un "Maximum
     * execution time exceeded" en CarbonInterval al cargar el Resumen con
     * datos reales): la versión anterior recorría día por día entre `desde`
     * y `hasta` con un `while` — con miles de solicitudes y fechas de
     * suscripción viejas (años de rango), esto podía sumar millones de
     * iteraciones. Se reemplazó por una fórmula matemática (semanas
     * completas × 5 + los, como máximo, 6 días sobrantes) que no depende del
     * tamaño del rango, y los feriados (una lista corta, ~16 por año) se
     * restan aparte en vez de compararse día por día.
     */
    private static function networkDays(Carbon $desde, Carbon $hasta, array $feriados): int
    {
        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->startOfDay();

        $totalDias = (int) $desde->diffInDays($hasta) + 1; // ambos extremos inclusive
        $semanasCompletas = intdiv($totalDias, 7);
        $sobrantes = $totalDias % 7;

        $diasHabiles = $semanasCompletas * 5;

        // Los días sobrantes son, como máximo, 6 — se recorren uno por uno
        // (nunca todo el rango) tomando los últimos $sobrantes días, porque
        // cualquier bloque de 7 días consecutivos completos ya aporta
        // exactamente 5 días hábiles sin importar en qué día de la semana
        // empiece.
        if ($sobrantes > 0) {
            $cursor = $hasta->copy()->subDays($sobrantes - 1);
            for ($i = 0; $i < $sobrantes; $i++) {
                if (!$cursor->isWeekend()) {
                    $diasHabiles++;
                }
                $cursor->addDay();
            }
        }

        // Restar los feriados que caen dentro del rango y en día hábil (los
        // que caen en fin de semana no se habían contado, así que no se
        // vuelven a restar).
        foreach ($feriados as $feriado) {
            $fechaFeriado = Carbon::parse($feriado)->startOfDay();
            if ($fechaFeriado->between($desde, $hasta) && !$fechaFeriado->isWeekend()) {
                $diasHabiles--;
            }
        }

        return $diasHabiles;
    }
}
