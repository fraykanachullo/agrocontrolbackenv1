<?php

namespace App\Services;

use App\Models\TransaccionFinanciera;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

class CosechaEnvioService
{
    public function __construct(
        protected CosechaEnvioRepositoryInterface $cosechaRepo,
        protected TransaccionRepositoryInterface $transaccionRepo,
    ) {}

    public function listar(int $campanaId, ?string $estado = null)
    {
        return $this->cosechaRepo->getByCampana($campanaId, $estado);
    }

    public function obtener(int $id)
    {
        return $this->cosechaRepo->findById($id);
    }

    public function crear(array $data, int $campanaId)
    {
        $data['campana_id'] = $campanaId;
        $data['estado'] = $data['tipo_venta'] === 'parcela' ? 'liquidado' : 'pendiente_de_liquidacion';

        $cosecha = $this->cosechaRepo->create($data);

        if ($data['tipo_venta'] === 'parcela') {
            $precio = $data['precio_unitario'] ?? 0;
            $descuentos = $data['descuentos_logistica'] ?? 0;
            $neto = ($cosecha->cantidad_unidades * $precio) - $descuentos;

            $this->cosechaRepo->update($cosecha, [
                'precio_unitario' => $precio,
                'descuentos_logistica' => $descuentos,
                'monto_neto_final' => $neto,
                'fecha_liquidacion' => $cosecha->fecha_envio,
                'estado' => 'liquidado',
            ]);

            $this->transaccionRepo->create([
                'campana_id' => $campanaId,
                'tipo' => 'ingreso',
                'categoria' => null,
                'monto' => $neto,
                'descripcion' => "Venta directa en parcela - {$cosecha->cantidad_unidades} unidades",
                'fecha' => $cosecha->fecha_envio,
                'estado_pago' => 'pagado',
                'is_synced' => false,
            ]);

            return $cosecha;
        }

        return $cosecha;
    }

    public function liquidar($envio, array $data)
    {
        if ($envio->estado !== 'pendiente_de_liquidacion') {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Este envío ya está liquidado.',
            ], 409));
        }

        $descuentos = $data['descuentos_logistica'] ?? 0;
        $neto = ($envio->cantidad_unidades * $data['precio_unitario']) - $descuentos;

        $this->cosechaRepo->update($envio, [
            'precio_unitario' => $data['precio_unitario'],
            'descuentos_logistica' => $descuentos,
            'monto_neto_final' => $neto,
            'fecha_liquidacion' => $data['fecha_liquidacion'],
            'estado' => 'liquidado',
        ]);

        $this->transaccionRepo->create([
            'campana_id' => $envio->campana_id,
            'tipo' => 'ingreso',
            'categoria' => null,
            'monto' => $neto,
            'descripcion' => "Liquidación envío a {$envio->mercado_destino} - {$envio->cantidad_unidades} unidades",
            'fecha' => $envio->fecha_liquidacion,
            'estado_pago' => 'pagado',
            'is_synced' => false,
        ]);

        return [
            'id' => $envio->id,
            'estado' => 'liquidado',
            'precio_unitario' => $data['precio_unitario'],
            'descuentos_logistica' => $descuentos,
            'monto_neto_final' => $neto,
        ];
    }
}
