<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Material;
use App\Models\ParametroControlMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * CM-1: CRUD del catálogo de materiales (hoja CATALOGO, bloque de
 * materiales). El listado en sí se renderiza desde CatalogoController; este
 * controlador atiende el crudModal de edición (store crea o actualiza según
 * venga o no el id, edit/destroy REST normales) y la vista "Agregar
 * materiales" (create): uno por uno (mismo store) o desde una plantilla
 * Excel (plantilla/importar).
 */
class MaterialController extends Controller
{
    public function index()
    {
        return response()->json(['materiales' => Material::orderBy('codigo')->get()]);
    }

    /**
     * Columnas de la plantilla Excel => campo del material. Al importar se
     * busca cada columna por su encabezado (no por letra).
     */
    private const COLUMNAS_PLANTILLA = [
        'Código' => 'codigo',
        'Descripción' => 'descripcion',
        'Unidad' => 'unidad',
        'Precio base S/IGV' => 'precio_base',
        'Margen %' => 'margen_pct',
        'Stock inicial' => 'stock_inicial',
        'Stock mínimo' => 'stock_minimo',
        'Metros por unidad' => 'factor_metros_por_unidad',
    ];

    private const NOMBRES_CAMPOS = [
        'codigo' => 'Código',
        'descripcion' => 'Descripción',
        'unidad' => 'Unidad',
        'precio_base' => 'Precio base',
        'margen_pct' => 'Margen %',
        'stock_inicial' => 'Stock inicial',
        'stock_minimo' => 'Stock mínimo',
        'factor_metros_por_unidad' => 'Metros por unidad',
    ];

    private function reglas(?int $materialId = null): array
    {
        return [
            'codigo' => 'required|string|max:30|unique:materiales,codigo' . ($materialId ? ",$materialId" : ''),
            'descripcion' => 'required|string|max:255',
            'unidad' => 'required|string|max:20',
            'precio_base' => 'required|numeric|min:0',
            'margen_pct' => 'nullable|numeric|min:0|max:100',
            'stock_inicial' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'factor_metros_por_unidad' => 'required|numeric|min:1',
        ];
    }

    /**
     * Vista "Agregar materiales": uno por uno y desde Excel, lado a lado.
     */
    public function create()
    {
        return view('employee.pages.materiales.material-create', [
            'parametros' => ParametroControlMaterial::actual(),
        ]);
    }

