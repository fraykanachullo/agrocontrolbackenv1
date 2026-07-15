<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LiquidarEnvioRequest;
use App\Http\Requests\Api\V1\StoreCosechaEnvioRequest;
use App\Http\Resources\Api\V1\CosechaEnvioResource;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use App\Services\CosechaEnvioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CosechaController extends Controller
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected CosechaEnvioRepositoryInterface $cosechaRepo,
        protected CosechaEnvioService $cosechaService,
    ) {}

    public function index(Request $request, $campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $cosechas = $this->cosechaService->listar($campana->id, $request->estado);

        return response()->json([
            'success' => true,
            'data' => CosechaEnvioResource::collection($cosechas),
        ]);
    }

    public function show($id): JsonResponse
    {
        $cosecha = $this->cosechaRepo->findById($id);
        $this->verificarPropiedad($cosecha);

        return response()->json([
            'success' => true,
            'data' => new CosechaEnvioResource($cosecha),
        ]);
    }

    public function store(StoreCosechaEnvioRequest $request, $campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $cosecha = $this->cosechaService->crear(
            $request->validated(),
            $campana->id
        );

        $message = $cosecha->tipo_venta === 'parcela'
            ? 'Cosecha registrada y liquidada exitosamente'
            : 'Salida registrada pendiente de liquidación';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new CosechaEnvioResource($cosecha),
        ], 201);
    }

    public function liquidar(LiquidarEnvioRequest $request, $id): JsonResponse
    {
        $envio = $this->cosechaRepo->findById($id);
        $this->verificarPropiedad($envio);

        $resultado = $this->cosechaService->liquidar($envio, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Envío liquidado correctamente. Ingreso registrado en balance de campaña.',
            'data' => $resultado,
        ]);
    }

    protected function verificarPropiedad($cosecha): void
    {
        if ($cosecha->campana->user_id !== Auth::id()) {
            abort(403, 'No autorizado.');
        }
    }
}
