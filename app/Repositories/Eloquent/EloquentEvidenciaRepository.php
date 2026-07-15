<?php

namespace App\Repositories\Eloquent;

use App\Models\EvidenciaFoto;
use App\Repositories\Contracts\EvidenciaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentEvidenciaRepository implements EvidenciaRepositoryInterface
{
    public function __construct(protected EvidenciaFoto $model) {}

    public function getByRelacion(int $relacionId, string $relacionTipo): Collection
    {
        return $this->model->where('relacion_id', $relacionId)
            ->where('relacion_tipo', $relacionTipo)
            ->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }
}
