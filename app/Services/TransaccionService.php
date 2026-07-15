<?php

namespace App\Services;

use App\Models\TransaccionFinanciera;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransaccionService
{
    public function __construct(
        protected TransaccionRepositoryInterface $repo,
    ) {}

    public function listar(int $campanaId, ?string $tipo = null, ?string $categoria = null, ?string $fechaDesde = null, ?string $fechaHasta = null)
    {
        return $this->repo->getByCampana($campanaId, $tipo, $categoria, $fechaDesde, $fechaHasta);
    }

    public function crear(array $data, int $campanaId)
    {
        $data['campana_id'] = $campanaId;
        return $this->repo->create($data);
    }

    public function actualizar($transaccion, array $data)
    {
        return $this->repo->update($transaccion, $data);
    }

    public function eliminar($transaccion)
    {
        if ($transaccion->campana->estado === 'cerrada') {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'No se puede anular transacciones de una campaña cerrada.',
            ], 403));
        }

        return $this->repo->delete($transaccion);
    }
}
