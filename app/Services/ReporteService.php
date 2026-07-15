<?php

namespace App\Services;

use App\Exports\CampanaExport;
use App\Models\Campana;
use App\Repositories\Contracts\ActividadRepositoryInterface;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use Illuminate\Support\Facades\App;

class ReporteService
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected TransaccionRepositoryInterface $transaccionRepo,
        protected CosechaEnvioRepositoryInterface $cosechaRepo,
        protected ActividadRepositoryInterface $actividadRepo,
    ) {}

    public function generarPDF($campana): array
    {
        $campana->load(['actividades', 'transaccionesFinancieras', 'cosechasEnvios']);

        $balance = $this->calcularBalance($campana);

        $html = view('reportes.campana-pdf', [
            'campana' => $campana,
            'balance' => $balance,
        ])->render();

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        $filename = "reporte_campana_{$campana->id}_" . now()->format('Ymd_His') . ".pdf";
        $path = storage_path("app/reports/{$filename}");

        if (!file_exists(storage_path('app/reports'))) {
            mkdir(storage_path('app/reports'), 0755, true);
        }

        file_put_contents($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'url' => url("storage/reports/{$filename}"),
        ];
    }

    public function generarExcel($campana): array
    {
        $campana->load(['actividades', 'transaccionesFinancieras', 'cosechasEnvios']);

        $balance = $this->calcularBalance($campana);

        $filename = "reporte_campana_{$campana->id}_" . now()->format('Ymd_His') . ".xlsx";

        if (!file_exists(storage_path('app/reports'))) {
            mkdir(storage_path('app/reports'), 0755, true);
        }

        \Maatwebsite\Excel\Facades\Excel::store(
            new CampanaExport($campana, $balance),
            $filename,
            'local'
        );

        $path = storage_path("app/reports/{$filename}");

        return [
            'filename' => $filename,
            'path' => $path,
            'url' => url("storage/reports/{$filename}"),
        ];
    }

    protected function calcularBalance($campana): array
    {
        $totalIngresos = $this->transaccionRepo->sumMontoByTipoAndEstado($campana->id, 'ingreso', 'pagado');
        $totalIngresosLiquidados = $this->cosechaRepo->sumMontoNetoLiquidado($campana->id);
        $totalEgresos = $this->transaccionRepo->sumMontoByTipo($campana->id, 'egreso');

        return [
            'total_inversion' => round($totalEgresos, 2),
            'total_recaudado' => round($totalIngresos + $totalIngresosLiquidados, 2),
            'margen_ganancia_neta' => round(($totalIngresos + $totalIngresosLiquidados) - $totalEgresos, 2),
            'moneda' => 'PEN',
        ];
    }
}