    /**
     * Plantilla para la carga masiva: hoja MATERIALES (solo encabezados, se
     * llena desde la fila 2) + hoja INSTRUCCIONES con un ejemplo, aparte
     * para que el ejemplo no se importe por error.
     */
    public function plantilla()
    {
        $libro = new Spreadsheet();
        $encabezados = array_keys(self::COLUMNAS_PLANTILLA);

        $hoja = $libro->getActiveSheet()->setTitle('MATERIALES');
        $hoja->fromArray($encabezados, null, 'A1');
        $ultima = $hoja->getHighestColumn();
        $hoja->getStyle("A1:{$ultima}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle("A1:{$ultima}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        // Código y Unidad como texto (para que "00123" no pierda los ceros).
        $hoja->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
        $hoja->getStyle('C:C')->getNumberFormat()->setFormatCode('@');
        foreach (range('A', $ultima) as $col) {
            $hoja->getColumnDimension($col)->setWidth($col === 'B' ? 45 : 18);
        }
        $hoja->freezePane('A2');

        $info = $libro->createSheet()->setTitle('INSTRUCCIONES');
        $info->fromArray([
            ['CARGA MASIVA DE MATERIALES'],
            [null],
            ['1. Llene la hoja MATERIALES desde la fila 2 (una fila por material). No cambie los encabezados.'],
            ['2. Obligatorios: Código, Descripción, Unidad y Precio base S/IGV.'],
            ['3. Margen %: de 0 a 100 (ej. 25 = 25%). Vacío = usa el margen general de Parámetros.'],
            ['4. Stock inicial y Stock mínimo: vacío = 0. Metros por unidad: vacío = 1 (tuberías: 200 o 100).'],
            ['5. El código no puede repetirse ni existir ya en el catálogo. Si alguna fila tiene error, no se carga ninguna.'],
            [null],
            ['EJEMPLO (solo de referencia):'],
            $encabezados,
            ['TUB-PEALPE-1216', 'TUBERÍA PEALPE 1216 ROLLO', 'RLL', 350.5, 25, 10, 2, 200],
            ['CODO-12', 'CODO DE BRONCE 1/2"', 'UNID', 4.8, null, 100, 20, 1],
        ], null, 'A1');
        $info->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $info->getStyle('A10:H10')->getFont()->setBold(true);
        $info->getColumnDimension('A')->setWidth(22);
        $info->getColumnDimension('B')->setWidth(40);

        $libro->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
        }, 'plantilla_materiales.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Carga masiva desde la plantilla. Todo o nada: si alguna fila tiene
     * error no se guarda ninguna y se listan los errores por fila.
     */
    public function importar(Request $request)
    {
        $request->validate(
            ['archivo' => 'required|file|mimes:xlsx,xls|max:10240'],
            [],
            ['archivo' => 'archivo Excel']
        );

        try {
            $libro = IOFactory::load($request->file('archivo')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('import_errores', ['No se pudo leer el archivo. Use la plantilla descargada (.xlsx).']);
        }

        $hoja = $libro->getSheetByName('MATERIALES') ?? $libro->getSheet(0);
        $filas = $hoja->toArray(null, true, false, true);

        // Encabezados de la fila 1 => campo (sin tildes ni mayúsculas).
        $normalizar = fn ($texto) => Str::of((string) $texto)->ascii()->lower()->squish()->toString();
        $porEncabezado = [];
        foreach (self::COLUMNAS_PLANTILLA as $titulo => $campo) {
            $porEncabezado[$normalizar($titulo)] = $campo;
        }
        $mapa = [];
        foreach ($filas[1] ?? [] as $letra => $titulo) {
            $campo = $porEncabezado[$normalizar($titulo)] ?? null;
            if ($campo) {
                $mapa[$letra] = $campo;
            }
        }
        if (array_diff(['codigo', 'descripcion', 'unidad', 'precio_base'], $mapa)) {
            return back()->with('import_errores', ['El archivo no tiene el formato de la plantilla: faltan columnas obligatorias (Código, Descripción, Unidad, Precio base S/IGV).']);
        }

        $errores = [];
        $nuevos = [];
        $codigosArchivo = [];

        foreach ($filas as $numero => $fila) {
            if ($numero === 1) {
                continue;
            }

            $datos = [];
            foreach ($mapa as $letra => $campo) {
                $valor = $fila[$letra] ?? null;
                $datos[$campo] = is_string($valor) ? trim($valor) : $valor;
            }

            // Fila completamente vacía: se ignora.
            if (collect($datos)->every(fn ($v) => $v === null || $v === '')) {
                continue;
            }

            $vacio = fn ($campo) => ($datos[$campo] ?? null) === null || $datos[$campo] === '';
            $datos['codigo'] = trim((string) ($datos['codigo'] ?? ''));
            $datos['stock_inicial'] = $vacio('stock_inicial') ? 0 : $datos['stock_inicial'];
            $datos['stock_minimo'] = $vacio('stock_minimo') ? 0 : $datos['stock_minimo'];
            $datos['factor_metros_por_unidad'] = $vacio('factor_metros_por_unidad') ? 1 : $datos['factor_metros_por_unidad'];
            $datos['margen_pct'] = $vacio('margen_pct') ? null : $datos['margen_pct'];

            $mensajes = Validator::make($datos, $this->reglas(), [], self::NOMBRES_CAMPOS)->errors()->all();

            $clave = mb_strtoupper($datos['codigo'], 'UTF-8');
            if ($clave !== '' && isset($codigosArchivo[$clave])) {
                $mensajes[] = "El código {$datos['codigo']} está repetido en el archivo (fila {$codigosArchivo[$clave]}).";
            } elseif ($clave !== '') {
                $codigosArchivo[$clave] = $numero;
            }

            foreach ($mensajes as $mensaje) {
                $errores[] = "Fila {$numero}: {$mensaje}";
            }

            if (!$mensajes) {
                $nuevos[] = $datos;
            }
        }

        if ($errores) {
            return back()->with('import_errores', $errores);
        }

        if (!$nuevos) {
            return back()->with('import_errores', ['La hoja MATERIALES no tiene filas para cargar (se llena desde la fila 2).']);
        }

        DB::transaction(function () use ($nuevos, $request) {
            foreach ($nuevos as $datos) {
                $datos['margen_pct'] = $datos['margen_pct'] !== null ? round($datos['margen_pct'] / 100, 4) : null;
                $datos['serie'] = Material::SERIE;
                $datos['correlativo'] = Material::siguienteCorrelativo();
                Material::create($datos);
            }

            // Cada material queda auditado como "creado"; esto agrupa la carga.
            Auditoria::registrar(
                'importado',
                'Importó ' . count($nuevos) . ' material(es) desde Excel (' . $request->file('archivo')->getClientOriginalName() . ')',
                null,
                null,
                ['codigos' => array_column($nuevos, 'codigo')],
                'Material',
            );
        });

        session()->flash('message', count($nuevos) . ' material(es) cargado(s) desde el Excel.');

        return redirect()->route('employee.materiales.index');
    }

    public function store(Request $request)
    {
        $materialId = $request->id;

        $validator = Validator::make($request->all(), $this->reglas($materialId ? (int) $materialId : null));

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'codigo', 'descripcion', 'unidad', 'precio_base',
            'stock_inicial', 'stock_minimo', 'factor_metros_por_unidad',
        ]);
        // El formulario trabaja el margen en porcentaje (0-100); en base de
        // datos se guarda como fracción (0-1), igual que el resto del
        // sistema (ver ParametroControlInterno::meta_ind2).
        $data['margen_pct'] = $request->filled('margen_pct') ? round($request->margen_pct / 100, 4) : null;

        if ($materialId) {
            $material = Material::findOrFail($materialId);
            $material->update($data);
            $message = 'Material actualizado.';
        } else {
            $data['serie'] = Material::SERIE;
            $data['correlativo'] = Material::siguienteCorrelativo();
            $material = Material::create($data);
            $message = 'Material agregado.';
        }

        session()->flash('message', $message);
        return response()->json([
            'success' => true,
            'redirect' => route('employee.materiales.index'),
        ]);
    }

