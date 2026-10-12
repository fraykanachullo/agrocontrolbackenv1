<?php

namespace Tests\Feature\Api\V1;

use App\Models\Campana;
use App\Models\TransaccionFinanciera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaccionTest extends TestCase
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

    public function test_registrar_gasto(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/transacciones", [
                'tipo' => 'egreso',
                'categoria' => 'insumos_fertilizantes',
                'monto' => 850.50,
                'descripcion' => 'Fertilizante NPK',
                'fecha' => '2026-09-01',
                'estado_pago' => 'pagado',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tipo', 'egreso')
            ->assertJsonPath('data.monto', 850.50);

        $this->assertDatabaseHas('transacciones_financieras', [
            'campana_id' => $campana->id,
            'tipo' => 'egreso',
            'monto' => 850.50,
        ]);
    }

    public function test_registrar_ingreso_por_cobrar(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/transacciones", [
                'tipo' => 'ingreso',
                'monto' => 1200.00,
                'descripcion' => 'Venta anticipada',
                'fecha' => '2026-09-01',
                'estado_pago' => 'por_cobrar',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.estado_pago', 'por_cobrar');
    }

    public function test_registrar_transaccion_con_datos_invalidos(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/transacciones", [
                'tipo' => 'transferencia',
                'monto' => 0,
                'fecha' => '',
                'estado_pago' => 'pendiente',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['tipo', 'monto', 'fecha', 'estado_pago']]);
    }

    public function test_listar_transacciones_con_filtros(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        TransaccionFinanciera::factory()->create([
            'campana_id' => $campana->id,
            'tipo' => 'egreso',
            'monto' => 100,
            'fecha' => '2026-08-01',
        ]);
        TransaccionFinanciera::factory()->create([
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'estado_pago' => 'pagado',
            'monto' => 500,
            'fecha' => '2026-09-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campanas/{$campana->id}/transacciones?tipo=egreso");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo', 'egreso')
            ->assertJsonStructure(['meta' => ['total']]);
    }

    public function test_transaccion_no_accesible_de_otra_campana(): void
    {
        $userA = $this->setupUser();
        $userB = $this->setupUser();
        $campanaB = $this->setupCampana($userB);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/campanas/{$campanaB->id}/transacciones");

        $response->assertStatus(404);
    }

    public function test_no_se_puede_anular_transaccion_de_campana_cerrada(): void
    {
        $user = $this->setupUser();
        $campana = Campana::factory()->cerrada()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
        ]);
        $transaccion = TransaccionFinanciera::factory()->egreso()->create([
            'campana_id' => $campana->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/transacciones/{$transaccion->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('transacciones_financieras', ['id' => $transaccion->id]);
    }

    public function test_actualizar_transaccion(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);
        $transaccion = TransaccionFinanciera::factory()->egreso()->create([
            'campana_id' => $campana->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/transacciones/{$transaccion->id}", [
                'tipo' => 'egreso',
                'monto' => 999.99,
                'descripcion' => 'Actualizado',
                'fecha' => '2026-09-05',
                'estado_pago' => 'pagado',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.monto', 999.99);
    }
}