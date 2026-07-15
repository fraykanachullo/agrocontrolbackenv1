<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BulkSyncRequest;
use App\Http\Requests\Api\V1\UploadEvidenciaRequest;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncController extends Controller
{
    public function __construct(
        protected SyncService $syncService,
    ) {}

    public function campanas(BulkSyncRequest $request): JsonResponse
    {
        $result = $this->syncService->syncCampanas(
            Auth::id(),
            $request->registros
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => count($result['confirmados']) . ' de ' . count($request->registros) . ' registros sincronizados.',
        ]);
    }

    public function actividades(BulkSyncRequest $request): JsonResponse
    {
        $result = $this->syncService->syncActividades(
            Auth::id(),
            $request->registros
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => count($result['confirmados']) . ' de ' . count($request->registros) . ' registros sincronizados.',
        ]);
    }

    public function transacciones(BulkSyncRequest $request): JsonResponse
    {
        $result = $this->syncService->syncTransacciones(
            Auth::id(),
            $request->registros
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => count($result['confirmados']) . ' de ' . count($request->registros) . ' registros sincronizados.',
        ]);
    }

    public function cosechas(BulkSyncRequest $request): JsonResponse
    {
        $result = $this->syncService->syncCosechas(
            Auth::id(),
            $request->registros
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => count($result['confirmados']) . ' de ' . count($request->registros) . ' registros sincronizados.',
        ]);
    }

    public function evidencias(UploadEvidenciaRequest $request): JsonResponse
    {
        $result = $this->syncService->uploadEvidencia(
            Auth::id(),
            $request->file('archivo'),
            $request->input('uuid_local'),
            $request->relacion_id,
            $request->relacion_tipo
        );

        return response()->json([
            'success' => true,
            'message' => 'Evidencia subida exitosamente',
            'data' => $result,
        ]);
    }

    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'since' => 'nullable|date',
        ]);

        $since = $request->since ?? now()->subDays(30);
        $result = $this->syncService->pull(Auth::id(), $since);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
