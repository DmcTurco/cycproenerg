<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ControlGeneral;
use App\Services\DocumentosHabilitacion;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Documentos de habilitación de una solicitud: lista de documentos, vista
 * previa del elegido y un formulario corto con lo que el sistema todavía no
 * tiene (se guarda en control_generales.datos_documentos, provisional hasta
 * que otro módulo dé esos datos). Se descarga uno o todos en un ZIP.
 */
class DocumentosHabilitacionController extends Controller
{
    public function __construct(private DocumentosHabilitacion $documentos)
    {
    }

    public function index(ControlGeneral $controlGeneral)
    {
        $ctx = $this->documentos->contexto($controlGeneral);
        $cfg = config('documentos_habilitacion');

        return view('employee.pages.control-general.documentos.index', [
            'cg' => $controlGeneral,
            's' => $ctx['s'],
            'cliente' => $ctx['cliente'],
            'faltantes' => $this->documentos->faltantes($ctx),
            'puedeEditar' => Permisos::puedeRuta('employee.control-general.documentos.guardar'),
            'js' => [
                'documentos' => collect($cfg['documentos'])->map(fn ($d, $clave) => ['clave' => $clave, 'titulo' => $d['titulo'], 'campos' => $d['campos']])->values()->all(),
                'datos' => $ctx['datos'],
                'archivos' => $this->archivosJs($controlGeneral),
                'urlSubir' => route('employee.control-general.documentos.archivos.subir', $controlGeneral),
                'urlArchivo' => route('employee.control-general.documentos.archivos.eliminar', [$controlGeneral, '__ID__']),
                'anexos' => collect($cfg['anexos'])->map(fn ($a, $clave) => ['clave' => $clave, 'titulo' => $a['titulo']])->values()->all(),
                'materiales' => $cfg['materiales'],
                'tiposInstalacion' => $cfg['tipos_instalacion'],
                'tipos' => $cfg['tipos'],
                'filasPuntos' => $cfg['filas_puntos'],
                'filasItems' => $cfg['filas_items'],
                'urls' => [
                    'pdf' => route('employee.control-general.documentos.pdf', [$controlGeneral, '__DOC__']),
                    'zip' => route('employee.control-general.documentos.zip', $controlGeneral),
                    'guardar' => route('employee.control-general.documentos.guardar', $controlGeneral),
                ],
            ],
        ]);
    }

