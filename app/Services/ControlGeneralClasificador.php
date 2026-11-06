<?php

namespace App\Services;

use App\Models\ControlGeneral;
use App\Models\ParametroControlGeneral;
use App\Models\Solicitud;

/**
 * Motor de etapas de Control General, traducido de la macro
 * `ActualizarControl` / `MoverMarcadas` del Excel "Control General CLB -
 * C&C.xlsm" (ver docs/modulos/control-general.md).
 *
 * Dos entradas:
 *  - aplicarMovimientos(): lo que mueve la usuaria a mano (Construida = Sí,
 *    Anular/Reingresar, TC Concluida, Habilitado = Sí). En el Excel pasaba
 *    al correr la macro; acá pasa al guardar el cambio.
 *  - aplicarPortal(): lo que dice la carga del Excel del portal (lo llama
 *    ProcessExcelJob por cada fila).
 *
 * Regla "manda el portal" (MergeVal del VBA): en los datos que trae el
 * portal y ella puede corregir (resultado de TC, observado), si el portal
 * trae un valor DISTINTO al de la carga anterior, gana el portal; si trae
 * el mismo, se respeta lo que ella marcó. Lo que solo escribe ella
 * (observación, asesor, cuadrilla, técnico, trámite...) nunca se pisa.
 */
class ControlGeneralClasificador
{
    /**
     * Movimientos por marca manual. Devuelve la etapa nueva si se movió, o
     * null si se quedó donde estaba. Sigue el orden de la macro: primero
     * Anular/Reingresar, después Construida, TC Concluida y Habilitado (una
     * misma fila puede avanzar más de una etapa si ya tiene todo marcado).
     */
    public static function aplicarMovimientos(ControlGeneral $cg): ?string
    {
        $etapaInicial = $cg->etapa;

        for ($i = 0; $i < 5; $i++) {
            if (!self::unMovimiento($cg)) {
                break;
            }
        }

        if ($cg->isDirty()) {
            $cg->save();
        }

        return $cg->etapa !== $etapaInicial ? $cg->etapa : null;
    }

    private static function unMovimiento(ControlGeneral $cg): bool
    {
        switch ($cg->etapa) {
            case ControlGeneral::APROBADA:
                if (in_array($cg->accion, ControlGeneral::ACCIONES, true) && !$cg->construida) {
                    self::mover($cg, ControlGeneral::ANULACION);
                    $cg->fecha_marca = $cg->fecha_marca ?? now()->toDateString();
                    $cg->estado_tramite = $cg->estado_tramite ?? 'Pendiente';
                    return true;
                }
                if ($cg->construida) {
                    self::mover($cg, ControlGeneral::TC_CONSTRUIDA);
                    return true;
                }
                return false;

            case ControlGeneral::ANULACION:
                // "Si borras la Acción de una fila, vuelve a 1-Aprobadas."
                if (!in_array($cg->accion, ControlGeneral::ACCIONES, true)) {
                    self::mover($cg, ControlGeneral::APROBADA);
                    $cg->accion = null;
                    $cg->construida = false;
                    $cg->fecha_marca = null;
                    $cg->estado_tramite = null;
                    return true;
                }
                return false;

            case ControlGeneral::TC_CONSTRUIDA:
                if ($cg->resultado_tc === 'Concluida') {
                    self::entrarAHabilitacion($cg);
                    return true;
                }
                return false;

            case ControlGeneral::HABILITADA:
                // Deshacer: si se marcó Habilitado por error y se quita la
                // marca, vuelve a 3-Habilitación (en el Excel no había vuelta).
                if (!$cg->habilitado) {
                    self::mover($cg, ControlGeneral::HABILITACION);
                    $cg->fecha_habilitacion = null;
                    return true;
                }
                return false;

            case ControlGeneral::HABILITACION:
                if ($cg->habilitado) {
                    self::mover($cg, ControlGeneral::HABILITADA);
                    $cg->fecha_habilitacion = $cg->fecha_habilitacion ?? now()->toDateString();
                    return true;
                }
                return false;
        }

        return false;
    }

