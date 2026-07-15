<?php

namespace Database\Factories;

use App\Models\Campana;
use App\Models\CosechaEnvio;
use Illuminate\Database\Eloquent\Factories\Factory;

class CosechaEnvioFactory extends Factory
{
    protected $model = CosechaEnvio::class;

    public function definition(): array
    {
        $tipoVenta = fake()->randomElement(['parcela', 'mercado']);
        $cantidad = fake()->randomFloat(2, 10, 500);

        return [
            'campana_id' => Campana::factory(),
            'tipo_venta' => $tipoVenta,
            'cantidad_unidades' => $cantidad,
            'conductor' => fake()->randomElement([null, fake()->name()]),
            'mercado_destino' => $tipoVenta === 'mercado'
                ? fake()->randomElement(['Mercado Central', 'Mercado del Sur', 'Exportación', 'Frutería La Paz'])
                : null,
            'estado' => $tipoVenta === 'parcela' ? 'liquidado' : 'pendiente_de_liquidacion',
            'precio_unitario' => $tipoVenta === 'parcela' ? fake()->randomFloat(2, 5, 30) : null,
            'descuentos_logistica' => $tipoVenta === 'parcela' ? fake()->randomFloat(2, 0, 200) : null,
            'monto_neto_final' => $tipoVenta === 'parcela' ? fake()->randomFloat(2, 100, 5000) : null,
            'fecha_envio' => fake()->dateTimeBetween('-2 months', 'now'),
            'fecha_liquidacion' => $tipoVenta === 'parcela' ? fake()->dateTimeBetween('-2 months', 'now') : null,
            'is_synced' => fake()->boolean(70),
        ];
    }

    public function parcela(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_venta' => 'parcela',
            'estado' => 'liquidado',
        ]);
    }

    public function mercado(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_venta' => 'mercado',
            'estado' => 'pendiente_de_liquidacion',
        ]);
    }

    public function liquidado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'liquidado',
        ]);
    }
}
