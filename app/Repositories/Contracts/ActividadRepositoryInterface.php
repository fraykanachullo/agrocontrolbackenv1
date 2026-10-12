<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActividadRepositoryInterface
{
    public function getByCampana(int $campanaId, ?string $fechaDesde = null, ?string $fechaHasta = null, int $perPage = 20): LengthAwarePaginator;
    public function create(array $data);
    public function findOrCreateByUuid(string $uuidLocal, array $data);
    public function getUpdatedAtSince(int $tenantId, $since): Collection;
}
