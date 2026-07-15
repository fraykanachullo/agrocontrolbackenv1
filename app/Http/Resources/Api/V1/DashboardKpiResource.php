<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardKpiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_inversion' => $this->resource['total_inversion'],
            'total_recaudado' => $this->resource['total_recaudado'],
            'margen_ganancia_neta' => $this->resource['margen_ganancia_neta'],
            'cuentas_por_cobrar_pendientes' => $this->resource['cuentas_por_cobrar_pendientes'],
            'envios_pendientes_liquidacion' => $this->resource['envios_pendientes_liquidacion'],
            'moneda' => $this->resource['moneda'],
        ];
    }
}
