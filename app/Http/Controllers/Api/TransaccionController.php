<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransaccionRequest;
use App\Http\Requests\Api\V1\UpdateTransaccionRequest;
use App\Http\Resources\Api\V1\TransaccionFinancieraResource;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use App\Services\TransaccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransaccionController extends Controller
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected TransaccionRepositoryInterface $transaccionRepo,
        protected TransaccionService $transaccionService,
    ) {}

    public function index(Request $request, $campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $transacciones = $this->transaccionService->listar(
            $campana->id,
            $request->tipo,
            $request->categoria,
            $request->fecha_desde,
            $request->fecha_hasta
        );

        return response()->json([
            'success' => true,
            'data' => TransaccionFinancieraResource::collection($transacciones),
        ]);
    }

    public function store(StoreTransaccionRequest $request, $campanaId): JsonResponse
    {
        $campana = $this->campanaRepo->findByIdAndUser($campanaId, Auth::id());

        $transaccion = $this->transaccionService->crear(
            $request->validated(),
            $campana->id
        );

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->tipo) . ' registrado exitosamente',
            'data' => new TransaccionFinancieraResource($transaccion),
        ], 201);
    }

    public function update(UpdateTransaccionRequest $request, $id): JsonResponse
    {
        $transaccion = $this->transaccionRepo->findById($id);
        $this->verificarPropiedad($transaccion);

        $transaccion = $this->transaccionService->actualizar($transaccion, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Transacción actualizada exitosamente',
            'data' => new TransaccionFinancieraResource($transaccion),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $transaccion = $this->transaccionRepo->findById($id);
        $this->verificarPropiedad($transaccion);

        $this->transaccionService->eliminar($transaccion);

        return response()->json([
            'success' => true,
            'message' => 'Transacción anulada exitosamente',
        ]);
    }

    protected function verificarPropiedad($transaccion): void
    {
        if ($transaccion->campana->user_id !== Auth::id()) {
            abort(403, 'No autorizado.');
        }
    }
}
