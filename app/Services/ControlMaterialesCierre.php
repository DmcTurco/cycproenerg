<?php

namespace App\Services;

use App\Models\CierreMaterial;
use App\Models\Cotizacion;
use App\Models\PersonaCampo;
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
     *   2. Por cada Material: el stock actual y el precio vigente se
     *      fotografían ANTES de archivar. Después de archivar se fija
     *      stock_inicial = stock fotografiado − ingresos() + salidas() de lo
     *      que NO se archivó (cotizaciones PENDIENTE o SIN REGISTRO,
     *      documentos posteriores a la fecha de cierre). Así lo que sigue
     *      vigente se vuelve a restar una sola vez y el stock actual no
     *      cambia por cerrar el mes. precio_base = MAX(precio_base,
     *      precio vigente fotografiado) (consolidar solo si sube).
     *   3. Archivar (marcar cierre_materiales_id + soft-delete):
     *      - Ingreso con fecha <= fecha_cierre, todavía sin cierre.
     *      - Ejecutado con fecha <= fecha_cierre, todavía sin cierre.
     *      - Cotizacion con estado distinto de PENDIENTE y de SIN REGISTRO
     *        (o sea, TODOS los vales de personal directo y las cotizaciones
     *        de contratista ya DESCONTADAS) con fecha <= fecha_cierre,
     *        todavía sin cierre.
     *   Las PENDIENTE y las SIN REGISTRO no se archivan (siguen visibles
     *   para cobrar/confirmar) y siguen restando en Material::salidas();
     *   por eso el stock_inicial del paso 2 las vuelve a sumar una vez,
     *   evitando el doble descuento.
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

            // Foto previa: el precio vigente depende de los ingresos, que se
            // archivan abajo.
            $materiales = Material::all();
            $antes = $materiales->mapWithKeys(fn (Material $m) => [$m->id => [
                'stock' => $m->stockActual(),
                'precio_vigente' => $m->precioVigente(),
            ]]);

            self::archivar(Ingreso::query(), $fechaCierre, $cierre->id);
            self::archivar(Ejecutado::query(), $fechaCierre, $cierre->id);
            self::archivar(
                Cotizacion::query()->whereNotIn('estado', [
                    Cotizacion::ESTADO_PENDIENTE,
                    Cotizacion::ESTADO_SIN_REGISTRO,
                ]),
                $fechaCierre,
                $cierre->id
            );

            foreach ($materiales as $material) {
                // Lo que quedó vigente tras archivar (PENDIENTE, SIN REGISTRO
                // o posterior al cierre) sigue sumando/restando: se compensa
                // para que stockActual() quede igual que antes de cerrar.
                $material->update([
                    'stock_inicial' => $antes[$material->id]['stock'] - $material->ingresos() + $material->salidas(),
                    'precio_base' => max((float) $material->precio_base, $antes[$material->id]['precio_vigente']),
                ]);
            }

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