    /**
     * Aplica una fila del Excel del portal. `$p` trae:
     *  provincia, estado (código, ej. "08.1"), resultado_tc,
     *  fecha_suscripcion, fecha_entrega_documentos (Y-m-d o null),
     *  anulada, rechazada (bool).
     *
     * Devuelve ['antes' => etapa|null, 'despues' => etapa] o null si la
     * solicitud no entra (o no está) en Control General.
     */
    public static function aplicarPortal(Solicitud $solicitud, array $p, ?array $ambito = null): ?array
    {
        $provincia = mb_strtoupper(trim((string) ($p['provincia'] ?? '')), 'UTF-8');
        $ambito = self::ambito($ambito);

        // El VBA ignora del todo las filas fuera de Lima/Callao (ni las
        // agrega ni las actualiza).
        if (!in_array($provincia, $ambito, true)) {
            return null;
        }

        // Las que el portal marca Anulada o Rechazada no están en las
        // descargas con las que trabaja la usuaria (en su Excel no hay
        // ninguna): ni entran ni se actualizan.
        if (!empty($p['anulada']) || !empty($p['rechazada'])) {
            return null;
        }

        $estado = trim((string) ($p['estado'] ?? ''));
        $pref = substr($estado, 0, 2);
        $tcPortal = self::texto($p['resultado_tc'] ?? null);
        $observadoPortal = self::esObservado($estado);
        $fechaAprobacion = ($p['fecha_suscripcion'] ?? null) ?: (($p['fecha_entrega_documentos'] ?? null) ?: null);

        $cg = ControlGeneral::where('solicitud_id', $solicitud->id)->first();

        if (!$cg) {
            $etapa = match (true) {
                $tcPortal === 'Concluida' && $pref !== '02' => ControlGeneral::HABILITACION,
                in_array($pref, ['06', '08'], true) => ControlGeneral::TC_CONSTRUIDA,
                $pref === '02' => ControlGeneral::APROBADA,
                default => null,
            };

            if (!$etapa) {
                return null;
            }

            ControlGeneral::create([
                'solicitud_id' => $solicitud->id,
                'etapa' => $etapa,
                'fecha_ingreso_etapa' => now()->toDateString(),
                'fecha_aprobacion' => $fechaAprobacion,
                'resultado_tc' => $tcPortal,
                'resultado_tc_portal' => $tcPortal,
                'observado' => $observadoPortal,
                'observado_portal' => $observadoPortal,
            ]);

            return ['antes' => null, 'despues' => $etapa];
        }

        $antes = $cg->etapa;

        // Por si quedó algo marcado sin mover (la macro hace esto primero).
        self::aplicarMovimientos($cg);

        if ($fechaAprobacion) {
            $cg->fecha_aprobacion = $fechaAprobacion;
        }

        if (in_array($cg->etapa, [ControlGeneral::ANULACION, ControlGeneral::HABILITADA], true)) {
            // En 4 y 5 solo se actualizan los datos comunes (viven en las
            // tablas del portal), la etapa no se toca.
            self::guardarSnapshots($cg, $tcPortal, $observadoPortal);
            return ['antes' => $antes, 'despues' => $cg->etapa];
        }

        $tcActual = $cg->etapa === ControlGeneral::TC_CONSTRUIDA
            ? self::merge($cg->resultado_tc, $tcPortal, $cg->resultado_tc_portal)
            : $tcPortal;

        if ($cg->etapa === ControlGeneral::HABILITACION) {
            if ($pref === '02') {
                // Volvió al estado 02: no corresponde a habilitación.
                self::mover($cg, ControlGeneral::APROBADA);
                $cg->construida = false;
            } else {
                $cg->observado = (bool) self::merge($cg->observado, $observadoPortal, $cg->observado_portal);
            }
        } elseif ($tcActual === 'Concluida' && $pref !== '02') {
            $cg->resultado_tc = $tcActual;
            self::entrarAHabilitacion($cg, $observadoPortal);
        } elseif ($cg->etapa === ControlGeneral::TC_CONSTRUIDA) {
            $cg->resultado_tc = $tcActual;
        } elseif ($cg->etapa === ControlGeneral::APROBADA && in_array($pref, ['06', '08'], true)) {
            self::mover($cg, ControlGeneral::TC_CONSTRUIDA);
            $cg->resultado_tc = $tcPortal;
        }

        self::guardarSnapshots($cg, $tcPortal, $observadoPortal);

        return ['antes' => $antes, 'despues' => $cg->etapa];
    }

    /**
     * Provincias que entran al control (Lima y Callao, parámetro de Control
     * Interno). ProcessExcelJob lo resuelve una vez por carga y lo pasa.
     */
    public static function ambito(?array $ambito = null): array
    {
        $ambito ??= ParametroControlGeneral::actual()->ambito_departamentos ?: ['LIMA', 'CALLAO'];

        return array_map(fn ($d) => mb_strtoupper(trim($d), 'UTF-8'), $ambito);
    }

    /**
     * Estados 08.1 / 10.1 del portal = expediente observado (ReadPortal del VBA).
     */
    public static function esObservado(string $codigoEstado): bool
    {
        return str_starts_with($codigoEstado, '08.1') || str_starts_with($codigoEstado, '10.1');
    }

    private static function entrarAHabilitacion(ControlGeneral $cg, ?bool $observadoPortal = null): void
    {
        self::mover($cg, ControlGeneral::HABILITACION);
        $cg->habilitado = false;
        $cg->observado = (bool) ($observadoPortal ?? $cg->observado_portal ?? false);
    }

    private static function mover(ControlGeneral $cg, string $etapa): void
    {
        $cg->etapa = $etapa;
        $cg->fecha_ingreso_etapa = now()->toDateString();
    }

    private static function guardarSnapshots(ControlGeneral $cg, ?string $tcPortal, bool $observadoPortal): void
    {
        $cg->resultado_tc_portal = $tcPortal;
        $cg->observado_portal = $observadoPortal;
        $cg->save();
    }

    /**
     * MergeVal del VBA. Si ya había una "foto" del portal: gana el portal
     * solo cuando cambió respecto a esa foto. Sin foto: se respeta lo que
     * ya había escrito (si no estaba vacío).
     */
    public static function merge($actual, $nuevoPortal, $anteriorPortal)
    {
        if (!is_null($anteriorPortal)) {
            return self::igual($nuevoPortal, $anteriorPortal) ? $actual : $nuevoPortal;
        }

        return (is_null($actual) || $actual === '') ? $nuevoPortal : $actual;
    }

    private static function igual($a, $b): bool
    {
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }

        return trim((string) $a) === trim((string) $b);
    }

    private static function texto($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
