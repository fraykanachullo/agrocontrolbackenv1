<?php

namespace Database\Factories;

use App\Models\EvidenciaFoto;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvidenciaFotoFactory extends Factory
{
    protected $model = EvidenciaFoto::class;

    public function definition(): array
    {
        return [
            'relacion_id' => 1,
            'relacion_tipo' => fake()->randomElement(['transaccion', 'actividad']),
            'ruta_archivo' => 'evidencias/' . fake()->dateTimeBetween('-3 months', 'now')->format('Y/m/d') . '/' . fake()->uuid() . '.jpg',
            'is_synced' => fake()->boolean(70),
        ];
    }
}
