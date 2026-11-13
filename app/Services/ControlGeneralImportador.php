<?php

namespace App\Services;

use App\Models\Asesor;
use App\Models\ControlGeneral;
use App\Models\PersonaCampo;
use App\Models\Solicitud;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Pasa al sistema lo que la usuaria ya trabajó a mano en su Excel
 * "Control General CLB - C&C.xlsm" (asesor, cuadrilla, técnico,
 * observaciones, trámites, habilitadas...), para que no empiece de cero.
 * Se usa una sola vez, con el comando `control-general:importar-excel`.
 *
 * Lee cada hoja por las claves de la fila 3 (n, emp, sum, sol, ...), igual
 * que la macro (ColMap), así no depende del orden de las columnas.
 * Las solicitudes se buscan por N° de solicitud: tienen que existir ya en el
 * sistema (cargadas del portal). Las que no se encuentran se informan.
 *
 * Asesor, cuadrilla, técnico y contratista se guardan por id: el nombre del
 * Excel se busca en Asesores / Personal de campo (sin distinguir mayúsculas
 * ni espacios de más). Si no hay coincidencia queda vacío y se informa en
 * `sin_coincidencia`, para que se registre a la persona y se vuelva a importar.
 */
class ControlGeneralImportador
{
    // Hoja => etapa. Si una solicitud aparece en dos hojas gana la de más abajo.
    private const HOJAS = [
        '1-Aprobadas' => ControlGeneral::APROBADA,
        '2-TC Construidas' => ControlGeneral::TC_CONSTRUIDA,
        '3-Habilitación' => ControlGeneral::HABILITACION,
        '4-Anulaciones-Reingresos' => ControlGeneral::ANULACION,
        '5-Habilitadas' => ControlGeneral::HABILITADA,
    ];

    // Columnas de la hoja 2 sin clave en la fila 3: se buscan por el título de la fila 4.
    private const TITULOS_SIN_CLAVE = [
        'FECHA DE OBS.' => 'fobs',
        'CONTRATISTA' => 'contratista',
    ];

    /** @var array{asesor:array<string,int>, persona:array<string,int>} nombre normalizado => id */
    private array $catalogo = ['asesor' => [], 'persona' => []];

    /** @var array<string,array<string,bool>> rol => nombres del Excel que no se encontraron */
    private array $sinCoincidencia = [];

