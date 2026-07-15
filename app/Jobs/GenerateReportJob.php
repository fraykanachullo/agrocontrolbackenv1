<?php

namespace App\Jobs;

use App\Models\Campana;
use App\Services\ReporteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public int $campanaId,
        public string $tipo,
    ) {}

    public function handle(ReporteService $reporteService): void
    {
        $campana = Campana::find($this->campanaId);

        if (!$campana) {
            return;
        }

        $campana->load(['actividades', 'transaccionesFinancieras', 'cosechasEnvios']);

        match ($this->tipo) {
            'pdf' => $reporteService->generarPDF($campana),
            'excel' => $reporteService->generarExcel($campana),
            default => null,
        };
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("Error generando reporte {$this->tipo} para campaña {$this->campanaId}: " . $exception->getMessage());
    }
}
