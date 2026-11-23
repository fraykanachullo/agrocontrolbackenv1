<?php

namespace App\Services;

use App\Repositories\Contracts\TransaccionRepositoryInterface;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DashboardService
{
    public function __construct(
        protected TransaccionRepositoryInterface $transaccionRepo,
        protected CosechaEnvioRepositoryInterface $cosechaRepo,
    ) {}

    public function obtenerKpis($campana): array
    {
        $totalInversion = $campana->transaccionesFinancieras()
            ->where('tipo', 'egreso')
            ->sum('monto');

        // Liquidar un envío ya crea su transacción de ingreso; sumar además
        // monto_neto_final contaría la misma venta dos veces.
        $totalRecaudado = $campana->transaccionesFinancieras()
            ->where('tipo', 'ingreso')
            ->where('estado_pago', 'pagado')
            ->sum('monto');

        $cuentasPorCobrar = $campana->transaccionesFinancieras()
            ->where('estado_pago', 'por_cobrar')
            ->sum('monto');

        $enviosPendientes = $campana->cosechasEnvios()
            ->where('estado', 'pendiente_de_liquidacion')
            ->count();

        return [
            'total_inversion' => round($totalInversion, 2),
            'total_recaudado' => round($totalRecaudado, 2),
            'margen_ganancia_neta' => round($totalRecaudado - $totalInversion, 2),
            'cuentas_por_cobrar_pendientes' => round($cuentasPorCobrar, 2),
            'envios_pendientes_liquidacion' => $enviosPendientes,
            'moneda' => 'PEN',
        ];
    }

    public function obtenerHistorial($campana, ?string $fecha = null, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $query = $campana->transaccionesFinancieras();

        if ($fecha) {
            $query->whereDate('fecha', $fecha);
        }

        if ($fechaDesde && $fechaHasta) {
            $query->whereDate('fecha', '>=', $fechaDesde)
                  ->whereDate('fecha', '<=', $fechaHasta);
        }

        $transacciones = $query->orderBy('fecha', 'desc')->get();

        $actividades = $campana->actividades()
            ->whereDate('fecha', '>=', $fechaDesde ?? '1970-01-01')
            ->when($fechaHasta, function ($q) use ($fechaHasta) {
                return $q->whereDate('fecha', '<=', $fechaHasta);
            })
            ->orderBy('fecha', 'desc')
            ->get();

        return [
            'transacciones' => $transacciones,
            'actividades' => $actividades,
        ];
    }
}