    /**
     * @return array{
     *   leidas:int, importadas:int, por_etapa:array<string,int>,
     *   no_encontradas:array<int,string>, movidas_por_marca:int,
     *   sin_coincidencia:array<string,array<int,string>>
     * }
     */
    public function importar(string $archivo, bool $guardar = true): array
    {
        $libro = IOFactory::load($archivo);

        $this->catalogo = [
            'asesor' => Asesor::pluck('id', 'nombre')->mapWithKeys(fn ($id, $n) => [$this->clave($n) => $id])->all(),
            'persona' => PersonaCampo::pluck('id', 'nombre')->mapWithKeys(fn ($id, $n) => [$this->clave($n) => $id])->all(),
        ];
        $this->sinCoincidencia = [];

        // 1) Filas "fijas" de cada hoja + lo escrito en las filas "vivas"
        //    (fórmulas que jalan de otra hoja, FindPull de la macro).
        $registros = [];
        $vivas = [];
        foreach (self::HOJAS as $nombreHoja => $etapa) {
            $hoja = $libro->getSheetByName($nombreHoja);
            if (!$hoja) {
                continue;
            }
            foreach ($this->filas($hoja) as $fila) {
                if ($fila['_formula']) {
                    $vivas[$fila['sol']] = array_merge($vivas[$fila['sol']] ?? [], array_filter($fila, fn ($v) => !$this->vacio($v)));
                    continue;
                }
                $fila['_etapa'] = $etapa;
                $registros[$fila['sol']] = $fila;
            }
        }

        // 2) Última foto del portal (_base): para que la regla "manda el
        //    portal" siga funcionando desde la primera carga.
        $base = [];
        if ($hojaBase = $libro->getSheetByName('_base')) {
            $base = $this->leerBase($hojaBase);
        }

        $resultado = [
            'leidas' => count($registros),
            'importadas' => 0,
            'por_etapa' => [],
            'no_encontradas' => [],
            'movidas_por_marca' => 0,
        ];

        $ids = Solicitud::whereIn('numero_solicitud', array_keys($registros))->pluck('id', 'numero_solicitud');

        $trabajo = function () use ($registros, $vivas, $base, $ids, $guardar, &$resultado) {
            foreach ($registros as $sol => $fila) {
                $solicitudId = $ids[$sol] ?? null;
                if (!$solicitudId) {
                    $resultado['no_encontradas'][] = (string) $sol;
                    continue;
                }

                // Lo escrito en la fila "viva" completa lo que falte.
                foreach ($vivas[$sol] ?? [] as $clave => $valor) {
                    if (!str_starts_with($clave, '_') && $this->vacio($fila[$clave] ?? null)) {
                        $fila[$clave] = $valor;
                    }
                }

                $datos = $this->datos($fila, $base[$sol] ?? []);
                $resultado['importadas']++;

                if (!$guardar) {
                    $resultado['por_etapa'][$datos['etapa']] = ($resultado['por_etapa'][$datos['etapa']] ?? 0) + 1;
                    continue;
                }

                $cg = ControlGeneral::withTrashed()->firstOrNew(['solicitud_id' => $solicitudId]);
                if ($cg->trashed()) {
                    $cg->restore();
                }
                $cg->fill($datos)->save();

                // Lo que estaba marcado en el Excel y la macro todavía no
                // había movido (Construida = Sí, Habilitado = Sí, ...).
                if (ControlGeneralClasificador::aplicarMovimientos($cg)) {
                    $resultado['movidas_por_marca']++;
                }

                $resultado['por_etapa'][$cg->etapa] = ($resultado['por_etapa'][$cg->etapa] ?? 0) + 1;
            }
        };

        $guardar ? DB::transaction($trabajo) : $trabajo();

        $resultado['sin_coincidencia'] = array_map(fn ($nombres) => array_keys($nombres), $this->sinCoincidencia);

        return $resultado;
    }

    private function datos(array $f, array $base): array
    {
        $etapa = $f['_etapa'];
        $tcBase = $this->texto($base['tc'] ?? null);

        $datos = [
            'etapa' => $etapa,
            'fecha_ingreso_etapa' => now()->toDateString(),
            'fecha_aprobacion' => $this->fecha($f['fapr'] ?? null) ?? $this->fecha($base['fapr'] ?? null),
            'construida' => $this->si($f['constr'] ?? null),
            'asesor_id' => $this->buscarId('asesor', 'asesor', $f['asesor'] ?? null),
            'cuadrilla_id' => $this->buscarId('persona', 'cuadrilla', $f['cuadrilla'] ?? null),
            'contratista_id' => $this->buscarId('persona', 'contratista', $f['contratista'] ?? null),
            'observacion' => $this->texto($f['obs'] ?? null),
            'fecha_observacion' => $this->fecha($f['fobs'] ?? null),
            // En la hoja 2 vale lo que dice la hoja (ella lo puede corregir);
            // en las demás, lo último que mandó el portal.
            'resultado_tc' => $etapa === ControlGeneral::TC_CONSTRUIDA ? ($this->texto($f['tc'] ?? null) ?? $tcBase) : $tcBase,
            'resultado_tc_portal' => $tcBase,
            'accion' => in_array($this->texto($f['reing'] ?? null), ControlGeneral::ACCIONES, true) ? $this->texto($f['reing']) : null,
            'fecha_marca' => $this->fecha($f['fmarca'] ?? null),
            'estado_tramite' => $this->texto($f['tramite'] ?? null),
            'fecha_programacion' => $this->fecha($f['fprog'] ?? null),
            'tecnico_id' => $this->buscarId('persona', 'técnico', $f['tecnico'] ?? null),
            'observado' => $this->si($f['obsv'] ?? null),
            'observado_portal' => array_key_exists('obsv', $base) ? $this->si($base['obsv']) : null,
            'habilitado' => (int) ($this->si($f['habil'] ?? null) || $etapa === ControlGeneral::HABILITADA),
            'fecha_habilitacion' => $this->fecha($f['fhab'] ?? null),
        ];

        if ($etapa === ControlGeneral::ANULACION) {
            $datos['accion'] ??= 'Anular';
            $datos['estado_tramite'] ??= 'Pendiente';
        }

        return $datos;
    }

