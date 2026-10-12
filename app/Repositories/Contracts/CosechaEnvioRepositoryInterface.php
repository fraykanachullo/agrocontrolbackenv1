<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CosechaEnvioRepositoryInterface
{
    public function getByCampana(int $campanaId, ?string $estado = null, int $perPage = 20): LengthAwarePaginator;
    public function findById(int $id);
    public function create(array $data);
    public function update($cosecha, array $data);
    public function syncDetalles($cosecha, array $detalles);
    public function sumMontoNetoLiquidado(int $campanaId): float;
    public function findOrCreateByUuid(string $uuidLocal, array $data);
    public function findByUuid(string $uuidLocal);
    public function getUpdatedAtSince(int $tenantId, $since): Collection;
    public function getDetallesUpdatedAtSince(int $tenantId, $since): Collection;
}
