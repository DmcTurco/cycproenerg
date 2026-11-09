<?php

namespace Database\Seeders;

use App\Models\Cotizacion;
use App\Models\Ejecutado;
use App\Models\Empresa;
use App\Models\Entrega;
use App\Models\Herramienta;
use App\Models\Ingreso;
use App\Models\Material;
use App\Models\ParametroControlMaterial;
use App\Models\PersonaCampo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de desarrollo/pruebas de Control de Materiales tomados tal cual del
 * Excel real (rev 03): CONTROL_MATERIALES_CC_10-2026_COMPLETO.xlsm.
 *
 * Carga: parámetros, personal (hoja RESUMEN), 123 materiales, herramientas,
 * ingresos, 60 cotizaciones/vales con su detalle, EJECUTADO y ENTREGAS.
 * Los datos viven en database/seeders/data/control_materiales_excel.json
 * (exportado del Excel); el stock resultante coincide con CATALOGO!L del
 * Excel en los 123 materiales.
 *
 * Los movimientos (ingresos, cotizaciones, ejecutados, entregas) no tienen
 * clave natural para ser idempotentes: si ya hay datos, el seeder se
 * detiene. Para recargar: `php artisan migrate:fresh --seed`.
 */
class ControlMaterialesExcelSeeder extends Seeder
{
    public function run(): void
    {
        if (Cotizacion::withTrashed()->exists() || Ingreso::withTrashed()->exists()) {
            $this->command?->warn('Control de Materiales ya tiene movimientos; no se cargó el Excel. Usa migrate:fresh --seed para recargar.');

            return;
        }

        $datos = json_decode(
            file_get_contents(database_path('seeders/data/control_materiales_excel.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($datos) {
            $empresas = $this->empresas();
            $this->parametros($datos['parametros']);
            $personas = $this->personas($datos['personas'], $empresas);
            $materiales = $this->materiales($datos['materiales']);
            $herramientas = $this->herramientas($datos['herramientas']);
            $this->ingresos($datos['ingresos'], $materiales);
            $this->cotizaciones($datos['cotizaciones'], $personas, $materiales);
            $this->ejecutados($datos['ejecutados'], $personas, $materiales);
            $this->entregas($datos['entregas'], $personas, $herramientas);

            // El precio vigente depende de los ingresos: recalcular ventas.
            Material::all()->each->recalcularPreciosVenta();
        });
    }

    /** @return array<string, Empresa> por código (CYC / CLB) */
    private function empresas(): array
    {
        $definicion = [
            'CYC' => ['20604329397', 'C&C PROYECTOS INTEGRALES EN ENERGÍA S.A.C.'],
            'CLB' => ['20610320032', 'CLB INGENIERIA Y PROYECTOS EN ENERGIA SOSTENIBLE S.A.C.'],
        ];

        $empresas = [];
        foreach ($definicion as $codigo => [$ruc, $nombre]) {
            $empresas[$codigo] = Empresa::firstOrCreate(
                ['codigo' => $codigo],
                ['tipo_documento' => '6', 'numero_documento' => $ruc, 'nombre' => $nombre]
            );
        }

        return $empresas;
    }

    private function parametros(array $p): void
    {
        $parametros = ParametroControlMaterial::actual();
        $parametros->update([
            'igv' => $p['igv'],
            'margen_general' => $p['margen_general'],
            'umbral_alarma_precio' => $p['umbral_alarma_precio'],
        ]);
    }

    /** @return array<string, PersonaCampo> por nombre */
    private function personas(array $filas, array $empresas): array
    {
        $personas = [];

        foreach ($filas as $f) {
            $persona = PersonaCampo::updateOrCreate(
                ['nombre' => $f['nombre']],
                [
                    'tipo' => $f['tipo'],
                    'estado' => $f['estado'] ?? PersonaCampo::ESTADO_ACTIVO,
                    'tipo_documento' => $f['dni'] ? 1 : null,
                    'numero_documento' => $f['dni'],
                    'fecha_nacimiento' => $f['fecha_nacimiento'],
                    'celular' => $f['celular'],
                ]
            );

            $persona->empresas()->sync(
                collect($f['empresas'])->map(fn ($codigo) => $empresas[$codigo]->id)->all()
            );

            $personas[$f['nombre']] = $persona;
        }

        return $personas;
    }

    /** @return array<string, Material> por código */
    private function materiales(array $filas): array
    {
        $materiales = [];

        foreach ($filas as $f) {
            $materiales[$f['codigo']] = Material::updateOrCreate(
                ['codigo' => $f['codigo']],
                [
                    'descripcion' => $f['descripcion'],
                    'unidad' => $f['unidad'],
                    'precio_base' => $f['precio_base'],
                    'margen_pct' => $f['margen_pct'],
                    'stock_inicial' => $f['stock_inicial'],
                    'stock_minimo' => $f['stock_minimo'],
                    'factor_metros_por_unidad' => $f['factor_metros_por_unidad'],
                ] + (Material::where('codigo', $f['codigo'])->exists() ? [] : [
                    'serie' => Material::SERIE,
                    'correlativo' => Material::siguienteCorrelativo(),
                ])
            );
        }

        return $materiales;
    }

    /** @return array<string, Herramienta> por descripción */
    private function herramientas(array $filas): array
    {
        $herramientas = [];

        foreach ($filas as $f) {
            $herramientas[$f['descripcion']] = Herramienta::firstOrCreate(
                ['codigo' => $f['codigo']],
                [
                    'serie' => Herramienta::SERIE,
                    'correlativo' => Herramienta::siguienteCorrelativo(),
                    'descripcion' => $f['descripcion'],
                    'marca_modelo' => $f['marca_modelo'],
                    'numero_serie' => $f['numero_serie'],
                    'fecha_compra' => $f['fecha_compra'],
                    'precio' => $f['precio'],
                    'estado' => $f['estado'] ?? 'OPERATIVA',
                    'observacion' => $f['observacion'],
                ]
            );
        }

        return $herramientas;
    }

    private function ingresos(array $filas, array $materiales): void
    {
        foreach ($filas as $f) {
            Ingreso::create([
                'material_id' => $materiales[$f['codigo']]->id,
                'fecha' => $f['fecha'],
                'cantidad' => $f['cantidad'],
                'proveedor' => $f['proveedor'],
                'guia_factura' => $f['guia_factura'],
                'precio_compra' => $f['precio_compra'],
                'observacion' => $f['observacion'],
            ]);
        }
    }

    /**
     * Se crean con los números, montos y estados del Excel (incluidos los
     * SIN REGISTRO - CONFIRMAR), sin pasar por Cotizacion::emitir() para no
     * recalcular precios ni renumerar.
     */
    private function cotizaciones(array $filas, array $personas, array $materiales): void
    {
        foreach ($filas as $f) {
            $serie = $f['es_vale'] ? Cotizacion::SERIE_VALE : Cotizacion::SERIE_COTIZACION;

            $cotizacion = Cotizacion::create([
                'numero' => $f['numero'],
                'serie' => $serie,
                'correlativo' => Cotizacion::siguienteCorrelativo($serie),
                'fecha' => $f['fecha'],
                'cuadrilla_id' => $personas[$f['persona']]->id,
                'es_vale' => $f['es_vale'],
                'monto_sin_igv' => $f['monto_sin_igv'],
                'igv' => $f['igv'],
                'total_con_igv' => $f['total_con_igv'],
                'estado' => $f['estado'],
                'n_valorizacion' => $f['n_valorizacion'],
                'observacion' => $f['observacion'],
            ]);

            foreach ($f['detalles'] as $linea) {
                $cotizacion->detalles()->create([
                    'material_id' => $materiales[$linea['codigo']]->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'total' => $linea['total'],
                ]);
            }
        }
    }

    private function ejecutados(array $filas, array $personas, array $materiales): void
    {
        foreach ($filas as $f) {
            Ejecutado::create([
                'fecha' => $f['fecha'],
                'tipo_trabajo' => $f['tipo_trabajo'],
                'cuadrilla_id' => $personas[$f['persona']]->id,
                'material_id' => $materiales[$f['codigo']]->id,
                'cantidad' => $f['cantidad'],
                'n_suministro' => $f['n_suministro'],
                'observacion' => $f['observacion'],
                'movimiento' => $f['movimiento'],
            ]);
        }
    }

    /**
     * La hoja ENTREGAS del Excel trae una entrega sin código de herramienta
     * (solo la descripción, ej. ESCALERA TELESCOPICA) que no está en el
     * bloque HERRAMIENTAS: se crea la herramienta para no perder el dato.
     */
    private function entregas(array $filas, array $personas, array &$herramientas): void
    {
        foreach ($filas as $f) {
            $herramienta = $herramientas[$f['herramienta']] ??= Herramienta::create([
                'codigo' => 'ESCALERA-TELESC',
                'serie' => Herramienta::SERIE,
                'correlativo' => Herramienta::siguienteCorrelativo(),
                'descripcion' => $f['herramienta'],
                'estado' => 'OPERATIVA',
                'observacion' => 'Creada desde la hoja ENTREGAS del Excel (no tenía código HER-).',
            ]);

            Entrega::create([
                'fecha' => $f['fecha'],
                'herramienta_id' => $herramienta->id,
                'tipo' => $f['tipo'],
                'cuadrilla_id' => $personas[$f['persona']]->id,
                'observacion' => $f['observacion'],
            ]);
        }
    }
}