    /**
     * Filas de datos (desde la 5) como [clave => valor], con `_formula` =
     * true si la celda de suministro es una fórmula (fila "viva").
     */
    private function filas(Worksheet $hoja): \Generator
    {
        $claves = [];
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($hoja->getHighestDataColumn());
        for ($c = 1; $c <= $maxCol; $c++) {
            $clave = trim((string) $hoja->getCell([$c, 3])->getValue());
            if ($clave === '') {
                $titulo = mb_strtoupper(trim((string) $hoja->getCell([$c, 4])->getValue()), 'UTF-8');
                $clave = self::TITULOS_SIN_CLAVE[$titulo] ?? '';
            }
            if ($clave !== '') {
                $claves[$clave] = $c;
            }
        }

        if (!isset($claves['sol'])) {
            return;
        }

        $ultima = $hoja->getHighestDataRow();
        for ($r = 5; $r <= $ultima; $r++) {
            $fila = ['_formula' => false];
            foreach ($claves as $clave => $c) {
                $celda = $hoja->getCell([$c, $r]);
                if ($clave === 'sum' && $celda->isFormula()) {
                    $fila['_formula'] = true;
                }
                $fila[$clave] = $this->valor($celda);
            }

            $fila['sol'] = trim((string) ($fila['sol'] ?? ''));
            if ($fila['sol'] === '') {
                continue;
            }

            yield $fila;
        }
    }

    private function leerBase(Worksheet $hoja): array
    {
        $cols = [];
        foreach ($hoja->getRowIterator(1, 1) as $fila) {
            foreach ($fila->getCellIterator() as $celda) {
                $clave = trim((string) $celda->getValue());
                if ($clave !== '') {
                    $cols[$clave] = $celda->getColumn();
                }
            }
        }

        $base = [];
        if (!isset($cols['sol'])) {
            return $base;
        }
        for ($r = 2; $r <= $hoja->getHighestDataRow(); $r++) {
            $sol = trim((string) $hoja->getCell($cols['sol'] . $r)->getValue());
            if ($sol === '') {
                continue;
            }
            foreach (['fapr', 'tc', 'obsv'] as $clave) {
                if (isset($cols[$clave])) {
                    $base[$sol][$clave] = $hoja->getCell($cols[$clave] . $r)->getValue();
                }
            }
        }

        return $base;
    }

    private function valor(Cell $celda)
    {
        if ($celda->isFormula()) {
            try {
                return $celda->getOldCalculatedValue();
            } catch (\Throwable) {
                return null;
            }
        }

        return $celda->getValue();
    }

    private function fecha($valor): ?string
    {
        if ($this->vacio($valor)) {
            return null;
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }
        if (is_numeric($valor)) {
            return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim((string) $valor), $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        try {
            return (new \DateTime((string) $valor))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function texto($valor): ?string
    {
        $valor = trim(preg_replace('/\s+/u', ' ', (string) $valor));

        return $valor === '' ? null : $valor;
    }

    /**
     * Id de quien se llama así en el Excel, o null si no está registrado
     * (queda anotado para el informe).
     */
    private function buscarId(string $lista, string $rol, $nombre): ?int
    {
        $nombre = $this->texto($nombre);
        if ($nombre === null) {
            return null;
        }

        $id = $this->catalogo[$lista][$this->clave($nombre)] ?? null;
        if ($id === null) {
            $this->sinCoincidencia[$rol][$nombre] = true;
        }

        return $id;
    }

    private function clave(string $nombre): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($nombre)), 'UTF-8');
    }

    private function si($valor): int
    {
        return (int) in_array(mb_strtoupper(trim((string) $valor), 'UTF-8'), ['SÍ', 'SI'], true);
    }

    private function vacio($valor): bool
    {
        return is_null($valor) || trim((string) $valor) === '';
    }
}
