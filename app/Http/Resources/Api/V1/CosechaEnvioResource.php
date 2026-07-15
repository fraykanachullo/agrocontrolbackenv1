<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CosechaEnvioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campana_id' => $this->campana_id,
            'tipo_venta' => $this->tipo_venta,
            'cantidad_unidades' => (float) $this->cantidad_unidades,
            'conductor' => $this->conductor,
            'mercado_destino' => $this->mercado_destino,
            'estado' => $this->estado,
            'precio_unitario' => $this->precio_unitario ? (float) $this->precio_unitario : null,
            'descuentos_logistica' => $this->descuentos_logistica ? (float) $this->descuentos_logistica : null,
            'monto_neto_final' => $this->monto_neto_final ? (float) $this->monto_neto_final : null,
            'fecha_envio' => $this->fecha_envio?->format('Y-m-d'),
            'fecha_liquidacion' => $this->fecha_liquidacion?->format('Y-m-d'),
            'created_at' => $this->created_at,
        ];
    }
}
