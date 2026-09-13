<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\FaseControlInterno;
use App\Models\ParametroControlInterno;
use App\Models\Solicitud;
use Illuminate\Support\Carbon;

/**
 * CI-7: IND 2 (cumplimiento de plazo de construcción FISE) y puntaje
 * trimestral — equivalente a la hoja PUNTAJE del Excel original.
 *
 * A diferencia de CI-4 (que sí tenía lógica en VBA), PUNTAJE es 100% fórmulas
 * de hoja de cálculo — se leyeron directamente del archivo original
 * (CONTROL INTERNAS - CYC CLB v5.4.xlsm, hoja PUNTAJE, filas 8-25) en vez de
 * adivinar la definición de IND 2. La fórmula real, por empresa (CYC/CLB) y
 * por cada uno de los 3 meses del trimestre:
 *   - Cuenta solicitudes con Usuario FISE = "Sí" (Solicitante.usuario_fise),
 *     que ya llegaron a CONSTRUIDO o TC (fase_control_internos.fase — el
 *     Excel las cuenta sumando las hojas CONSTRUIDO + TC), categoría
 *     Residencial o Comercio (Proyecto.categoria_proyecto mapeado a RES/COM
 *     vía config('const.control_interno.categorias') — las Multifamiliares
 *     NO cuentan, "FP: 5" en el Excel), con "Fecha de finalización de la
 *     Instalación Interna" (Instalacion) dentro de ese mes.
 *   - FUERA DE PLAZO = las de ese grupo cuyo indicador FUERA DE PLAZO
 *     (CI-5, columna AC del Excel: DÍAS HÁBILES > plazo de PARAM) es "SÍ".
 *   - IND 2 = en plazo / total FISE construidas ese período.
 *   - PUNTAJE = IND2 × 5 (máximo 5 puntos, fórmula literal de PUNTAJE!G8/G9).
 *     El "meta_ind2" de CI-1 (95%) NO es parte de esta fórmula — es un valor
 *     de referencia aparte para comparar contra el IND2 obtenido, no un
 *     factor multiplicador.
 *
 * Sin tabla propia (reporte de solo lectura, como sugiere el roadmap):
 * calcula todo al vuelo reutilizando ControlInternoIndicadores::para() por
 * solicitud (mismo cálculo ya probado de DÍAS HÁBILES/FUERA DE PLAZO de
 * CI-5, en vez de duplicar el NETWORKDAYS).
 */
class ControlInternoPuntaje
{
    private const FASES_QUE_CUENTAN = [FaseControlInterno::CONSTRUIDO, FaseControlInterno::TC];

    public static function trimestre(int $anio, int $trimestre): array
    {
        $param = ParametroControlInterno::actual();
        $categoriasMap = config('const.control_interno.categorias', []);
        $meses = [
            ($trimestre - 1) * 3 + 1,
            ($trimestre - 1) * 3 + 2,
            ($trimestre - 1) * 3 + 3,
        ];

        $empresas = Empresa::whereNotNull('codigo')->orderBy('codigo')->get();

        $resultado = [
            'trimestre' => "T{$trimestre}",
            'anio' => $anio,
            'meta_ind2' => $param->meta_ind2,
            'empresas' => [],
        ];

        foreach ($empresas as $empresa) {
            $mesesData = [];
            $totalFise = 0;
            $totalEnPlazo = 0;
            $totalFueraDePlazo = 0;

            foreach ($meses as $mes) {
                $desde = Carbon::create($anio, $mes, 1)->startOfDay();
                $hasta = $desde->copy()->endOfMonth()->endOfDay();

                $solicitudesFise = Solicitud::query()
                    ->where('empresa_id', $empresa->id)
                    ->whereHas('faseControlInterno', fn ($q) => $q->whereIn('fase', self::FASES_QUE_CUENTAN))
                    ->whereHas('solicitante', fn ($q) => $q->where('usuario_fise', 'Sí'))
                    ->whereHas('instalacion', fn ($q) => $q->whereBetween('fecha_finalizacion_instalacion_interna', [$desde, $hasta]))
                    ->with(['proyecto', 'instalacion'])
                    ->get()
                    ->filter(function (Solicitud $solicitud) use ($categoriasMap) {
                        $categoria = $categoriasMap[$solicitud->proyecto?->categoria_proyecto ?? ''] ?? null;

                        // Excluye Multifamiliares (y cualquier categoría sin
                        // mapeo): "FP: 5" del Excel — solo RES y COM puntúan.
                        return in_array($categoria, ['RES', 'COM'], true);
                    });

                $comercio = 0;
                $residencial = 0;
                $fueraDePlazo = 0;

                foreach ($solicitudesFise as $solicitud) {
                    $categoria = $categoriasMap[$solicitud->proyecto?->categoria_proyecto] ?? null;
                    $categoria === 'COM' ? $comercio++ : $residencial++;

                    if (ControlInternoIndicadores::para($solicitud)['fuera_de_plazo']) {
                        $fueraDePlazo++;
                    }
                }

                $total = $comercio + $residencial;
                $enPlazo = $total - $fueraDePlazo;

                $mesesData[] = [
                    'mes' => $desde->format('Y-m'),
                    'comercio' => $comercio,
                    'residencial' => $residencial,
                    'total_fise' => $total,
                    'en_plazo' => $enPlazo,
                    'fuera_de_plazo' => $fueraDePlazo,
                    'ind2' => $total > 0 ? round($enPlazo / $total, 4) : null,
                ];

                $totalFise += $total;
                $totalEnPlazo += $enPlazo;
                $totalFueraDePlazo += $fueraDePlazo;
            }

            $ind2 = $totalFise > 0 ? round($totalEnPlazo / $totalFise, 4) : null;

            $resultado['empresas'][$empresa->codigo] = [
                'nombre' => $empresa->nombre,
                'meses' => $mesesData,
                'total_fise' => $totalFise,
                'en_plazo' => $totalEnPlazo,
                'fuera_de_plazo' => $totalFueraDePlazo,
                'ind2' => $ind2,
                'puntaje' => $ind2 !== null ? round($ind2 * 5, 2) : null,
            ];
        }

        return $resultado;
    }
}
