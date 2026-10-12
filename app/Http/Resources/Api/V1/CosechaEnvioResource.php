<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CosechaEnvioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detalles = $this->relationLoaded('detalles')
            ? $this->detalles->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'categoria' => $detalle->categoria,
                    'simbolo' => $detalle->simbolo,
                    'posicion_fila' => $detalle->posicion_fila,
                    'cantidad_bultos' => $detalle->cantidad_bultos,
                    'peso_promedio_bulto_kg' => $detalle->peso_promedio_bulto_kg !== null ? (float) $detalle->peso_promedio_bulto_kg : null,
                    'peso_kg' => $detalle->peso_kg !== null ? (float) $detalle->peso_kg : null,
                    'peso_linea_kg' => round($detalle->peso_linea_kg, 2),
                    'precio_venta' => $detalle->precio_venta !== null ? (float) $detalle->precio_venta : null,
                    'monto_parcial' => $detalle->monto_parcial !== null ? (float) $detalle->monto_parcial : null,
                    'uuid_local' => $detalle->uuid_local,
                ];
            })->values()
            : null;

        return [
            'id' => $this->id,
            'campana_id' => $this->campana_id,
            'tipo_venta' => $this->tipo_venta,
            'modo_cosecha' => $this->modo_cosecha,
            'tipo_calculo_venta' => $this->tipo_calculo_venta,
            'unidad_empaque' => $this->unidad_empaque,
            'total_bultos' => $this->total_bultos,
            'peso_total_kg' => $this->peso_total_kg !== null ? (float) $this->peso_total_kg : null,
            'cantidad_unidades' => (float) $this->cantidad_unidades,
            'conductor' => $this->conductor,
            'mercado_destino' => $this->mercado_destino,
            'estado' => $this->estado,
            'precio_unitario' => $this->precio_unitario ? (float) $this->precio_unitario : null,
            'descuentos_logistica' => $this->descuentos_logistica ? (float) $this->descuentos_logistica : null,
            'monto_neto_final' => $this->monto_neto_final ? (float) $this->monto_neto_final : null,
            'fecha_envio' => $this->fecha_envio?->format('Y-m-d'),
            'fecha_liquidacion' => $this->fecha_liquidacion?->format('Y-m-d'),
            'detalles' => $detalles,
            'created_at' => $this->created_at,
        ];
    }
}