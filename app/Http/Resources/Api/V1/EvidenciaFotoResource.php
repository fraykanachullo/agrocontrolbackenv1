<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvidenciaFotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'relacion_id' => $this->relacion_id,
            'relacion_tipo' => $this->relacion_tipo,
            'ruta_archivo' => $this->ruta_archivo,
            'created_at' => $this->created_at,
        ];
    }
}
