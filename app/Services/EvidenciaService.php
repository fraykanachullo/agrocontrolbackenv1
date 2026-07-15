<?php

namespace App\Services;

use App\Repositories\Contracts\EvidenciaRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class EvidenciaService
{
    public function __construct(
        protected EvidenciaRepositoryInterface $repo,
    ) {}

    public function subir($archivo, int $relacionId, string $relacionTipo)
    {
        $fechaDir = now()->format('Y/m/d');
        $path = $archivo->store("evidencias/{$fechaDir}", 'public');

        $evidencia = $this->repo->create([
            'relacion_id' => $relacionId,
            'relacion_tipo' => $relacionTipo,
            'ruta_archivo' => $path,
        ]);

        return [
            'id' => $evidencia->id,
            'ruta' => $path,
            'url' => Storage::url($path),
        ];
    }

    public function listar(int $relacionId, string $relacionTipo)
    {
        return $this->repo->getByRelacion($relacionId, $relacionTipo);
    }
}
