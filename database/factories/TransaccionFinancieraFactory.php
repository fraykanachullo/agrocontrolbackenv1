<?php

namespace Database\Factories;

use App\Models\Campana;
use App\Models\TransaccionFinanciera;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaccionFinancieraFactory extends Factory
{
    protected $model = TransaccionFinanciera::class;

    public function definition(): array
    {
        $tipo = fake()->randomElement(['ingreso', 'egreso']);

        $egresoCategorias = [
            'insumos_fertilizantes', 'insumos_semillas', 'insumos_plaguicidas',
            'mano_de_obra', 'maquinaria', 'transporte', 'riego', 'otros',
        ];

        $ingresoCategorias = [null, 'venta_directa', 'anticipo'];

        return [
            'campana_id' => Campana::factory(),
            'tipo' => $tipo,
            'categoria' => $tipo === 'egreso'
                ? fake()->randomElement($egresoCategorias)
                : fake()->randomElement($ingresoCategorias),
            'monto' => fake()->randomFloat(2, 50, 5000),
            'descripcion' => fake()->sentence(5),
            'fecha' => fake()->dateTimeBetween('-3 months', 'now'),
            'estado_pago' => fake()->randomElement(['pagado', 'por_cobrar']),
            'is_synced' => fake()->boolean(70),
        ];
    }

    public function egreso(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'egreso',
            'estado_pago' => 'pagado',
        ]);
    }

    public function ingreso(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'ingreso',
        ]);
    }

    public function porCobrar(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_pago' => 'por_cobrar',
        ]);
    }
}
