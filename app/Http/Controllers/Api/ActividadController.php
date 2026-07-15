<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreActividadRequest;
use App\Http\Resources\Api\V1\ActividadDiariaResource;
use App\Repositories\Contracts\ActividadRepositoryInterface;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActividadController extends Controller
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected ActividadRepositoryInterface $actividadRepo,
    ) {}

    public function index(Request $request, $campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $actividades = $this->actividadRepo->getByCampana(
            $campana->id,
            $request->fecha_desde,
            $request->fecha_hasta
        );

        return response()->json([
            'success' => true,
            'data' => ActividadDiariaResource::collection($actividades),
        ]);
    }

    public function store(StoreActividadRequest $request, $campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $actividad = $this->actividadRepo->create(array_merge(
            $request->validated(),
            ['campana_id' => $campana->id]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Actividad registrada exitosamente',
            'data' => new ActividadDiariaResource($actividad),
        ], 201);
    }
}
