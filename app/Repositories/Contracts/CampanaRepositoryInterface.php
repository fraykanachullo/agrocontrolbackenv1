<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CampanaRepositoryInterface
{
    public function getAllByUser(int $userId, int $perPage = 20): LengthAwarePaginator;
    public function findByIdAndUser(int $id, int $userId);
    public function countActivasByUser(int $userId): int;
    public function create(array $data);
    public function update($campana, array $data);
    public function getCierresPendientes($campana): array;
    public function calcularBalance($campana): array;
    public function findOrCreateByUuid(string $uuidLocal, array $data);
    public function getUpdatedAtSince(int $tenantId, $since): Collection;
}
