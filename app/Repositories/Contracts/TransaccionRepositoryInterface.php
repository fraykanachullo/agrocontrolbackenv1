<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface TransaccionRepositoryInterface
{
    public function getByCampana(int $campanaId, ?string $tipo = null, ?string $categoria = null, ?string $fechaDesde = null, ?string $fechaHasta = null): Collection;
    public function findById(int $id);
    public function create(array $data);
    public function update($transaccion, array $data);
    public function delete($transaccion): bool;
    public function sumMontoByTipo(int $campanaId, string $tipo): float;
    public function sumMontoByTipoAndEstado(int $campanaId, string $tipo, string $estadoPago): float;
    public function findOrCreateByUuid(string $uuidLocal, array $data);
    public function getUpdatedAtSince(int $tenantId, $since): Collection;
}
