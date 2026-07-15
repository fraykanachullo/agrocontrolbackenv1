<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected ReporteService $reporteService,
    ) {}

    public function pdf($campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $result = $this->reporteService->generarPDF($campana);

        return response()->json([
            'success' => true,
            'message' => 'Reporte PDF generado exitosamente',
            'data' => $result,
        ]);
    }

    public function excel($campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $result = $this->reporteService->generarExcel($campana);

        return response()->json([
            'success' => true,
            'message' => 'Reporte Excel generado exitosamente',
            'data' => $result,
        ]);
    }
}
