<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DashboardKpiResource;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected DashboardService $dashboardService,
    ) {}

    public function show($campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $kpis = $this->dashboardService->obtenerKpis($campana);

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    public function historial(Request $request, $campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $historial = $this->dashboardService->obtenerHistorial(
            $campana,
            $request->fecha,
            $request->fecha_desde,
            $request->fecha_hasta
        );

        return response()->json([
            'success' => true,
            'data' => $historial,
        ]);
    }
}
