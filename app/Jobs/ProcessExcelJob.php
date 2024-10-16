<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Solicitante;
use App\Models\Empresa;
use App\Models\Concesionaria;
use App\Models\Solicitud;
use App\Models\Ubicacion;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Log;
use App\Events\RowProcessed;

class ProcessExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $row;
    protected $batchId;
    protected $rowIndex;
    protected $totalRows;

    /**
     * Create a new job instance.
     */
    public function __construct($row, $batchId, $rowIndex, $totalRows)
    {
        $this->row = $row;
        $this->batchId = $batchId;
        $this->rowIndex = $rowIndex;
        $this->totalRows = $totalRows;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Validar datos de la fila
            if (empty($this->row['H']) || empty($this->row['I']) || empty($this->row['G']) || !is_numeric($this->row['H']) || !filter_var($this->row['L'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Datos inválidos en la fila {$this->rowIndex}");
            }

            // Procesar la fila
            $solicitante = $this->processSolicitante();
            $this->processSolicitud($solicitante);
            // $this->processUbicacion($solicitante);
            // $this->processProyecto($solicitante);
            // $this->processEmpresa($solicitante);
            // $this->processConcesionaria($solicitante);

            // Emitir evento de progreso
            event(new RowProcessed($this->batchId, $this->rowIndex, $this->totalRows));
        } catch (\Exception $e) {
            Log::error("Error procesando fila {$this->rowIndex}: " . $e->getMessage());
            // Aquí podrías emitir un evento de error si lo deseas
        }
    }


    private function processSolicitante()
    {
        return Solicitante::updateOrCreate(
            ['numero_documento' => trim($this->row['H'])],
            [
                'tipo_documento' => trim($this->row['G']),
                'nombre' => trim($this->row['I']),
                'celular' => trim($this->row['K']),
                'correo_electronico' => trim($this->row['L']),
                'usuario_fise' => trim($this->row['U']),
            ]
        );
    }

    private function processSolicitud($solicitante)
    {
        Solicitud::updateOrCreate(
            ['solicitante_id' => $solicitante->id],
            [
                'numero_solicitud' => trim($this->row['A']) ?: null,
                'numero_suministro' => trim($this->row['C']) ?: null,
                'numero_contrato_suministro' => trim($this->row['D']) ?: null,
                'fecha_aprobacion_contrato' => $this->parseDate(trim($this->row['F'])),
                'fecha_registro_portal' => $this->parseDate(trim($this->row['X'])),
                'estado_solicitud' => trim($this->row['CO']),
            ]
        );
    }

    private function parseDate($dateString)
    {
        return !empty($dateString) ? \Carbon\Carbon::parse($dateString)->format('Y-m-d') : null;
    }








}
