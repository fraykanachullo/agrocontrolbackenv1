<?php

namespace App\Repositories\Eloquent;

use App\Models\CosechaEnvio;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCosechaEnvioRepository implements CosechaEnvioRepositoryInterface
{
    public function __construct(protected CosechaEnvio $model) {}

    public function getByCampana(int $campanaId, ?string $estado = null): Collection
    {
        $query = $this->model->where('campana_id', $campanaId);

        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query->orderBy('fecha_envio', 'desc')->get();
    }

    public function findById(int $id)
    {
        return $this->model->with('campana')->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($cosecha, array $data)
    {
        $cosecha->update($data);
        return $cosecha;
    }

    public function sumMontoNetoLiquidado(int $campanaId): float
    {
        return (float) $this->model->where('campana_id', $campanaId)
            ->where('estado', 'liquidado')
            ->sum('monto_neto_final');
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
