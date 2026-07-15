<?php

namespace App\Services;

use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

class CampanaService
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected TransaccionRepositoryInterface $transaccionRepo,
        protected CosechaEnvioRepositoryInterface $cosechaRepo,
    ) {}

    public function listar(int $userId)
    {
        return $this->campanaRepo->getAllByUser($userId);
    }

    public function obtener(int $id, int $userId)
    {
        return $this->campanaRepo->findByIdAndUser($id, $userId);
    }

    public function crear(array $data, int $userId, string $planSuscripcion, int $tenantId)
    {
        if ($planSuscripcion === 'gratuito') {
            $activas = $this->campanaRepo->countActivasByUser($userId);

            if ($activas >= 1) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'message' => 'Plan gratuito: solo puedes tener 1 campaña activa. Actualiza a Premium para crear más.',
                    'requires_upgrade' => true,
                ], 403));
            }
        }

        $data['user_id'] = $userId;
        $data['tenant_id'] = $tenantId;
        $data['estado'] = 'activa';

        return $this->campanaRepo->create($data);
    }

    public function actualizar($campana, array $data)
    {
        return $this->campanaRepo->update($campana, $data);
    }

    public function cerrar($campana)
    {
        if ($campana->estado === 'cerrada') {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'La campaña ya está cerrada.',
            ], 409));
        }

        $pendientes = $this->campanaRepo->getCierresPendientes($campana);

        if ($pendientes['cuentas_por_cobrar'] > 0 || $pendientes['envios_pendientes_liquidacion'] > 0) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'No se puede cerrar la campaña: existen pendientes abiertos.',
                'errors' => $pendientes,
            ], 409));
        }

        $balance = $this->campanaRepo->calcularBalance($campana);

        return $this->campanaRepo->update($campana, [
            'estado' => 'cerrada',
            'fecha_cierre' => now(),
            'balance_neto_final' => $balance['margen_ganancia_neta'],
        ]);
    }

    public function balance($campana)
    {
        return $this->campanaRepo->calcularBalance($campana);
    }
}
