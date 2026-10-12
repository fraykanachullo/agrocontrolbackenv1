<?php

namespace Tests\Feature\Api\V1;

use App\Models\Campana;
use App\Models\CosechaEnvio;
use App\Models\CosechaEnvioDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CosechaTest extends TestCase
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

    public function test_registrar_salida_mercado_queda_pendiente(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 50,
                'conductor' => 'Juan Pérez',
                'mercado_destino' => 'Mercado Central',
                'fecha_envio' => '2026-09-01',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'pendiente_de_liquidacion');

        $this->assertDatabaseHas('cosechas_envios', [
            'campana_id' => $campana->id,
            'estado' => 'pendiente_de_liquidacion',
        ]);

        $this->assertDatabaseMissing('transacciones_financieras', ['campana_id' => $campana->id]);
    }

    public function test_registrar_venta_parcela_autoliquida_y_crea_ingreso(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'parcela',
                'cantidad_unidades' => 100,
                'fecha_envio' => '2026-09-01',
                'precio_unitario' => 12.50,
                'descuentos_logistica' => 25.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'liquidado')
            ->assertJsonPath('data.monto_neto_final', 1225);

        $this->assertDatabaseHas('transacciones_financieras', [
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'monto' => 1225.00,
            'estado_pago' => 'pagado',
        ]);
    }

    public function test_liquidar_envio_de_mercado(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);
        $envio = CosechaEnvio::factory()->mercado()->create([
            'campana_id' => $campana->id,
            'cantidad_unidades' => 200,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/cosechas-envios/{$envio->id}/liquidar", [
                'precio_unitario' => 15.00,
                'descuentos_logistica' => 100.00,
                'fecha_liquidacion' => '2026-09-05',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'liquidado')
            ->assertJsonPath('data.monto_neto_final', 2900);

        $this->assertDatabaseHas('cosechas_envios', [
            'id' => $envio->id,
            'estado' => 'liquidado',
            'monto_neto_final' => 2900.00,
        ]);

        $this->assertDatabaseHas('transacciones_financieras', [
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'monto' => 2900.00,
        ]);
    }

    public function test_no_se_puede_liquidar_dos_veces(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);
        $envio = CosechaEnvio::factory()->parcela()->create([
            'campana_id' => $campana->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/cosechas-envios/{$envio->id}/liquidar", [
                'precio_unitario' => 15.00,
                'descuentos_logistica' => 0,
                'fecha_liquidacion' => '2026-09-05',
            ]);

        $response->assertStatus(409);
    }

    public function test_listar_cosechas_filtrado_por_estado(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        CosechaEnvio::factory()->mercado()->create(['campana_id' => $campana->id]);
        CosechaEnvio::factory()->parcela()->create(['campana_id' => $campana->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campanas/{$campana->id}/cosechas-envios?estado=pendiente_de_liquidacion");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estado', 'pendiente_de_liquidacion');
    }

    public function test_modo_matricial_filas_guarda_detalles_y_cuadra_bultos(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 16,
                'modo_cosecha' => 'matricial_filas',
                'tipo_calculo_venta' => 'por_unidad',
                'unidad_empaque' => 'jaba',
                'conductor' => 'Juan Pérez',
                'mercado_destino' => 'Mercado Central',
                'fecha_envio' => '2026-09-01',
                'detalles' => [
                    ['posicion_fila' => 1, 'categoria' => 'I', 'cantidad_bultos' => 3],
                    ['posicion_fila' => 1, 'categoria' => 'II', 'cantidad_bultos' => 5],
                    ['posicion_fila' => 2, 'categoria' => 'I', 'cantidad_bultos' => 4],
                    ['posicion_fila' => 2, 'categoria' => 'II', 'cantidad_bultos' => 4],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'pendiente_de_liquidacion')
            ->assertJsonPath('data.modo_cosecha', 'matricial_filas')
            ->assertJsonPath('data.tipo_calculo_venta', 'por_unidad')
            ->assertJsonPath('data.total_bultos', 16)
            ->assertJsonCount(4, 'data.detalles');

        $this->assertDatabaseHas('cosecha_envio_detalles', [
            'cosecha_envio_id' => $response->json('data.id'),
            'posicion_fila' => 2,
            'categoria' => 'I',
            'cantidad_bultos' => 4,
        ]);
    }

    public function test_modo_matricial_rechaza_si_el_total_no_cuadra(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 16,
                'modo_cosecha' => 'matricial_filas',
                'tipo_calculo_venta' => 'por_unidad',
                'unidad_empaque' => 'jaba',
                'fecha_envio' => '2026-09-01',
                'total_bultos' => 20,
                'detalles' => [
                    ['posicion_fila' => 1, 'categoria' => 'I', 'cantidad_bultos' => 3],
                    ['posicion_fila' => 1, 'categoria' => 'II', 'cantidad_bultos' => 5],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_modo_por_categorias_guarda_peso_y_cuadra(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 17,
                'modo_cosecha' => 'por_categorias',
                'tipo_calculo_venta' => 'por_kilo',
                'unidad_empaque' => 'saco',
                'conductor' => 'Juan Pérez',
                'mercado_destino' => 'Mercado Central',
                'fecha_envio' => '2026-09-01',
                'total_bultos' => 17,
                'peso_total_kg' => 500,
                'detalles' => [
                    ['categoria' => '1ra', 'cantidad_bultos' => 10, 'peso_kg' => 300],
                    ['categoria' => '2da', 'cantidad_bultos' => 5, 'peso_kg' => 150],
                    ['categoria' => 'Floreadas', 'cantidad_bultos' => 2, 'peso_kg' => 50],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_bultos', 17)
            ->assertJsonPath('data.peso_total_kg', 500);

        $this->assertDatabaseHas('cosecha_envio_detalles', [
            'cosecha_envio_id' => $response->json('data.id'),
            'categoria' => 'Floreadas',
            'peso_kg' => 50.00,
        ]);
    }

    public function test_modo_peso_directo_sin_detalles_y_liquida_por_kg(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 40,
                'modo_cosecha' => 'peso_directo',
                'tipo_calculo_venta' => 'por_kilo',
                'unidad_empaque' => 'malla',
                'total_bultos' => 40,
                'peso_total_kg' => 800,
                'conductor' => 'Juan Pérez',
                'mercado_destino' => 'Mercado Central',
                'fecha_envio' => '2026-09-01',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.modo_cosecha', 'peso_directo')
            ->assertJsonPath('data.peso_total_kg', 800);

        $envioId = $response->json('data.id');

        $liquidacion = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/cosechas-envios/{$envioId}/liquidar", [
                'precio_unitario' => 5.00,
                'descuentos_logistica' => 200.00,
                'fecha_liquidacion' => '2026-09-05',
            ]);

        $liquidacion->assertStatus(200)
            ->assertJsonPath('data.estado', 'liquidado')
            ->assertJsonPath('data.monto_neto_final', 3800);

        $this->assertDatabaseHas('transacciones_financieras', [
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'monto' => 3800.00,
        ]);
    }

    public function test_liquidar_por_detalles_con_precios_por_categoria(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $creacion = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 17,
                'modo_cosecha' => 'por_categorias',
                'tipo_calculo_venta' => 'por_kilo',
                'unidad_empaque' => 'saco',
                'conductor' => 'Juan Pérez',
                'mercado_destino' => 'Mercado Central',
                'fecha_envio' => '2026-09-01',
                'detalles' => [
                    ['categoria' => '1ra', 'cantidad_bultos' => 10, 'peso_kg' => 300],
                    ['categoria' => '2da', 'cantidad_bultos' => 5, 'peso_kg' => 150],
                    ['categoria' => 'Floreadas', 'cantidad_bultos' => 2, 'peso_kg' => 50],
                ],
            ]);

        $envioId = $creacion->json('data.id');

        $liquidacion = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/cosechas-envios/{$envioId}/liquidar", [
                'fecha_liquidacion' => '2026-09-05',
                'descuentos_logistica' => 25.00,
                'detalles' => [
                    ['categoria' => '1ra', 'precio_venta' => 4.00],
                    ['categoria' => '2da', 'precio_venta' => 2.50],
                    ['categoria' => 'Floreadas', 'precio_venta' => 1.00],
                ],
            ]);

        $liquidacion->assertStatus(200)
            ->assertJsonPath('data.estado', 'liquidado')
            ->assertJsonPath('data.total_bruto', 1625)
            ->assertJsonPath('data.monto_neto_final', 1600)
            ->assertJsonCount(3, 'data.detalles');

        $this->assertDatabaseHas('cosecha_envio_detalles', [
            'cosecha_envio_id' => $envioId,
            'categoria' => '1ra',
            'precio_venta' => 4.00,
            'monto_parcial' => 1200.00,
        ]);

        $this->assertDatabaseHas('transacciones_financieras', [
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'monto' => 1600.00,
        ]);
    }

    public function test_liquidar_por_unidad_usa_cantidad_y_no_peso(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);

        $creacion = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campanas/{$campana->id}/cosechas-envios", [
                'tipo_venta' => 'mercado',
                'cantidad_unidades' => 16,
                'modo_cosecha' => 'matricial_filas',
                'tipo_calculo_venta' => 'por_unidad',
                'unidad_empaque' => 'jaba',
                'conductor' => 'Juan Pérez',
                'mercado_destino' => 'Mercado Central',
                'fecha_envio' => '2026-09-01',
                'detalles' => [
                    ['posicion_fila' => 1, 'categoria' => 'I', 'cantidad_bultos' => 8],
                    ['posicion_fila' => 1, 'categoria' => 'II', 'cantidad_bultos' => 8],
                ],
            ]);

        $envioId = $creacion->json('data.id');

        $liquidacion = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/cosechas-envios/{$envioId}/liquidar", [
                'fecha_liquidacion' => '2026-09-05',
                'detalles' => [
                    ['categoria' => 'I', 'precio_venta' => 30.00],
                    ['categoria' => 'II', 'precio_venta' => 25.00],
                ],
            ]);

        $liquidacion->assertStatus(200)
            ->assertJsonPath('data.total_bruto', 440)
            ->assertJsonPath('data.monto_neto_final', 440);

        $this->assertDatabaseHas('cosecha_envio_detalles', [
            'cosecha_envio_id' => $envioId,
            'categoria' => 'I',
            'monto_parcial' => 240.00,
        ]);
    }

    public function test_sync_crear_y_liquidar_cosecha_por_detalles(): void
    {
        $user = $this->setupUser();
        $campana = $this->setupCampana($user);
        $envioUuid = '11111111-1111-1111-1111-111111111111';

        $crear = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/cosechas-envios', [
                'registros' => [[
                    'uuid_local' => $envioUuid,
                    'campana_id' => $campana->id,
                    'tipo_venta' => 'mercado',
                    'modo_cosecha' => 'por_categorias',
                    'tipo_calculo_venta' => 'por_kilo',
                    'unidad_empaque' => 'saco',
                    'cantidad_unidades' => 15,
                    'conductor' => 'Juan Pérez',
                    'mercado_destino' => 'Mercado Central',
                    'fecha_envio' => '2026-09-01',
                    'detalles' => [
                        ['categoria' => '1ra', 'cantidad_bultos' => 10, 'peso_kg' => 300],
                        ['categoria' => '2da', 'cantidad_bultos' => 5, 'peso_kg' => 150],
                    ],
                ]],
            ]);

        $crear->assertStatus(200)
            ->assertJsonPath('data.confirmados.0.uuid_local', $envioUuid);

        $envioId = $crear->json('data.confirmados.0.id_remoto');

        $liquidar = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/cosechas-envios/liquidar', [
                'registros' => [[
                    'uuid_local' => '22222222-2222-2222-2222-222222222222',
                    'cosecha_uuid' => $envioUuid,
                    'descuentos_logistica' => 20,
                    'fecha_liquidacion' => '2026-09-05',
                    'detalles' => [
                        ['categoria' => '1ra', 'precio_venta' => 4.00],
                        ['categoria' => '2da', 'precio_venta' => 2.00],
                    ],
                ]],
            ]);

        $liquidar->assertStatus(200)
            ->assertJsonPath('data.confirmados.0.id_remoto', $envioId);

        $this->assertDatabaseHas('cosechas_envios', [
            'id' => $envioId,
            'estado' => 'liquidado',
            'monto_neto_final' => 1480.00,
        ]);

        $this->assertDatabaseHas('transacciones_financieras', [
            'campana_id' => $campana->id,
            'tipo' => 'ingreso',
            'monto' => 1480.00,
        ]);
    }
}