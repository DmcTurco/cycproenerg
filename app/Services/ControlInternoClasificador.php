<?php

namespace App\Services;

use App\Models\FaseControlInterno;
use App\Models\Solicitud;

/**
 * CI-4: motor de clasificación, extraído a servicio para que lo puedan usar
 * tanto `ProcessExcelJob` (con los datos recién leídos de una fila del
 * portal) como `ControlInternoManualController` (al guardar F. CONSTRUCCIÓN
 * / ANULAR a mano desde el Detalle de Solicitud, para que el cambio de fase
 * y los indicadores de CI-5 se vean al toque, sin esperar la próxima carga
 * de Excel) — el Excel original tenía el mismo botón "Aplicar movimientos"
 * separado de "Cargar del portal" para este caso.
 *
 * Reglas (idénticas a las del VBA original, ver docblock de
 * ProcessExcelJob::processFaseControlInterno() para el detalle completo de
 * dónde salió cada una):
 *  1. PEND_ANULACION es terminal en este alcance (CI-6 decide cómo sale).
 *  2. marcado_para_anular (CI-3), en cualquier fase abierta -> PEND_ANULACION.
 *  3. TC es histórico cerrado: no se reclasifica con datos del portal.
 *  4. CONSTRUIDO -> TC si el portal ya reportó TC concluida.
 *  5. GENERAL -> CONSTRUIDO (o TC directo) si ya hay F. CONSTRUCCIÓN (CI-3).
 *
 * Nunca pisa las columnas manuales de CI-3: solo las lee.
 */
class ControlInternoClasificador
{
    public static function clasificar(Solicitud $solicitud, bool $tcConcluida, ?string $fechaTc = null): FaseControlInterno
    {
        $fase = FaseControlInterno::firstOrCreate(
            ['solicitud_id' => $solicitud->id],
            ['fase' => FaseControlInterno::GENERAL, 'fecha_ingreso_general' => now()->toDateString()]
        );

        if ($fase->fase === FaseControlInterno::PEND_ANULACION) {
            return $fase;
        }

        if ($fase->marcado_para_anular) {
            $fase->update(['fase' => FaseControlInterno::PEND_ANULACION]);
            return $fase;
        }

        if ($fase->fase === FaseControlInterno::TC) {
            return $fase;
        }

        if ($fase->fase === FaseControlInterno::CONSTRUIDO) {
            if ($tcConcluida) {
                $fase->update(['fase' => FaseControlInterno::TC, 'fecha_tc' => $fechaTc ?? $fase->fecha_tc]);
            }
            return $fase;
        }

        // fase === GENERAL
        if (!is_null($fase->fecha_construccion_control)) {
            $fase->update([
                'fase' => $tcConcluida ? FaseControlInterno::TC : FaseControlInterno::CONSTRUIDO,
                'fecha_tc' => $tcConcluida ? ($fechaTc ?? $fase->fecha_tc) : $fase->fecha_tc,
            ]);
        }

        return $fase->fresh();
    }
}
