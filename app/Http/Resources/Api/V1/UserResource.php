<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_completo' => $this->nombre_completo,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'rol' => $this->rol,
            'plan_suscripcion' => $this->plan_suscripcion,
            'fecha_inicio_suscripcion' => $this->fecha_inicio_suscripcion,
            'fecha_fin_suscripcion' => $this->fecha_fin_suscripcion,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at,
        ];
    }
}
