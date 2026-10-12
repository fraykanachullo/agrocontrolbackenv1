<?php

namespace App\Repositories\Eloquent;

use App\Models\Campana;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentCampanaRepository implements CampanaRepositoryInterface
{
    public function __construct(protected Campana $model) {}

    public function getAllByUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('fecha_inicio', 'desc')
            ->paginate($perPage);
    }

    public function findByIdAndUser(int $id, int $userId)
    {
        return $this->model->where('user_id', $userId)->findOrFail($id);
    }

    public function countActivasByUser(int $userId): int
    {
        return $this->model->where('user_id', $userId)
            ->where('estado', 'activa')
            ->count();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($campana, array $data)
    {
        $campana->update($data);
        return $campana;
    }

    public function getCierresPendientes($campana): array
    {
        $cuentasPorCobrar = $campana->transaccionesFinancieras()
            ->where('estado_pago', 'por_cobrar')
            ->count();

        $enviosPendientes = $campana->cosechasEnvios()
            ->where('estado', 'pendiente_de_liquidacion')
            ->count();

        return [
            'cuentas_por_cobrar' => $cuentasPorCobrar,
            'envios_pendientes_liquidacion' => $enviosPendientes,
        ];
    }

    public function calcularBalance($campana): array
    {
        $totalIngresos = $campana->transaccionesFinancieras()
            ->where('tipo', 'ingreso')
            ->where('estado_pago', 'pagado')
            ->sum('monto');

        $totalIngresosLiquidados = $campana->cosechasEnvios()
            ->where('estado', 'liquidado')
            ->sum('monto_neto_final');

        $totalEgresos = $campana->transaccionesFinancieras()
            ->where('tipo', 'egreso')
            ->sum('monto');

        return [
            'total_inversion' => round($totalEgresos, 2),
            'total_recaudado' => round($totalIngresos + $totalIngresosLiquidados, 2),
            'margen_ganancia_neta' => round(($totalIngresos + $totalIngresosLiquidados) - $totalEgresos, 2),
            'cuentas_por_cobrar_pendientes' => round($campana->transaccionesFinancieras()
                ->where('estado_pago', 'por_cobrar')
                ->sum('monto'), 2),
            'envios_pendientes_liquidacion' => $campana->cosechasEnvios()
                ->where('estado', 'pendiente_de_liquidacion')
                ->count(),
            'moneda' => 'PEN',
        ];
    }

    public function findOrCreateByUuid(string $uuidLocal, array $data)
    {
        $existing = $this->model->where('uuid_local', $uuidLocal)->first();

        if ($existing) {
            $existing->update($data);
            return $existing;
        }

        return $this->model->create(array_merge($data, ['uuid_local' => $uuidLocal]));
    }

    public function getUpdatedAtSince(int $tenantId, $since): Collection
    {
        return $this->model->where('tenant_id', $tenantId)
            ->where('updated_at', '>', $since)
            ->limit(2000)
            ->get();
    }
}
