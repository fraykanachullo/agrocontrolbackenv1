<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaccionFinancieraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campana_id' => $this->campana_id,
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'monto' => (float) $this->monto,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'estado_pago' => $this->estado_pago,
            'created_at' => $this->created_at,
        ];
    }
}
