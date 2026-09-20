<?php

namespace App\Services;

use App\Models\CotizacionDetalle;
use App\Models\Cotizacion;
use App\Models\Cuadrilla;
use App\Models\Ejecutado;
use App\Models\Entrega;
use App\Models\Herramienta;
use App\Models\Ingreso;
use App\Models\Material;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * CM-8: RESUMEN / Panel de control — equivalente a la hoja RESUMEN (parte
 * de arriba: indicadores generales) + la macro GenerarResumenPDF (parte de
 * abajo: corte por cuadrilla, hoja oculta RESUMEN CUADRILLA). Ninguna de
 * las dos tiene VBA para los indicadores generales (son fórmulas de hoja,
 * igual que PUNTAJE/INICIO en Control Interno), pero el corte por
 * cuadrilla SÍ es 100% macro — se leyó GenerarResumenPDF completa del
 * módulo MacrosCYC.bas en vez de adivinar, porque tiene reglas no obvias
 * (ver comentarios de corteCuadrilla()).
 */
class ControlMaterialesResumen
{
    /**
     * Indicadores generales — RESUMEN!C4:C15 del Excel. El almacén es
     * único (no hay separación por Empresa CYC/CLB en materiales ni
     * herramientas, decisión de CM-1), así que a diferencia de
     * ControlInternoDashboard esto NO se parte por empresa.
     *
     * Cacheado 5 minutos: "valor del inventario" y "ejecutado personal
     * directo" recorren todo el catálogo/ejecutados en PHP porque
     * Material::precioVigente()/Ejecutado::total() son cálculos en vivo,
     * no columnas (mismo motivo que ControlInternoDashboard).
     */
    public static function generales(): array
    {
        return Cache::remember('control_materiales_resumen_generales', now()->addMinutes(5), function () {
            $materiales = Material::all();

            $valorInventario = 0.0;
            $itemsSinStock = 0;
            $itemsPorReponer = 0;
            foreach ($materiales as $material) {
                // RESUMEN!C4: SUMPRODUCT(CATALOGO!E, CATALOGO!L) — precio
                // VIGENTE (no precio base) x stock actual, pese a que la
                // etiqueta del Excel dice "a costo": el vigente ES el
                // costo estimado actual (sube solo con el último ingreso).
                $valorInventario += $material->precioVigente() * $material->stockActual();

                $estado = $material->estado();
                if ($estado === 'SIN STOCK') {
                    $itemsSinStock++;
                } elseif ($estado === 'REPONER') {
                    $itemsPorReponer++;
                }
            }

            // RESUMEN!C8: precios que BAJARON, columna CATALOGO!Q (alerta
            // por MATERIAL, simple mayor/menor, distinta de la de INGRESOS).
            $preciosBajaron = $materiales->filter(
                fn (Material $m) => str_starts_with((string) $m->alertaPrecio(), 'BAJO')
            )->count();

            // RESUMEN!C7: alarmas de precio, columna INGRESOS!I (alerta por
            // FILA de ingreso, con umbral — distinta de la de arriba).
            $alarmasPrecioIngresos = Ingreso::all()->filter(
                fn (Ingreso $i) => str_starts_with((string) $i->alertaPrecio(), 'SUBIO')
            )->count();

            // RESUMEN!C9: SUMIFS(COTIZACIONES!D, B, "C&C*") — TODAS las
            // cotizaciones a contratistas emitidas alguna vez (cualquier
            // estado), no solo las pendientes.
            $totalValorizadoContratistas = (float) Cotizacion::where('es_vale', false)->sum('monto_sin_igv');

            // RESUMEN!C10: SUM(EJECUTADO!K) — neto (SALIDA positivo,
            // DEVOLUCION negativo, igual que Ejecutado::total()).
            $ejecutadoPersonalDirecto = Ejecutado::all()->sum(fn (Ejecutado $e) => $e->total());

            $cotizacionesPendientes = Cotizacion::where('estado', Cotizacion::ESTADO_PENDIENTE)->count();
            $montoPendienteDescuento = (float) Cotizacion::where('estado', Cotizacion::ESTADO_PENDIENTE)->sum('total_con_igv');

            // RESUMEN!C13: en el Excel es un conteo crudo (ENTREGA menos
            // DEVOLUCION en TODO el historial, sin agrupar por
            // herramienta). Acá se usa Herramienta::ubicacion() (CM-7,
            // basado en el ÚLTIMO movimiento de cada herramienta), más
            // preciso que el conteo crudo si alguna herramienta tuvo un
            // historial irregular — mismo criterio que CM-6/CM-7 de
            // preferir la fuente de verdad ya construida en vez de repetir
            // la fórmula ingenua del Excel.
            $herramientas = Herramienta::all();
            $herramientasEnCampo = $herramientas->filter(fn (Herramienta $h) => $h->ubicacion() === 'EN CAMPO')->count();
            $herramientasPerdidas = $herramientas->where('estado', 'PERDIDA')->count();
            $valorHerramientasPerdidas = (float) $herramientas->where('estado', 'PERDIDA')->sum('precio');

            return [
                'valor_inventario' => $valorInventario,
                'items_sin_stock' => $itemsSinStock,
                'items_por_reponer' => $itemsPorReponer,
                'alarmas_precio_ingresos' => $alarmasPrecioIngresos,
                'precios_bajaron' => $preciosBajaron,
                'total_valorizado_contratistas' => $totalValorizadoContratistas,
                'ejecutado_personal_directo' => $ejecutadoPersonalDirecto,
                'cotizaciones_pendientes' => $cotizacionesPendientes,
                'monto_pendiente_descuento' => $montoPendienteDescuento,
                'herramientas_en_campo' => $herramientasEnCampo,
                'herramientas_perdidas' => $herramientasPerdidas,
                'valor_herramientas_perdidas' => $valorHerramientasPerdidas,
                'generado_en' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * Registro de cuadrillas y valorizado por persona — RESUMEN!B18:K69
     * del Excel. Una fila por cuadrilla activa con sus indicadores de
     * Cuadrilla (N° retiros, total valorizado, pendiente de descuento).
     */
    public static function registroCuadrillas(): \Illuminate\Support\Collection
    {
        return Cuadrilla::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Cuadrilla $c) => [
                'cuadrilla' => $c,
                'num_retiros' => $c->numRetiros(),
                'total_valorizado' => $c->totalValorizado(),
                'pendiente_descuento' => $c->pendienteDescuento(),
            ]);
    }

    /**
     * Corte por cuadrilla — GenerarResumenPDF de la macro, sin la parte de
     * exportar a PDF (eso lo hace el controlador). Traducción directa,
     * dos formatos completamente distintos según el tipo de cuadrilla:
     *
     * CONTRATISTA: lista sus cotizaciones PENDIENTES emitidas hasta la
     * fecha de corte (sin filtro "desde" — el corte es siempre acumulado
     * hasta una fecha, igual que el Excel).
     *
     * PERSONAL DIRECTO: liquidación acumulada al $hasta, material por
     * material: retirado por vale (convertido a la unidad reportada con
     * factor_metros_por_unidad) − ejecutado − devuelto = saldo en su
     * poder. Un saldo negativo (usó más de lo que retiró con vale) NO
     * cuenta a favor: su valor es 0, aunque el saldo se muestra igual
     * para que se revise. Solo se listan materiales con algún movimiento.
     */
    public static function corteCuadrilla(Cuadrilla $cuadrilla, Carbon $hasta): array
    {
        if ($cuadrilla->tipo === Cuadrilla::TIPO_PERSONAL_DIRECTO) {
            return self::corteLiquidacion($cuadrilla, $hasta);
        }

        return self::corteCotizacionesPendientes($cuadrilla, $hasta);
    }

    private static function corteLiquidacion(Cuadrilla $cuadrilla, Carbon $hasta): array
    {
        $items = [];
        $valorTotal = 0.0;

        foreach (Material::orderBy('codigo')->get() as $material) {
            $factor = max(1.0, (float) $material->factor_metros_por_unidad);

            $retirado = (float) CotizacionDetalle::query()
                ->join('cotizaciones', 'cotizaciones.id', '=', 'cotizacion_detalles.cotizacion_id')
                ->where('cotizaciones.cuadrilla_id', $cuadrilla->id)
                ->where('cotizaciones.es_vale', true)
                ->where('cotizaciones.fecha', '<=', $hasta)
                ->whereNull('cotizaciones.deleted_at')
                ->where('cotizacion_detalles.material_id', $material->id)
                ->sum('cotizacion_detalles.cantidad') * $factor;

            $ejecutado = (float) Ejecutado::where('cuadrilla_id', $cuadrilla->id)
                ->where('material_id', $material->id)
                ->where('fecha', '<=', $hasta)
                ->where('movimiento', Ejecutado::MOVIMIENTO_SALIDA)
                ->sum('cantidad');

            $devuelto = (float) Ejecutado::where('cuadrilla_id', $cuadrilla->id)
                ->where('material_id', $material->id)
                ->where('fecha', '<=', $hasta)
                ->where('movimiento', Ejecutado::MOVIMIENTO_DEVOLUCION)
                ->sum('cantidad');

            if ($retirado == 0.0 && $ejecutado == 0.0 && $devuelto == 0.0) {
                continue;
            }

            $saldo = $retirado - $ejecutado - $devuelto;
            $costoUnitario = $material->precioVigente() / $factor;
            $valor = $saldo > 0 ? $saldo * $costoUnitario : 0.0;
            $valorTotal += $valor;

            $items[] = [
                'material' => $material,
                'unidad' => $factor > 1 ? 'MTS' : $material->unidad,
                'retirado' => $retirado,
                'ejecutado' => $ejecutado,
                'devuelto' => $devuelto,
                'saldo' => $saldo,
                'precio_unitario' => $costoUnitario,
                'valor' => $valor,
            ];
        }

        return [
            'tipo' => 'personal_directo',
            'cuadrilla' => $cuadrilla,
            'hasta' => $hasta,
            'items' => $items,
            'valor_total' => $valorTotal,
        ];
    }

    private static function corteCotizacionesPendientes(Cuadrilla $cuadrilla, Carbon $hasta): array
    {
        $cotizaciones = Cotizacion::where('cuadrilla_id', $cuadrilla->id)
            ->where('estado', Cotizacion::ESTADO_PENDIENTE)
            ->where('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->get();

        return [
            'tipo' => 'contratista',
            'cuadrilla' => $cuadrilla,
            'hasta' => $hasta,
            'cotizaciones' => $cotizaciones,
            'totales' => [
                'monto_sin_igv' => (float) $cotizaciones->sum('monto_sin_igv'),
                'igv' => (float) $cotizaciones->sum('igv'),
                'total_con_igv' => (float) $cotizaciones->sum('total_con_igv'),
            ],
        ];
    }

    /**
     * "Marcar las N cotizaciones pendientes como DESCONTADO EN
     * VALORIZACION?" de la macro (solo CONTRATISTA — un vale de personal
     * directo nunca pasa por acá). A diferencia del Excel, que pisaba la
     * columna OBSERVACION, acá se ANEXA (mismo criterio que
     * Cotizacion::emitir() al corregir): nunca se pierde una nota previa.
     *
     * Devuelve cuántas cotizaciones se marcaron.
     */
    public static function cerrarCorteContratista(Cuadrilla $cuadrilla, Carbon $hasta, string $nValorizacion, ?Carbon $desde = null): int
    {
        $cotizaciones = Cotizacion::where('cuadrilla_id', $cuadrilla->id)
            ->where('estado', Cotizacion::ESTADO_PENDIENTE)
            ->where('fecha', '<=', $hasta)
            ->get();

        $notaCorte = $desde
            ? 'Corte ' . $desde->format('d/m') . '-' . $hasta->format('d/m/Y')
            : 'Corte hasta ' . $hasta->format('d/m/Y');

        foreach ($cotizaciones as $cotizacion) {
            $cotizacion->update([
                'estado' => Cotizacion::ESTADO_DESCONTADO,
                'n_valorizacion' => $nValorizacion,
                'observacion' => trim(($cotizacion->observacion ? $cotizacion->observacion . "\n" : '') . $notaCorte),
            ]);
        }

        return $cotizaciones->count();
    }
}
