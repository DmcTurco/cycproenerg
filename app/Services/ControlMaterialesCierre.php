<?php

namespace App\Services;

use App\Models\CierreMaterial;
use App\Models\Cotizacion;
use App\Models\Cuadrilla;
use App\Models\Ejecutado;
use App\Models\Herramienta;
use App\Models\Ingreso;
use App\Models\Material;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * CM-11: Cierre de mes — traducción de la macro CerrarMes de
 * MacrosCYC.bas, con una diferencia deliberada acordada con Turco
 * (22/09/2026): en vez de BORRAR Ingresos/Ejecutado/vales/cotizaciones ya
 * descontadas (como hace el Excel), se ARCHIVAN (soft-delete + la FK
 * `cierre_materiales_id`) — se conservan para siempre en la base de
 * datos, solo dejan de contar en el kardex/saldos del mes nuevo.
 *
 * Ver create_cierres_materiales_table.php para el detalle completo de la
 * comparación con la macro original.
 */
class ControlMaterialesCierre
{
    /**
     * Ejecuta el cierre. Devuelve el CierreMaterial creado.
     *
     * Pasos (todo dentro de una transacción):
     *   1. Snapshot del estado actual (indicadores generales + registro de
     *      cuadrillas + catálogo completo) ANTES de tocar nada — queda
     *      guardado en `resumen` aunque después se archiven los
     *      movimientos que lo alimentaron.
     *   2. Por cada Material: stock_inicial = stockActual() (equivalente a
     *      CATALOGO!I = CATALOGO!L de la macro); precio_base = MAX(precio_base,
     *      precioVigente()) (consolidar solo si sube, igual que la macro).
     *   3. Archivar (marcar cierre_materiales_id + soft-delete):
     *      - Ingreso con fecha <= fecha_cierre, todavía sin cierre.
     *      - Ejecutado con fecha <= fecha_cierre, todavía sin cierre.
     *      - Cotizacion con estado <> PENDIENTE (o sea, TODOS los vales de
     *        personal directo — nunca quedan PENDIENTE — y las cotizaciones
     *        de contratista ya DESCONTADAS) con fecha <= fecha_cierre,
     *        todavía sin cierre.
     *   A diferencia de la macro, NO se toca CotizacionDetalle ni se
     *   "compensa" nada: como las cotizaciones PENDIENTES no se tocan,
     *   Material::salidas() sigue contándolas exactamente igual que antes
     *   del cierre — no hay doble descuento que corregir.
     */
    public static function cerrar(string $etiqueta, Carbon $fechaCierre, ?int $employeeId): CierreMaterial
    {
        return DB::transaction(function () use ($etiqueta, $fechaCierre, $employeeId) {
            $resumen = self::snapshot();

            $cierre = CierreMaterial::create([
                'etiqueta' => $etiqueta,
                'fecha_cierre' => $fechaCierre->toDateString(),
                'employee_id' => $employeeId,
                'resumen' => $resumen,
            ]);

            foreach (Material::all() as $material) {
                $material->update([
                    'stock_inicial' => $material->stockActual(),
                    'precio_base' => max((float) $material->precio_base, $material->precioVigente()),
                ]);
            }

            self::archivar(Ingreso::query(), $fechaCierre, $cierre->id);
            self::archivar(Ejecutado::query(), $fechaCierre, $cierre->id);
            self::archivar(
                Cotizacion::query()->where('estado', '<>', Cotizacion::ESTADO_PENDIENTE),
                $fechaCierre,
                $cierre->id
            );

            return $cierre;
        });
    }

    /**
     * Marca con `cierre_materiales_id` y hace soft-delete de las filas de
     * $query con fecha <= $fechaCierre que todavía no pertenecen a ningún
     * cierre. Dos UPDATE en bloque (por ids), sin cargar los modelos uno
     * por uno.
     */
    private static function archivar(Builder $query, Carbon $fechaCierre, int $cierreId): void
    {
        $ids = $query->whereNull('cierre_materiales_id')
            ->where('fecha', '<=', $fechaCierre)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $modelo = $query->getModel();
        $modelo::whereIn('id', $ids)->update(['cierre_materiales_id' => $cierreId]);
        $modelo::whereIn('id', $ids)->delete();
    }

    /**
     * Snapshot completo del estado actual, tomado antes de archivar nada.
     * Reutiliza ControlMaterialesResumen (CM-8) para los indicadores
     * generales y el registro de cuadrillas, en vez de duplicar esa
     * lógica.
     */
    private static function snapshot(): array
    {
        return [
            'generado_en' => now()->toDateTimeString(),
            'generales' => ControlMaterialesResumen::generales(),
            'cuadrillas' => ControlMaterialesResumen::registroCuadrillas()->map(fn (array $fila) => [
                'nombre' => $fila['cuadrilla']->nombre,
                'tipo' => $fila['cuadrilla']->tipo,
                'num_retiros' => $fila['num_retiros'],
                'total_valorizado' => $fila['total_valorizado'],
                'pendiente_descuento' => $fila['pendiente_descuento'],
            ])->values()->all(),
            'materiales' => Material::orderBy('codigo')->get()->map(fn (Material $m) => [
                'codigo' => $m->codigo,
                'descripcion' => $m->descripcion,
                'unidad' => $m->unidad,
                'stock_inicial_anterior' => (float) $m->stock_inicial,
                'ingresos_periodo' => $m->ingresos(),
                'salidas_periodo' => $m->salidas(),
                'stock_actual_final' => $m->stockActual(),
                'precio_base_anterior' => (float) $m->precio_base,
                'precio_vigente_final' => $m->precioVigente(),
            ])->values()->all(),
            'herramientas' => Herramienta::orderBy('codigo')->get()->map(fn (Herramienta $h) => [
                'codigo' => $h->codigo,
                'descripcion' => $h->descripcion,
                'ubicacion' => $h->ubicacion(),
                'responsable' => $h->responsableActual(),
            ])->values()->all(),
        ];
    }
}
