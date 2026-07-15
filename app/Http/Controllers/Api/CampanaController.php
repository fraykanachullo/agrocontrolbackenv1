<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCampanaRequest;
use App\Http\Requests\Api\V1\UpdateCampanaRequest;
use App\Http\Resources\Api\V1\CampanaResource;
use App\Services\CampanaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampanaController extends Controller
{
    public function __construct(
        protected CampanaService $campanaService,
    ) {}

    public function index(): JsonResponse
    {
        $campanas = $this->campanaService->listar(Auth::id());

        return response()->json([
            'success' => true,
            'data' => CampanaResource::collection($campanas),
        ]);
    }

    public function store(StoreCampanaRequest $request): JsonResponse
    {
        $user = Auth::user();

        $campana = $this->campanaService->crear(
            $request->validated(),
            $user->id,
            $user->plan_suscripcion,
            $user->tenant_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Campaña creada exitosamente',
            'data' => new CampanaResource($campana),
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $campana = $this->campanaService->obtener($id, Auth::id());
        $campana->load(['actividades', 'transaccionesFinancieras', 'cosechasEnvios']);

        return response()->json([
            'success' => true,
            'data' => new CampanaResource($campana),
        ]);
    }

    public function update(UpdateCampanaRequest $request, $id): JsonResponse
    {
        $campana = $this->campanaService->obtener($id, Auth::id());
        $campana = $this->campanaService->actualizar($campana, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Campaña actualizada exitosamente',
            'data' => new CampanaResource($campana),
        ]);
    }

    public function cerrar($id): JsonResponse
    {
        $campana = $this->campanaService->obtener($id, Auth::id());
        $campana = $this->campanaService->cerrar($campana);

        return response()->json([
            'success' => true,
            'message' => 'Campaña cerrada exitosamente',
            'data' => new CampanaResource($campana),
        ]);
    }

    public function balance($id): JsonResponse
    {
        $campana = $this->campanaService->obtener($id, Auth::id());
        $balance = $this->campanaService->balance($campana);

        return response()->json([
            'success' => true,
            'data' => $balance,
        ]);
    }
}
