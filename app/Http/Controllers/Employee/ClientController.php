<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

use App\Jobs\ProcessExcelJob;
use App\Models\Concesionaria;
use App\Models\Empresa;
use App\Models\Proyecto;
use App\Models\Prueba;
use App\Models\Solicitante;
use App\Models\Solicitud;
use App\Models\Ubicacion;
use App\Models\Instalacion;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use App\Helpers\TipoDocumentoHelper;
use App\Models\EstadoPortal;
use App\Models\EstadoInterno;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\table;

class ClientController extends Controller
{

    public function index(Request $request)
    {

        $estados_Portal = EstadoPortal::orderBy('nombre', 'DESC')->get();

        // Establecer fechas por defecto (últimos 6 meses)
        $fechaFin = $request->fecha_fin ? Carbon::parse($request->fecha_fin) : Carbon::now();
        $fechaInicio = $request->fecha_inicio
            ? Carbon::parse($request->fecha_inicio)
            : Carbon::now()->subMonths(6);

        $query = Solicitud::with('estadoPortal');

        // Aplicar filtro de fechas
        $query->whereBetween('created_at', [$fechaInicio->startOfDay(), $fechaFin->endOfDay()]);


        if ($request->filled('numero_solicitud')) {
            $query->where('numero_solicitud', 'like', '%' . $request->numero_solicitud . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado_portal_id', $request->estado);
        }

        if ($request->filled('dni')) {
            $query->whereHas('solicitante', function ($q) use ($request) {
                $q->where('numero_documento', 'like', '%' . $request->dni . '%');
            });
        }

        if ($request->filled('nombre')) {
            $query->whereHas('solicitante', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->nombre . '%');
            });
        }

        if ($request->filled('numero_suministro')) {
            $query->where('numero_suministro', 'like', '%' . $request->numero_suministro . '%');
        }

        $clientesConSolicitudes = $query->paginate(10)->appends($request->except('page'));

        $totalSolicitudes = Solicitud::count();

        $totalSolicitudesFiltradas = $query->count();

        // Transformar los resultados usando el helper
        $clientesConSolicitudes->through(function ($solicitud) {
            $solicitud->tipo_documento_nombre = TipoDocumentoHelper::getTypeDocumentName(
                optional($solicitud->solicitante)->tipo_documento
            );
            return $solicitud;
        });
        // Pasar las fechas a la vista
        $fechas = [
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
        ];
        return view('employee.pages.clients.index', compact('clientesConSolicitudes', 'estados_Portal', 'totalSolicitudes', 'totalSolicitudesFiltradas', 'fechas'));
    }

    public function checkProgress($processId)
    {
        $progress = Cache::get("excel_progress_{$processId}");

        // Verificar si el proceso está atascado
        $startTime = Cache::get("excel_start_time_{$processId}");
        $timeoutLimit = now()->subMinutes(5); // 5 minutos de timeout

        if ($startTime && $startTime < $timeoutLimit && (!$progress || $progress['progress'] < 100)) {
            return response()->json([
                'error' => 'El proceso ha excedido el tiempo de espera',
                'timeout' => true
            ]);
        }

        if (!$progress) {
            return response()->json([
                'error' => 'No se encontró el proceso',
                'processId' => $processId
            ]);
        }

        return response()->json($progress);
    }

    public function change(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'No se ha subido ningún archivo.'
            ], 400);
        }

        $file = $request->file('file');

        // Validar extensión
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo debe ser un documento Excel válido (.xlsx o .xls).'
            ], 422);
        }

        // Validar el tamaño del archivo (10MB máximo)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no debe superar los 10MB.'
            ], 422);
        }

        try {

            $filePath = $file->store('temp-excel', 'local');
            $processId = Str::uuid();
            $job = new ProcessExcelJob($filePath, $processId);

            Log::info('Creando nuevo proceso', ['processId' => $processId]);
            Cache::put("excel_start_time_{$processId}", now(), now()->addHours(1));
            Cache::put("excel_progress_{$processId}", [
                'progress' => 0,
                'processed' => 0,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'started' => true,
                'lastUpdate' => now()->timestamp
            ], now()->addHours(1));

            dispatch($job);

            return response()->json([
                'success' => true,
                'message' => 'El archivo se está procesando en segundo plano',
                'processId' => $processId
            ]);
        } catch (\Exception $e) {
            Log::error('Error al iniciar el proceso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el proceso: ' . $e->getMessage()
            ], 500);
        }
    }
}
