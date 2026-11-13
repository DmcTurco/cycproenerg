<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ControlGeneral;
use App\Services\DocumentosHabilitacion;
use App\Support\Permisos;
use Illuminate\Http\Request;
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
