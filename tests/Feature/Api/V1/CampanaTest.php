<?php

namespace Tests\Feature\Api\V1;

use App\Models\Campana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampanaTest extends TestCase
{
    use RefreshDatabase;

    private function userPremium(): User
    {
        $user = User::factory()->premium()->create();
        $user->update(['tenant_id' => $user->id]);

        return $user;
    }

    private function userGratuito(): User
    {
        $user = User::factory()->gratuito()->create();
        $user->update(['tenant_id' => $user->id]);

        return $user;
    }

    public function test_listar_campanas_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/campanas');

        $response->assertStatus(401);
    }

    public function test_crear_campana(): void
    {
        $user = $this->userPremium();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/campanas', [
                'nombre' => 'Campaña de Tomate',
                'cultivo' => 'Tomate',
                'extension' => 2.5,
                'tipo_extension' => 'hectarea',
                'fecha_inicio' => '2026-09-01',
                'latitud' => -12.0464,
                'longitud' => -77.0428,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Campaña de Tomate')
            ->assertJsonStructure(['data' => ['id', 'nombre', 'cultivo', 'estado']]);

        $this->assertDatabaseHas('campanas', [
            'nombre' => 'Campaña de Tomate',
            'user_id' => $user->id,
            'tenant_id' => $user->id,
            'estado' => 'activa',
        ]);
    }

    public function test_crear_campana_con_datos_invalidos(): void
    {
        $user = $this->userPremium();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/campanas', [
                'nombre' => '',
                'cultivo' => 'Tomate',
                'extension' => -1,
                'tipo_extension' => 'invalido',
                'fecha_inicio' => 'no-es-fecha',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    public function test_plan_gratuito_limita_a_una_campana_activa(): void
    {
        $user = $this->userGratuito();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/campanas', [
                'nombre' => 'Primera Campaña',
                'cultivo' => 'Maíz',
                'extension' => 1,
                'tipo_extension' => 'topo',
                'fecha_inicio' => '2026-09-01',
            ])
            ->assertStatus(201);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/campanas', [
                'nombre' => 'Segunda Campaña',
                'cultivo' => 'Cebolla',
                'extension' => 1,
                'tipo_extension' => 'topo',
                'fecha_inicio' => '2026-09-01',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('requires_upgrade', true);
    }

    public function test_usuario_solo_ve_sus_campanas(): void
    {
        $userA = $this->userPremium();
        $userB = $this->userPremium();

        Campana::factory()->create(['user_id' => $userA->id, 'tenant_id' => $userA->id, 'nombre' => 'Campaña de A']);
        Campana::factory()->create(['user_id' => $userB->id, 'tenant_id' => $userB->id, 'nombre' => 'Campaña de B']);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/campanas');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Campaña de A');
    }

    public function test_listar_campanas_con_paginacion(): void
    {
        $user = $this->userPremium();

        for ($i = 1; $i <= 5; $i++) {
            Campana::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $user->id,
                'nombre' => "Campaña $i",
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/campanas?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_mostrar_campana(): void
    {
        $user = $this->userPremium();
        $campana = Campana::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campanas/{$campana->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $campana->id)
            ->assertJsonPath('success', true);
    }

    public function test_mostrar_campana_de_otro_usuario_devuelve_404(): void
    {
        $userA = $this->userPremium();
        $userB = $this->userPremium();
        $campana = Campana::factory()->create([
            'user_id' => $userB->id,
            'tenant_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/campanas/{$campana->id}");

        $response->assertStatus(404);
    }

    public function test_cerrar_campana_sin_pendientes(): void
    {
        $user = $this->userPremium();
        $campana = Campana::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cerrar");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'cerrada');

        $this->assertNotNull($campana->fresh()->fecha_cierre);
    }

    public function test_no_se_puede_cerrar_campana_con_cuentas_por_cobrar(): void
    {
        $user = $this->userPremium();
        $campana = Campana::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        \App\Models\TransaccionFinanciera::factory()->create([
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'estado_pago' => 'por_cobrar',
            'monto' => 500,
            'fecha' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cerrar");

        $response->assertStatus(409);
    }

    public function test_balance_campana(): void
    {
        $user = $this->userPremium();
        $campana = Campana::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);

        \App\Models\TransaccionFinanciera::factory()->create([
            'campana_id' => $campana->id,
            'tipo' => 'egreso',
            'monto' => 1000,
            'fecha' => now()->toDateString(),
        ]);
        \App\Models\TransaccionFinanciera::factory()->create([
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'estado_pago' => 'pagado',
            'monto' => 2500,
            'fecha' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campanas/{$campana->id}/balance");

        $response->assertStatus(200)
            ->assertJsonPath('data.total_inversion', 1000)
            ->assertJsonPath('data.total_recaudado', 2500)
            ->assertJsonPath('data.margen_ganancia_neta', 1500);
    }
}