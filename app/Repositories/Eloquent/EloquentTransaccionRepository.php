<?php

namespace App\Repositories\Eloquent;

use App\Models\TransaccionFinanciera;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTransaccionRepository implements TransaccionRepositoryInterface
{
    public function __construct(protected TransaccionFinanciera $model) {}

    public function getByCampana(int $campanaId, ?string $tipo = null, ?string $categoria = null, ?string $fechaDesde = null, ?string $fechaHasta = null): Collection
    {
        $query = $this->model->where('campana_id', $campanaId);

        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        if ($categoria) {
            $query->where('categoria', $categoria);
        }
        if ($fechaDesde) {
            $query->whereDate('fecha', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha', '<=', $fechaHasta);
        }

        return $query->orderBy('fecha', 'desc')->get();
    }

    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($transaccion, array $data)
    {
        $transaccion->update($data);
        return $transaccion;
    }

    public function delete($transaccion): bool
    {
        return $transaccion->delete();
    }

    public function sumMontoByTipo(int $campanaId, string $tipo): float
    {
        return (float) $this->model->where('campana_id', $campanaId)
            ->where('tipo', $tipo)
            ->sum('monto');
    }

    public function sumMontoByTipoAndEstado(int $campanaId, string $tipo, string $estadoPago): float
    {
        return (float) $this->model->where('campana_id', $campanaId)
            ->where('tipo', $tipo)
            ->where('estado_pago', $estadoPago)
            ->sum('monto');
    }

    public function findOrCreateByUuid(string $uuidLocal, array $data)
    {
        $existing = $this->model->where('uuid_local', $uuidLocal)->first();

        if ($existing) {
            $existing->update($data);
            return $existing;
        }

        return $this->model->create(array_merge($data, ['uuid_local' => $uuidLocal]));
    }

    public function getUpdatedAtSince(int $tenantId, $since): Collection
    {
        return $this->model->whereHas('campana', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('updated_at', '>', $since)->get();
    }
}
