<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CosechaEnvioRepositoryInterface
{
    public function getByCampana(int $campanaId, ?string $estado = null): Collection;
    public function findById(int $id);
    public function create(array $data);
    public function update($cosecha, array $data);
    public function sumMontoNetoLiquidado(int $campanaId): float;
    public function findOrCreateByUuid(string $uuidLocal, array $data);
    public function getUpdatedAtSince(int $tenantId, $since): Collection;
}