    // El resource se registró como Route::resource('items', ...) (para no
    // repetir "materiales/materiales" en la URL bajo el prefijo del
    // módulo), así que el parámetro de ruta implícito es {item} — el
    // nombre del argumento tiene que ser $item para que el route model
    // binding lo resuelva, aunque el tipo siga siendo Material.
    public function edit(Material $item)
    {
        return response()->json([
            'material' => [
                'id' => $item->id,
                'codigo' => $item->codigo,
                'serie' => $item->serie,
                'correlativo' => $item->correlativo,
                'descripcion' => $item->descripcion,
                'unidad' => $item->unidad,
                'precio_base' => $item->precio_base,
                'margen_pct' => $item->margen_pct !== null ? round($item->margen_pct * 100, 2) : null,
                // Solo para la vista previa del precio de venta en el
                // formulario (el vigente puede estar por encima del base).
                'ultimo_precio_ingresos' => $item->ultimoPrecioIngresos(),
                'stock_inicial' => $item->stock_inicial,
                'stock_minimo' => $item->stock_minimo,
                'factor_metros_por_unidad' => $item->factor_metros_por_unidad,
            ],
        ]);
    }

    public function destroy(Material $item)
    {
        $item->delete();

        session()->flash('message', 'Material eliminado.');
        return redirect()->route('employee.materiales.index');
    }
}
