<?php

namespace App\Services;

use App\Models\CosechaEnvio;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

class CosechaEnvioService
{
    public function __construct(
        protected CosechaEnvioRepositoryInterface $cosechaRepo,
        protected TransaccionRepositoryInterface $transaccionRepo,
    ) {}

    public function listar(int $campanaId, ?string $estado = null, int $perPage = 20)
    {
        return $this->cosechaRepo->getByCampana($campanaId, $estado, $perPage);
    }

    public function obtener(int $id)
    {
        return $this->cosechaRepo->findById($id);
    }

    public function crear(array $data, int $campanaId)
    {
        $data['campana_id'] = $campanaId;
        $data['estado'] = $data['tipo_venta'] === 'parcela' ? 'liquidado' : 'pendiente_de_liquidacion';

        $detalles = $data['detalles'] ?? [];
        unset($data['detalles']);

        if (!empty($detalles)) {
            $this->aplicarTotalesDesdeDetalles($data, $detalles);
        }

        $cosecha = $this->cosechaRepo->create($data);

        if (!empty($detalles)) {
            $this->cosechaRepo->syncDetalles($cosecha, $detalles);
            $cosecha->load('detalles');
        }

        if ($data['tipo_venta'] === 'parcela') {
            $this->liquidarAutomaticamente($cosecha, (float) ($data['precio_unitario'] ?? 0), (float) ($data['descuentos_logistica'] ?? 0));
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

        $descuentos = (float) ($data['descuentos_logistica'] ?? 0);
        $detallesLiquidacion = $data['detalles'] ?? [];

        if (!empty($data['bruto_total'])) {
            // Liquidación con el total de la boleta, sin precio por calidad.
            $totalBruto = (float) $data['bruto_total'];
            $items = [];
            $precioReferencia = null;
        } elseif (!empty($detallesLiquidacion)) {
            $resultado = $this->liquidarPorDetalles($envio, $detallesLiquidacion);
            $totalBruto = $resultado['total_bruto'];
            $items = $resultado['items'];
            $precioReferencia = null;
        } else {
            $base = $envio->peso_total_kg !== null
                ? (float) $envio->peso_total_kg
                : (float) $envio->cantidad_unidades;
            $precioReferencia = (float) $data['precio_unitario'];
            $totalBruto = $base * $precioReferencia;
            $items = [];
        }

        $neto = round($totalBruto - $descuentos, 2);

        $this->cosechaRepo->update($envio, [
            'precio_unitario' => $precioReferencia,
            'descuentos_logistica' => $descuentos,
            'monto_neto_final' => $neto,
            'fecha_liquidacion' => $data['fecha_liquidacion'],
            'estado' => 'liquidado',
        ]);

        $descripcion = !empty($items)
            ? "Liquidación envío a {$envio->mercado_destino} - " . count($items) . ' partidas'
            : "Liquidación envío a {$envio->mercado_destino} - " . $envio->cantidad_unidades . ' unidades';

        $this->transaccionRepo->create([
            'campana_id' => $envio->campana_id,
            'tipo' => 'ingreso',
            'categoria' => null,
            'monto' => $neto,
            'descripcion' => $descripcion,
            'fecha' => $envio->fecha_liquidacion,
            'estado_pago' => 'pagado',
            'is_synced' => false,
        ]);

        return [
            'id' => $envio->id,
            'estado' => 'liquidado',
            'total_bruto' => round($totalBruto, 2),
            'descuentos_logistica' => $descuentos,
            'monto_neto_final' => $neto,
            'detalles' => $items,
        ];
    }

    /**
     * Cuadre horizontal/vertical: si el encabezado trae totales, estos deben
     * coincidir con la suma de los detalles.
     */
    protected function aplicarTotalesDesdeDetalles(array &$data, array $detalles): void
    {
        $sumaBultos = 0;
        $sumaPeso = 0.0;

        foreach ($detalles as $detalle) {
            $cantidad = (int) ($detalle['cantidad_bultos'] ?? 0);
            $pesoLinea = (float) ($detalle['peso_kg'] ?? 0);

            if ($pesoLinea == 0 && !empty($detalle['peso_promedio_bulto_kg'])) {
                $pesoLinea = $cantidad * (float) $detalle['peso_promedio_bulto_kg'];
            }

            $sumaBultos += $cantidad;
            $sumaPeso += $pesoLinea;
        }

        $data['total_bultos'] = $data['total_bultos'] ?? $sumaBultos;

        if (!empty($data['total_bultos']) && (int) $data['total_bultos'] !== $sumaBultos) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => "El total de bultos no cuadra: los detalles suman {$sumaBultos} y el total registrado es {$data['total_bultos']}.",
            ], 422));
        }

        $data['peso_total_kg'] = $data['peso_total_kg'] ?? round($sumaPeso, 2);

        if (!empty($data['peso_total_kg']) && abs(round((float) $data['peso_total_kg'], 2) - round($sumaPeso, 2)) > 0.01) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'El peso total no cuadra: los detalles suman ' . round($sumaPeso, 2) . ' kg y el registrado es ' . number_format((float) $data['peso_total_kg'], 2) . ' kg.',
            ], 422));
        }
    }

    /**
     * Aplica un precio por cada línea (match por id o categoría) y calcula los montos.
     */
    protected function liquidarPorDetalles($envio, array $detallesLiquidacion): array
    {
        $totalBruto = 0.0;
        $items = [];

        foreach ($envio->detalles()->get() as $detalle) {
            $precio = null;

            foreach ($detallesLiquidacion as $dl) {
                $matchId = !empty($dl['id']) && (int) $detalle->id === (int) $dl['id'];
                $matchCategoria = !empty($dl['categoria']) && $detalle->categoria === $dl['categoria'];

                if ($matchId || $matchCategoria) {
                    $precio = (float) $dl['precio_venta'];
                    break;
                }
            }

            if ($precio === null) {
                continue;
            }

            $monto = round(
                $detalle->montoUnidad($envio->tipo_calculo_venta ?? 'por_kilo', $precio),
                2
            );

            $detalle->update([
                'precio_venta' => $precio,
                'monto_parcial' => $monto,
            ]);

            $totalBruto += $monto;

            $items[] = [
                'id' => $detalle->id,
                'categoria' => $detalle->categoria,
                'cantidad_bultos' => $detalle->cantidad_bultos,
                'peso_kg' => $detalle->peso_kg,
                'peso_linea_kg' => round($detalle->peso_linea_kg, 2),
                'precio_venta' => $precio,
                'monto_parcial' => $monto,
            ];
        }

        if (empty($items)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Los detalles enviados no coinciden con las partidas del envío.',
            ], 422));
        }

        return ['total_bruto' => $totalBruto, 'items' => $items];
    }

    /**
     * Venta directa en parcela: liquida al mismo precio todas las líneas (o legacy sin detalles).
     */
    protected function liquidarAutomaticamente(CosechaEnvio $cosecha, float $precio, float $descuentos): void
    {
        $items = [];

        if ($cosecha->detalles()->exists()) {
            $detallesLiquidacion = $cosecha->detalles()->get()->map(function ($detalle) use ($precio) {
                return ['id' => $detalle->id, 'precio_venta' => $precio];
            })->all();

            $resultado = $this->liquidarPorDetalles($cosecha, $detallesLiquidacion);
            $totalBruto = $resultado['total_bruto'];
            $items = $resultado['items'];
        } else {
            $totalBruto = (float) $cosecha->cantidad_unidades * $precio;
        }

        $neto = round($totalBruto - $descuentos, 2);

        $this->cosechaRepo->update($cosecha, [
            'precio_unitario' => $precio ?: null,
            'descuentos_logistica' => $descuentos,
            'monto_neto_final' => $neto,
            'fecha_liquidacion' => $cosecha->fecha_envio,
            'estado' => 'liquidado',
        ]);

        $this->transaccionRepo->create([
            'campana_id' => $cosecha->campana_id,
            'tipo' => 'ingreso',
            'categoria' => null,
            'monto' => $neto,
            'descripcion' => !empty($items)
                ? 'Venta directa en parcela - ' . count($items) . ' partidas'
                : "Venta directa en parcela - {$cosecha->cantidad_unidades} unidades",
            'fecha' => $cosecha->fecha_envio,
            'estado_pago' => 'pagado',
            'is_synced' => false,
        ]);
    }
}