<?php

namespace Tests\Feature\Api\V1;

use App\Models\Campana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncTransaccionesTest extends TestCase
{
    use RefreshDatabase;

    private function setupCampana(): array
    {
        $user = User::factory()->premium()->create();
        $user->update(['tenant_id' => $user->id]);
        $campana = Campana::factory()->create(['user_id' => $user->id, 'tenant_id' => $user->id]);

        return [$user, $campana];
    }

    /** La app guarda los gastos como tipo "gasto" y el fiado como "pendiente". */
    public function test_sincroniza_gasto_con_el_formato_de_la_app(): void
    {
        [$user, $campana] = $this->setupCampana();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/transacciones', [
            'registros' => [[
                'uuid_local' => 'b7b1c2d0-0000-4000-8000-000000000001',
                'campana_id' => $campana->id,
                'tipo' => 'gasto',
                'categoria' => 'Mano de obra',
                'etapa' => 'Cosecha',
                'monto' => 535,
                'descripcion' => '5 trabajadores × S/ 100.00 · Cosecha',
                'fecha' => '2026-10-11T09:15:00',
                'estado_pago' => 'pendiente',
            ]],
        ]);

        $response->assertOk()->assertJsonCount(1, 'data.confirmados')->assertJsonCount(0, 'data.rechazados');

        $this->assertDatabaseHas('transacciones_financieras', [
            'campana_id' => $campana->id,
            'tipo' => 'egreso',
            'etapa' => 'Cosecha',
            'estado_pago' => 'por_pagar',
            'monto' => 535,
        ]);
    }

    public function test_ingreso_pendiente_queda_por_cobrar(): void
    {
        [$user, $campana] = $this->setupCampana();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/transacciones', [
            'registros' => [[
                'uuid_local' => 'b7b1c2d0-0000-4000-8000-000000000002',
                'campana_id' => $campana->id,
                'tipo' => 'ingreso',
                'categoria' => 'Venta en campo',
                'monto' => 800,
                'fecha' => '2026-10-11',
                'estado_pago' => 'pendiente',
            ]],
        ])->assertOk()->assertJsonCount(1, 'data.confirmados');

        $this->assertDatabaseHas('transacciones_financieras', [
            'tipo' => 'ingreso',
            'estado_pago' => 'por_cobrar',
        ]);
    }
}
