<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface EvidenciaRepositoryInterface
{
    public function getByRelacion(int $relacionId, string $relacionTipo): Collection;
    public function create(array $data);
}
