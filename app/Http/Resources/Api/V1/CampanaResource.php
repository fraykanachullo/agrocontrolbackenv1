<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampanaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'cultivo' => $this->cultivo,
            'extension' => (float) $this->extension,
            'tipo_extension' => $this->tipo_extension,
            'latitud' => $this->latitud ? (float) $this->latitud : null,
            'longitud' => $this->longitud ? (float) $this->longitud : null,
            'estado' => $this->estado,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_cierre' => $this->fecha_cierre?->format('Y-m-d'),
            'balance_neto_final' => $this->balance_neto_final ? (float) $this->balance_neto_final : null,
            'created_at' => $this->created_at,
        ];
    }
}
