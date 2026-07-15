<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UploadEvidenciaRequest;
use App\Http\Resources\Api\V1\EvidenciaFotoResource;
use App\Models\ActividadDiaria;
use App\Models\TransaccionFinanciera;
use App\Services\EvidenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvidenciaController extends Controller
{
    public function __construct(
        protected EvidenciaService $evidenciaService,
    ) {}

    public function upload(UploadEvidenciaRequest $request): JsonResponse
    {
        $resultado = $this->evidenciaService->subir(
            $request->file('archivo'),
            $request->relacion_id,
            $request->relacion_tipo
        );

        return response()->json([
            'success' => true,
            'message' => 'Evidencia subida exitosamente',
            'data' => $resultado,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'relacion_id' => 'required|integer',
            'relacion_tipo' => 'required|in:transaccion,actividad',
        ]);

        $modelo = $request->relacion_tipo === 'actividad'
            ? ActividadDiaria::class
            : TransaccionFinanciera::class;

        $relacion = $modelo::where('id', $request->relacion_id)
            ->whereHas('campana', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->first();

        if (!$relacion) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado o recurso no existe.',
            ], 403);
        }

        $evidencias = $this->evidenciaService->listar(
            $request->relacion_id,
            $request->relacion_tipo
        );

        return response()->json([
            'success' => true,
            'data' => EvidenciaFotoResource::collection($evidencias),
        ]);
    }
}
