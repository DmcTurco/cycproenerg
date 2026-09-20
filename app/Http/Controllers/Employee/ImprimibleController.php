<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Cuadrilla;
use App\Models\Herramienta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * CM-9: Imprimibles — equivalente a la hoja IMPRIMIBLES del Excel. Son
 * documentos de impresión, sin lógica de negocio propia (no hay macro
 * dedicada): actas EN BLANCO para firmar en campo (de materiales y de
 * herramientas) y stickers de herramientas para pegar en cada una.
 *
 * Diferencia con el Excel: los stickers ahí estaban limitados a 100
 * herramientas fijas repartidas en 5 hojas A4 fijas (21+21+21+21+16); acá
 * se generan solo para las herramientas elegidas y dompdf pagina solas
 * (21 por página, como el Excel, pero sin el límite de 100).
 */
class ImprimibleController extends Controller
{
    public function index()
    {
        return view('employee.pages.materiales.imprimibles.index', [
            'cuadrillas' => Cuadrilla::where('estado', 'ACTIVO')->orderBy('nombre')->get(),
            'herramientas' => Herramienta::orderBy('codigo')->get(),
        ]);
    }

    /**
     * Acta de entrega de materiales — IMPRIMIBLES!A1:F30. Formato EN
     * BLANCO a propósito (los materiales/cantidades se llenan a mano en
     * campo, antes de que exista la cotización/vale formal de CM-4): solo
     * se pre-llena el encabezado si se elige una cuadrilla (nombre,
     * empresa(s), tipo).
     */
    public function actaMateriales(Request $request)
    {
        $cuadrilla = $request->filled('cuadrilla_id') ? Cuadrilla::with('empresas')->findOrFail($request->cuadrilla_id) : null;

        $pdf = Pdf::loadView('employee.pages.materiales.imprimibles.acta-materiales-pdf', [
            'cuadrilla' => $cuadrilla,
            'filas' => range(1, 18),
        ])->setPaper('a4');

        return $pdf->download('Acta de entrega de materiales.pdf');
    }

    /**
     * Acta de entrega de herramientas — IMPRIMIBLES!A33:F57. A diferencia
     * del acta de materiales, acá SÍ conviene pre-llenar las filas cuando
     * se eligen herramientas puntuales: el catálogo ya conoce código,
     * descripción, marca/serie y estado de cada una (identidad única, no
     * una cantidad a definir en campo como los materiales). Máximo 12 por
     * página, igual que el Excel (IMPRIMIBLES!A40:A51); si se eligen más,
     * se reparten en varias páginas.
     */
    public function actaHerramientas(Request $request)
    {
        $cuadrilla = $request->filled('cuadrilla_id') ? Cuadrilla::findOrFail($request->cuadrilla_id) : null;

        $herramientas = $request->filled('herramientas')
            ? Herramienta::whereIn('id', (array) $request->input('herramientas'))->orderBy('codigo')->get()
            : collect();

        $paginas = $herramientas->isEmpty()
            ? [collect()]
            : $herramientas->chunk(12)->values();

        $pdf = Pdf::loadView('employee.pages.materiales.imprimibles.acta-herramientas-pdf', [
            'cuadrilla' => $cuadrilla,
            'paginas' => $paginas,
        ])->setPaper('a4');

        return $pdf->download('Acta de entrega de herramientas.pdf');
    }

    /**
     * Stickers de herramientas — IMPRIMIBLES!A61 en adelante. Traducción
     * literal de la fórmula de cada etiqueta (ver
     * docs/modulos/control-materiales.md): 3 columnas x 7 filas = 21
     * etiquetas por hoja A4 adhesiva.
     */
    public function stickers(Request $request)
    {
        $herramientas = $request->filled('herramientas')
            ? Herramienta::whereIn('id', (array) $request->input('herramientas'))->orderBy('codigo')->get()
            : Herramienta::orderBy('codigo')->get();

        abort_if($herramientas->isEmpty(), 422, 'Elige al menos una herramienta para generar los stickers.');

        $paginas = $herramientas->chunk(21)->values();

        $pdf = Pdf::loadView('employee.pages.materiales.imprimibles.stickers-pdf', [
            'paginas' => $paginas,
        ])->setPaper('a4');

        return $pdf->download('Stickers de herramientas.pdf');
    }
}
