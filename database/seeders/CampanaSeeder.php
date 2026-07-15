<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campana;
use App\Models\ActividadDiaria;
use App\Models\TransaccionFinanciera;
use App\Models\CosechaEnvio;
use App\Models\User;
use Carbon\Carbon;

class CampanaSeeder extends Seeder
{
    public function run(): void
    {
        $agricultor = User::where('email', 'agricultor@test.com')->first();

        if (!$agricultor) {
            return;
        }

        // === CAMPAÑA 1: Activa con datos completos ===
        $campana1 = Campana::create([
            'user_id' => $agricultor->id,
            'tenant_id' => $agricultor->tenant_id,
            'nombre' => 'Campaña de Tomate - Primavera 2026',
            'cultivo' => 'Tomate',
            'extension' => 3.5,
            'tipo_extension' => 'hectarea',
            'latitud' => -12.046374,
            'longitud' => -77.042793,
            'estado' => 'activa',
            'fecha_inicio' => '2026-03-01',
            'is_synced' => true,
        ]);

        // 5 actividades
        ActividadDiaria::factory()->count(5)->create([
            'campana_id' => $campana1->id,
        ]);

        // 6 transacciones: 4 egresos + 2 ingresos pagados
        TransaccionFinanciera::factory()->egreso()->count(4)->create([
            'campana_id' => $campana1->id,
            'fecha' => Carbon::now()->subMonths(2),
        ]);

        TransaccionFinanciera::factory()->ingreso()->count(2)->create([
            'campana_id' => $campana1->id,
            'estado_pago' => 'pagado',
            'fecha' => Carbon::now()->subMonth(),
        ]);

        // 1 ingreso por cobrar
        TransaccionFinanciera::factory()->ingreso()->porCobrar()->create([
            'campana_id' => $campana1->id,
            'monto' => 1200.00,
            'descripcion' => 'Venta de tomate a mercado central',
            'fecha' => Carbon::now()->subDays(15),
        ]);

        // 2 cosechas: 1 parcela (auto-liquidada) + 1 mercado (pendiente)
        CosechaEnvio::factory()->parcela()->create([
            'campana_id' => $campana1->id,
            'cantidad_unidades' => 200,
            'precio_unitario' => 12.50,
            'descuentos_logistica' => 50.00,
            'monto_neto_final' => 2450.00,
            'fecha_envio' => Carbon::now()->subDays(20),
            'fecha_liquidacion' => Carbon::now()->subDays(20),
        ]);

        CosechaEnvio::factory()->mercado()->create([
            'campana_id' => $campana1->id,
            'cantidad_unidades' => 150,
            'conductor' => 'Pedro López',
            'mercado_destino' => 'Mercado Central',
            'fecha_envio' => Carbon::now()->subDays(10),
        ]);

        // === CAMPAÑA 2: Activa con pocos datos (para plan gratuito) ===
        $campana2 = Campana::create([
            'user_id' => $agricultor->id,
            'tenant_id' => $agricultor->tenant_id,
            'nombre' => 'Campaña de Cebolla - Verano 2026',
            'cultivo' => 'Cebolla',
            'extension' => 2.0,
            'tipo_extension' => 'topo',
            'latitud' => -13.163792,
            'longitud' => -72.545685,
            'estado' => 'activa',
            'fecha_inicio' => '2026-01-15',
            'is_synced' => true,
        ]);

        TransaccionFinanciera::factory()->egreso()->count(3)->create([
            'campana_id' => $campana2->id,
        ]);

        // === CAMPAÑA 3: Cerrada con balance ===
        $campana3 = Campana::create([
            'user_id' => $agricultor->id,
            'tenant_id' => $agricultor->tenant_id,
            'nombre' => 'Campaña de Papa - Invierno 2025',
            'cultivo' => 'Papa',
            'extension' => 5.0,
            'tipo_extension' => 'hectarea',
            'latitud' => -13.163792,
            'longitud' => -72.545685,
            'estado' => 'cerrada',
            'fecha_inicio' => '2025-06-01',
            'fecha_cierre' => '2025-11-15',
            'balance_neto_final' => 15000.00,
            'is_synced' => true,
        ]);

        TransaccionFinanciera::factory()->egreso()->count(8)->create([
            'campana_id' => $campana3->id,
        ]);

        TransaccionFinanciera::factory()->ingreso()->count(5)->create([
            'campana_id' => $campana3->id,
            'estado_pago' => 'pagado',
        ]);

        CosechaEnvio::factory()->liquidado()->count(3)->create([
            'campana_id' => $campana3->id,
        ]);

        // === Usuario gratuito: solo 1 campaña activa (límite) ===
        $gratuito = User::where('email', 'gratuito@test.com')->first();

        if ($gratuito) {
            Campana::create([
                'user_id' => $gratuito->id,
                'tenant_id' => $gratuito->tenant_id,
                'nombre' => 'Campaña Gratis - Maíz',
                'cultivo' => 'Maíz',
                'extension' => 1.0,
                'tipo_extension' => 'hectarea',
                'latitud' => -12.0,
                'longitud' => -77.0,
                'estado' => 'activa',
                'fecha_inicio' => '2026-02-01',
                'is_synced' => true,
            ]);
        }
    }
}
