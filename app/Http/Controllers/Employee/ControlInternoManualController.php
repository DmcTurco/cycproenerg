<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FaseControlInterno;
use App\Models\Solicitud;
use App\Services\ControlInternoClasificador;
use App\Services\ControlInternoIndicadores;
use Illuminate\Http\Request;

/**
 * CI-3: columnas "amarillas" del Excel (F. CONSTRUCCIÓN control, OBSERVACIÓN,
 * ANULAR) — las llena el staff a mano desde el detalle de la solicitud, nunca
 * la carga del portal (ProcessExcelJob).
 * CI-5: además devuelve los indicadores calculados (DESFACE, SEMÁFORO, etc.)
 * de esta misma solicitud, vía ControlInternoIndicadores.
 */
class ControlInternoManualController extends Controller
{
    public function show(Solicitud $solicitud)
    {
        $fase = $solicitud->faseControlInterno;

        return response()->json([
            'fase' => $fase->fase ?? FaseControlInterno::GENERAL,
            'fecha_ingreso_general' => optional($fase?->fecha_ingreso_general)->format('d/m/Y'),
            'fecha_construccion_control' => optional($fase?->fecha_construccion_control)->format('Y-m-d'),
            'observacion_control' => $fase->observacion_control ?? '',
            'marcado_para_anular' => (bool) ($fase->marcado_para_anular ?? false),
            // CI-5 (13/09/2026): calcularYGuardar() en vez de para() — este
            // detalle siempre debe mostrar el dato fresco (nunca el caché de
            // paraAlmacenado()), y de paso refresca el caché para el listado
            // cada vez que el staff abre esta pantalla.
            'indicadores' => ControlInternoIndicadores::calcularYGuardar($solicitud),
        ]);
    }

    public function update(Request $request, Solicitud $solicitud)
    {
        $data = $request->validate([
            'fecha_construccion_control' => 'nullable|date',
            'observacion_control' => 'nullable|string|max:1000',
            'marcado_para_anular' => 'boolean',
        ]);

        // La solicitud debería tener siempre su fila de fase (ProcessExcelJob
        // la crea en GENERAL al importar), pero por si es una solicitud
        // anterior a CI-2, la creamos aquí en vez de fallar.
        $fase = FaseControlInterno::firstOrCreate(
            ['solicitud_id' => $solicitud->id],
            ['fase' => FaseControlInterno::GENERAL, 'fecha_ingreso_general' => now()->toDateString()]
        );

        $fase->update($data);

        // CI-4: reclasificar de inmediato con lo que ya está guardado (el
        // resultado de TC de la última carga de Excel), sin esperar la
        // próxima carga — igual que el botón "Aplicar movimientos" del VBA,
        // pero automático al guardar. Así, si el staff recién marcó F.
        // CONSTRUCCIÓN, ve el cambio de fase (y los indicadores de CI-5) al
        // toque en vez de que quede desactualizado hasta el próximo Excel.
        $resultadoTc = strtoupper(trim((string) ($solicitud->instalacion?->resultado_instalacion_tc ?? '')));
        ControlInternoClasificador::clasificar($solicitud, $resultadoTc === 'CONCLUIDA');

        return response()->json([
            'success' => true,
            'message' => 'Control Interno actualizado.',
            // CI-5 (13/09/2026): calcularYGuardar() en vez de para() — guarda
            // el caché ya con el cambio recién hecho, para que el listado
            // (CI-9) no muestre datos viejos hasta el comando diario.
            'indicadores' => ControlInternoIndicadores::calcularYGuardar($solicitud->fresh()),
        ]);
    }
}
