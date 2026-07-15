<?php

namespace Database\Factories;

use App\Models\Campana;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampanaFactory extends Factory
{
    protected $model = Campana::class;

    public function definition(): array
    {
        $cultivos = ['Tomate', 'Cebolla', 'Papa', 'Maíz', 'Alfalfa', 'Frijol', 'Ají', 'Uva'];
        $estados = ['activa', 'cerrada'];

        return [
            'user_id' => User::factory(),
            'nombre' => fake()->sentence(3),
            'cultivo' => fake()->randomElement($cultivos),
            'extension' => fake()->randomFloat(2, 1, 10),
            'tipo_extension' => fake()->randomElement(['topo', 'hectarea']),
            'latitud' => fake()->latitude(-15, -5),
            'longitud' => fake()->longitude(-82, -68),
            'estado' => 'activa',
            'fecha_inicio' => fake()->dateTimeBetween('-6 months', 'now'),
            'is_synced' => fake()->boolean(80),
        ];
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrada',
            'fecha_cierre' => fake()->dateTimeBetween('-1 month', 'now'),
            'balance_neto_final' => fake()->randomFloat(2, -5000, 20000),
        ]);
    }
}
