<?php

namespace Tests\Feature\Api\V1;

use App\Models\Campana;
use App\Models\CosechaEnvio;
use App\Models\TransaccionFinanciera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** La sincronización no debe permitir escribir ni leer en campañas de otro usuario. */
class SyncSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConCampana(): array
    {
        $user = User::factory()->premium()->create();
        $user->update(['tenant_id' => $user->id]);
        $campana = Campana::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $user->id,
            'uuid_local' => fake()->uuid(),
        ]);

        return [$user, $campana];
    }

    private function gasto(array $extra): array
    {
        return array_merge([
            'uuid_local' => fake()->uuid(),
            'tipo' => 'gasto',
            'categoria' => 'Insumos',
            'monto' => 120,
            'descripcion' => 'Abono',
            'fecha' => '2026-11-01T08:00:00',
            'estado_pago' => 'pagado',
        ], $extra);
    }

    public function test_no_se_puede_registrar_en_la_campana_de_otro_usuario(): void
    {
        [$yo] = $this->usuarioConCampana();
        [, $ajena] = $this->usuarioConCampana();

        $this->actingAs($yo, 'sanctum')->postJson('/api/v1/sync/transacciones', [
            'registros' => [$this->gasto(['campana_id' => $ajena->id])],
        ])->assertOk()->assertJsonCount(0, 'data.confirmados')->assertJsonCount(1, 'data.rechazados');

        $this->assertDatabaseMissing('transacciones_financieras', ['campana_id' => $ajena->id]);
    }

    public function test_el_telefono_identifica_la_campana_por_su_uuid(): void
    {
        [$yo, $campana] = $this->usuarioConCampana();

        $this->actingAs($yo, 'sanctum')->postJson('/api/v1/sync/transacciones', [
            'registros' => [$this->gasto(['campana_id' => 999, 'campana_uuid' => $campana->uuid_local])],
        ])->assertOk()->assertJsonCount(1, 'data.confirmados');

        $this->assertDatabaseHas('transacciones_financieras', ['campana_id' => $campana->id, 'monto' => 120]);
    }

    public function test_no_se_puede_sobrescribir_un_registro_ajeno_con_su_uuid(): void
    {
        [$yo, $mia] = $this->usuarioConCampana();
        [, $ajena] = $this->usuarioConCampana();
        $ajeno = TransaccionFinanciera::factory()->create([
            'campana_id' => $ajena->id,
            'monto' => 500,
            'uuid_local' => fake()->uuid(),
        ]);

        $this->actingAs($yo, 'sanctum')->postJson('/api/v1/sync/transacciones', [
            'registros' => [$this->gasto(['uuid_local' => $ajeno->uuid_local, 'campana_uuid' => $mia->uuid_local, 'monto' => 1])],
        ])->assertOk()->assertJsonCount(1, 'data.rechazados');

        $this->assertDatabaseHas('transacciones_financieras', ['id' => $ajeno->id, 'campana_id' => $ajena->id, 'monto' => 500]);
    }

    public function test_liquidar_con_el_total_de_la_boleta(): void
    {
        [$yo, $campana] = $this->usuarioConCampana();
        $envio = CosechaEnvio::factory()->create([
            'campana_id' => $campana->id,
            'estado' => 'pendiente_de_liquidacion',
            'cantidad_unidades' => 100,
            'peso_total_kg' => null,
            'uuid_local' => fake()->uuid(),
        ]);

        $this->actingAs($yo, 'sanctum')->postJson('/api/v1/sync/cosechas-envios/liquidar', [
            'registros' => [[
                'uuid_local' => $envio->uuid_local,
                'cosecha_uuid' => $envio->uuid_local,
                'precio_unitario' => null,
                'descuentos_logistica' => 4000,
                'fecha_liquidacion' => '2026-11-10T10:00:00',
                'detalles' => [],
                'bruto_total' => 50000,
            ]],
        ])->assertOk()->assertJsonCount(1, 'data.confirmados');

        $this->assertEquals(46000, (float) $envio->fresh()->monto_neto_final);
    }

    public function test_no_se_puede_subir_una_foto_a_un_registro_ajeno(): void
    {
        Storage::fake('public');
        [$yo] = $this->usuarioConCampana();
        [, $ajena] = $this->usuarioConCampana();
        $ajeno = TransaccionFinanciera::factory()->create(['campana_id' => $ajena->id]);

        $this->actingAs($yo, 'sanctum')->postJson('/api/v1/evidencias', [
            'archivo' => UploadedFile::fake()->image('boleta.jpg'),
            'relacion_id' => $ajeno->id,
            'relacion_tipo' => 'transaccion',
        ])->assertForbidden();

        $this->assertDatabaseMissing('evidencias_fotos', ['relacion_id' => $ajeno->id]);
    }

    public function test_el_login_limita_los_intentos(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', ['correo_electronico' => 'nadie@agrocontrol.com', 'password' => 'incorrecta']);
        }

        $this->postJson('/api/v1/auth/login', ['correo_electronico' => 'nadie@agrocontrol.com', 'password' => 'incorrecta'])
            ->assertStatus(429);
    }
}
