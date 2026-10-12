<?php

namespace App\Repositories\Eloquent;

use App\Models\CosechaEnvio;
use App\Models\CosechaEnvioDetalle;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentCosechaEnvioRepository implements CosechaEnvioRepositoryInterface
{
    public function __construct(protected CosechaEnvio $model) {}

    public function getByCampana(int $campanaId, ?string $estado = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->where('campana_id', $campanaId);

        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query->orderBy('fecha_envio', 'desc')->paginate($perPage);
    }

    public function findById(int $id)
    {
        return $this->model->with(['campana', 'detalles'])->findOrFail($id);
    }

    public function findByUuid(string $uuidLocal)
    {
        return $this->model->where('uuid_local', $uuidLocal)->first();
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

    public function syncDetalles($cosecha, array $detalles)
    {
        $cosecha->detalles()->delete();

        foreach ($detalles as $detalle) {
            $cosecha->detalles()->create([
                'categoria' => $detalle['categoria'] ?? null,
                'simbolo' => $detalle['simbolo'] ?? null,
                'posicion_fila' => $detalle['posicion_fila'] ?? null,
                'cantidad_bultos' => $detalle['cantidad_bultos'] ?? null,
                'peso_promedio_bulto_kg' => $detalle['peso_promedio_bulto_kg'] ?? null,
                'peso_kg' => $detalle['peso_kg'] ?? null,
                'uuid_local' => $detalle['uuid_local'] ?? null,
                'is_synced' => true,
            ]);
        }
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
        })->where('updated_at', '>', $since)->limit(2000)->get();
    }

    public function getDetallesUpdatedAtSince(int $tenantId, $since): Collection
    {
        return CosechaEnvioDetalle::whereHas('cosechaEnvio.campana', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('updated_at', '>', $since)->limit(2000)->get();
    }
}
