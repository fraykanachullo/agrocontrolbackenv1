<?php

namespace Tests\Feature\Api\V1;

use App\Models\ActividadDiaria;
use App\Models\Campana;
use App\Models\TransaccionFinanciera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function setupUser(): User
    {
        $user = User::factory()->premium()->create();
        $user->update(['tenant_id' => $user->id]);

        return $user;
    }

    private function setupCampana(User $user): Campana
    {
        return Campana::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);
    }

    public function test_kpis_requieren_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/campanas/1/dashboard');

        $response->assertStatus(401);
    }

    public function test_obtener_kpi_dashboard(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        TransaccionFinanciera::factory()->egreso()->create([
            'campana_id' => $campana->id,
            'monto' => 2000,
            'fecha' => '2026-08-01',
        ]);
        TransaccionFinanciera::factory()->ingreso()->create([
            'campana_id' => $campana->id,
            'monto' => 5000,
            'estado_pago' => 'pagado',
            'fecha' => '2026-09-01',
        ]);
        TransaccionFinanciera::factory()->ingreso()->porCobrar()->create([
            'campana_id' => $campana->id,
            'monto' => 800,
            'fecha' => '2026-09-02',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campanas/{$campana->id}/dashboard");

        $response->assertStatus(200)
            ->assertJsonPath('data.total_inversion', 2000)
            ->assertJsonPath('data.total_recaudado', 5000)
            ->assertJsonPath('data.margen_ganancia_neta', 3000)
            ->assertJsonPath('data.cuentas_por_cobrar_pendientes', 800)
            ->assertJsonPath('data.moneda', 'PEN');
    }

    /** Un envío liquidado cuenta una sola vez en lo recaudado. */
    public function test_envio_liquidado_no_se_cuenta_dos_veces(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $envio = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 100,
                'conductor' => 'Juan',
                'mercado_destino' => 'Lima',
                'fecha_envio' => '2026-09-01',
            ])->json('data.id');

        $this->actingAs($user, 'sanctum')->patchJson("/api/v1/cosechas-envios/{$envio}/liquidar", [
            'precio_unitario' => 30,
            'descuentos_logistica' => 500,
            'fecha_liquidacion' => '2026-09-04',
        ])->assertOk();

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/campanas/{$campana->id}/dashboard")
            ->assertOk()
            ->assertJsonPath('data.total_recaudado', 2500);
    }

    public function test_historial_devuelve_actividades_y_transacciones(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        TransaccionFinanciera::factory()->egreso()->create([
            'campana_id' => $campana->id,
            'monto' => 300,
            'fecha' => '2026-09-01',
        ]);
        ActividadDiaria::factory()->create([
            'campana_id' => $campana->id,
            'descripcion' => 'Riego de parcela',
            'fecha' => '2026-09-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campanas/{$campana->id}/historial?fecha_desde=2026-09-01&fecha_hasta=2026-09-01");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['transacciones', 'actividades']])
            ->assertJsonCount(1, 'data.transacciones')
            ->assertJsonCount(1, 'data.actividades');
    }

    public function test_dashboard_de_campana_no_pertenece_al_usuario(): void
    {
        $userA = $this->setupUser();
        $userB = $this->setupUser();
        $campanaB = $this->setupCampana($userB);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/campanas/{$campanaB->id}/dashboard");

        $response->assertStatus(404);
    }
}