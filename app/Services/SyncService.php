<?php

namespace App\Services;

use App\Repositories\Contracts\ActividadRepositoryInterface;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use App\Repositories\Contracts\EvidenciaRepositoryInterface;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use App\Services\CosechaEnvioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncService
{
    public function __construct(
        protected CampanaRepositoryInterface $campanaRepo,
        protected ActividadRepositoryInterface $actividadRepo,
        protected TransaccionRepositoryInterface $transaccionRepo,
        protected CosechaEnvioRepositoryInterface $cosechaRepo,
        protected EvidenciaRepositoryInterface $evidenciaRepo,
        protected CosechaEnvioService $cosechaEnvioService,
    ) {}

    public function syncCampanas(int $userId, array $registros): array
    {
        $confirmados = [];
        $rechazados = [];

        foreach ($registros as $registro) {
            try {
                DB::beginTransaction();

                $data = [
                    'user_id' => $userId,
                    'tenant_id' => auth()->user()->tenant_id,
                    'nombre' => $registro['nombre'],
                    'cultivo' => $registro['cultivo'] ?? null,
                    'extension' => $registro['extension'] ?? null,
                    'tipo_extension' => $registro['tipo_extension'] ?? 'hectarea',
                    'latitud' => $registro['latitud'] ?? null,
                    'longitud' => $registro['longitud'] ?? null,
                    'estado' => $registro['estado'] ?? 'activa',
                    'fecha_inicio' => $registro['fecha_inicio'] ?? now()->toDateString(),
                    'is_synced' => true,
                ];

                $campana = $this->campanaRepo->findOrCreateByUuid($registro['uuid_local'], $data);

                DB::commit();

                $confirmados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'id_remoto' => $campana->id,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                $rechazados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return compact('confirmados', 'rechazados');
    }

    public function syncActividades(int $userId, array $registros): array
    {
        $confirmados = [];
        $rechazados = [];

        foreach ($registros as $registro) {
            try {
                DB::beginTransaction();

                $data = [
                    'campana_id' => $registro['campana_id'],
                    'descripcion' => $registro['descripcion'],
                    'fecha' => $registro['fecha'],
                    'is_synced' => true,
                ];

                $actividad = $this->actividadRepo->findOrCreateByUuid($registro['uuid_local'], $data);

                DB::commit();

                $confirmados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'id_remoto' => $actividad->id,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                $rechazados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return compact('confirmados', 'rechazados');
    }

    public function syncTransacciones(int $userId, array $registros): array
    {
        $confirmados = [];
        $rechazados = [];

        foreach ($registros as $registro) {
            try {
                DB::beginTransaction();

                $tipo = self::normalizarTipo($registro['tipo']);
                $data = [
                    'campana_id' => $registro['campana_id'],
                    'tipo' => $tipo,
                    'categoria' => $registro['categoria'] ?? null,
                    'etapa' => $registro['etapa'] ?? null,
                    'monto' => $registro['monto'],
                    'descripcion' => $registro['descripcion'] ?? null,
                    'fecha' => $registro['fecha'],
                    'estado_pago' => self::normalizarEstadoPago($registro['estado_pago'] ?? null, $tipo),
                    'is_synced' => true,
                ];

                $transaccion = $this->transaccionRepo->findOrCreateByUuid($registro['uuid_local'], $data);

                DB::commit();

                $confirmados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'id_remoto' => $transaccion->id,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                $rechazados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return compact('confirmados', 'rechazados');
    }

    /** La app guarda los egresos como "gasto". */
    private static function normalizarTipo(string $tipo): string
    {
        return $tipo === 'gasto' ? 'egreso' : $tipo;
    }

    /**
     * Estados válidos: pagado, por_cobrar (nos deben) y por_pagar (debemos).
     * La app usa "pendiente" para ambos casos según el tipo.
     */
    private static function normalizarEstadoPago(?string $estado, string $tipo): string
    {
        return match ($estado) {
            null, 'pagado' => 'pagado',
            'pendiente', 'debe' => $tipo === 'egreso' ? 'por_pagar' : 'por_cobrar',
            default => $estado,
        };
    }

    public function syncCosechas(int $userId, array $registros): array
    {
        $confirmados = [];
        $rechazados = [];

        foreach ($registros as $registro) {
            try {
                DB::beginTransaction();

                $data = [
                    'campana_id' => $registro['campana_id'],
                    'tipo_venta' => $registro['tipo_venta'],
                    'modo_cosecha' => $registro['modo_cosecha'] ?? null,
                    'tipo_calculo_venta' => $registro['tipo_calculo_venta'] ?? null,
                    'unidad_empaque' => $registro['unidad_empaque'] ?? null,
                    'total_bultos' => $registro['total_bultos'] ?? null,
                    'peso_total_kg' => $registro['peso_total_kg'] ?? null,
                    'cantidad_unidades' => $registro['cantidad_unidades'],
                    'conductor' => $registro['conductor'] ?? null,
                    'mercado_destino' => $registro['mercado_destino'] ?? null,
                    'estado' => $registro['estado'] ?? 'pendiente_de_liquidacion',
                    'precio_unitario' => $registro['precio_unitario'] ?? null,
                    'descuentos_logistica' => $registro['descuentos_logistica'] ?? null,
                    'monto_neto_final' => $registro['monto_neto_final'] ?? null,
                    'fecha_envio' => $registro['fecha_envio'],
                    'fecha_liquidacion' => $registro['fecha_liquidacion'] ?? null,
                    'is_synced' => true,
                ];

                $cosecha = $this->cosechaRepo->findOrCreateByUuid($registro['uuid_local'], $data);

                if (!empty($registro['detalles'])) {
                    $this->cosechaRepo->syncDetalles($cosecha, $registro['detalles']);
                }

                DB::commit();

                $confirmados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'id_remoto' => $cosecha->id,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                $rechazados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return compact('confirmados', 'rechazados');
    }

    public function uploadEvidencia(int $userId, $archivo, ?string $uuidLocal, int $relacionId, string $relacionTipo): array
    {
        $fechaDir = now()->format('Y/m/d');
        $path = $archivo->store("evidencias/{$fechaDir}", 'public');

        $evidencia = $this->evidenciaRepo->create([
            'relacion_id' => $relacionId,
            'relacion_tipo' => $relacionTipo,
            'ruta_archivo' => $path,
            'uuid_local' => $uuidLocal,
            'is_synced' => true,
        ]);

        return [
            'id' => $evidencia->id,
            'uuid_local' => $uuidLocal,
            'ruta' => $path,
            'url' => Storage::url($path),
        ];
    }

    public function liquidarCosechas(int $userId, array $registros): array
    {
        $confirmados = [];
        $rechazados = [];

        $tenantId = auth()->user()->tenant_id;

        foreach ($registros as $registro) {
            try {
                DB::beginTransaction();

                $envio = $this->cosechaRepo->findByUuid($registro['cosecha_uuid'] ?? null);

                if ($envio === null || $envio->campana->tenant_id !== $tenantId) {
                    throw new \Exception('Envío no encontrado para líquidar.');
                }

                $this->cosechaEnvioService->liquidar($envio, [
                    'precio_unitario' => $registro['precio_unitario'] ?? null,
                    'descuentos_logistica' => $registro['descuentos_logistica'] ?? 0,
                    'fecha_liquidacion' => $registro['fecha_liquidacion'],
                    'detalles' => $registro['detalles'] ?? [],
                ]);

                DB::commit();

                $confirmados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'id_remoto' => $envio->id,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                $rechazados[] = [
                    'uuid_local' => $registro['uuid_local'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return compact('confirmados', 'rechazados');
    }

    public function pull(int $userId, $since): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'campanas' => $this->campanaRepo->getUpdatedAtSince($tenantId, $since),
            'actividades' => $this->actividadRepo->getUpdatedAtSince($tenantId, $since),
            'transacciones' => $this->transaccionRepo->getUpdatedAtSince($tenantId, $since),
            'cosechas_envios' => $this->cosechaRepo->getUpdatedAtSince($tenantId, $since),
            'cosecha_envio_detalles' => $this->cosechaRepo->getDetallesUpdatedAtSince($tenantId, $since),
            'since' => $since,
        ];
    }
}