    /** PDF de un documento: en pantalla (vista previa) o como descarga (?descargar=1). */
    public function pdf(Request $request, ControlGeneral $controlGeneral, string $documento)
    {
        abort_unless($this->documentos->existe($documento), 404);

        $contenido = $this->documentos->pdf($documento, $controlGeneral);
        $nombre = $this->documentos->nombreArchivo($documento, $controlGeneral);

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('descargar') ? 'attachment' : 'inline') . '; filename="' . $nombre . '"',
        ]);
    }

    public function zip(ControlGeneral $controlGeneral)
    {
        $ctx = $this->documentos->contexto($controlGeneral);
        $codigo = $ctx['s']->numero_suministro ?: $ctx['s']->numero_solicitud;

        return response()
            ->download($this->documentos->zip($controlGeneral), "Documentos de habilitacion {$codigo}.zip")
            ->deleteFileAfterSend(true);
    }

    /** Sube un documento a mano para esta solicitud (queda en el disco privado). */
    public function subir(Request $request, ControlGeneral $controlGeneral)
    {
        $request->validate([
            'archivo' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'nombre' => 'nullable|string|max:150',
        ], [
            'archivo.required' => 'Elige un archivo.',
            'archivo.max' => 'El archivo pesa más de 20 MB.',
            'archivo.mimes' => 'Solo PDF, imágenes, Word o Excel.',
            'archivo.uploaded' => 'No se pudo subir el archivo.',
        ]);

        $file = $request->file('archivo');
        $original = $file->getClientOriginalName();

        // Nombre legible: solicitud-nombre-fecha.ext (ej. 3800001-acta-firmada-20261122-153045.pdf).
        $solicitud = $controlGeneral->solicitud?->numero_solicitud ?? $controlGeneral->id;
        $base = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'documento';
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension());
        $nombreDisco = Str::limit("{$solicitud}-{$base}", 80, '') . '-' . now()->format('Ymd-His') . '.' . $ext;
        if (Storage::disk('local')->exists("control-general/{$controlGeneral->id}/{$nombreDisco}")) {
            $nombreDisco = Str::beforeLast($nombreDisco, '.') . '-' . Str::lower(Str::random(4)) . '.' . $ext;
        }

        $archivo = $controlGeneral->archivos()->create([
            'nombre' => $request->filled('nombre') ? trim($request->input('nombre')) : pathinfo($original, PATHINFO_FILENAME),
            'nombre_original' => $original,
            'ruta' => $file->storeAs("control-general/{$controlGeneral->id}", $nombreDisco, 'local'),
            'mime' => $file->getMimeType(),
            'tamano' => $file->getSize(),
            'subido_por' => $request->user('employee')?->id,
        ]);

        return response()->json(['ok' => true, 'archivos' => $this->archivosJs($controlGeneral), 'id' => $archivo->id]);
    }

    /** Muestra (inline) o descarga (?descargar=1) un documento subido. */
    public function archivo(Request $request, ControlGeneral $controlGeneral, int $archivo)
    {
        $a = $controlGeneral->archivos()->findOrFail($archivo);
        abort_unless(Storage::disk('local')->exists($a->ruta), 404);

        $disposicion = $request->boolean('descargar') || !$a->esVisible() ? 'attachment' : 'inline';

        return Storage::disk('local')->response($a->ruta, $a->nombre_original, [
            'Content-Type' => $a->mime ?: 'application/octet-stream',
        ], $disposicion);
    }

    public function eliminarArchivo(ControlGeneral $controlGeneral, int $archivo)
    {
        $controlGeneral->archivos()->findOrFail($archivo)->delete();

        return response()->json(['ok' => true, 'archivos' => $this->archivosJs($controlGeneral)]);
    }

    private function archivosJs(ControlGeneral $cg): array
    {
        return $cg->archivos()->get()->map(fn ($a) => [
            'id' => $a->id,
            'nombre' => $a->nombre,
            'original' => $a->nombre_original,
            'tamano' => $a->tamano,
            'visible' => $a->esVisible(),
            'fecha' => $a->created_at?->format('d/m/Y H:i'),
            'ver' => route('employee.control-general.documentos.archivos.ver', [$cg, $a->id]),
        ])->all();
    }

    /**
     * Guarda lo escrito en el formulario. Cada lista (items, puntos, anexos)
     * reemplaza a la anterior; lo demás se mezcla con lo que ya había.
     */
    public function guardar(Request $request, ControlGeneral $controlGeneral)
    {
        $cfg = config('documentos_habilitacion');

        $datos = $request->validate([
            'fecha' => 'nullable|date',
            'lugar' => 'nullable|string|max:120',
            'n_certificado' => 'nullable|string|max:30',
            'n_instalacion' => 'nullable|string|max:30',
            'material' => ['nullable', Rule::in($cfg['materiales'])],
            'tipo' => ['nullable', Rule::in($cfg['tipos'])],
            'potencia_nominal' => 'nullable|string|max:20',
            'items' => 'nullable|array|max:' . $cfg['filas_items'],
            'items.*.inst' => ['nullable', Rule::in($cfg['tipos_instalacion'])],
            'items.*.tipo' => ['nullable', Rule::in($cfg['tipos'])],
            'items.*.potencia' => 'nullable|string|max:20',
            'puntos' => 'nullable|array|max:' . $cfg['filas_puntos'],
            'puntos.*.artefacto' => 'nullable|string|max:60',
            'puntos.*.estado' => ['nullable', Rule::in(['Conectado', 'Pendiente'])],
            'puntos.*.potencia' => 'nullable|string|max:20',
            'puntos.*.ambiente' => 'nullable|string|max:60',
            'puntos.*.volumen' => 'nullable|string|max:20',
            'puntos.*.confinado' => ['nullable', Rule::in(['SI', 'NO'])],
            'puntos.*.rejilla_sup' => 'nullable|string|max:20',
            'puntos.*.rejilla_inf' => 'nullable|string|max:20',
            'anexos' => 'nullable|array',
            'anexos.*' => ['string', Rule::in(array_keys($cfg['anexos']))],
        ]);

        // Filas completamente vacías no se guardan.
        foreach (['items', 'puntos'] as $lista) {
            if (isset($datos[$lista])) {
                $datos[$lista] = collect($datos[$lista])
                    ->filter(fn ($fila) => collect($fila)->filter(fn ($v) => filled($v))->isNotEmpty())
                    ->values()->all();
            }
        }

        $controlGeneral->datos_documentos = array_merge((array) $controlGeneral->datos_documentos, $datos);
        $controlGeneral->save();

        return response()->json(['ok' => true, 'mensaje' => 'Guardado', 'datos' => $this->documentos->datos($controlGeneral->fresh())]);
    }
}
