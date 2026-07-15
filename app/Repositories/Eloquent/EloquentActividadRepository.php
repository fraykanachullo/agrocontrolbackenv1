<?php

namespace App\Repositories\Eloquent;

use App\Models\ActividadDiaria;
use App\Repositories\Contracts\ActividadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentActividadRepository implements ActividadRepositoryInterface
{
    public function __construct(protected ActividadDiaria $model) {}

    public function getByCampana(int $campanaId, ?string $fechaDesde = null, ?string $fechaHasta = null): Collection
    {
        $query = $this->model->where('campana_id', $campanaId);

        if ($fechaDesde) {
            $query->whereDate('fecha', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha', '<=', $fechaHasta);
        }

        return $query->orderBy('fecha', 'desc')->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
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
