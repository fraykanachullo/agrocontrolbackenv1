<?php

namespace Database\Factories;

use App\Models\ActividadDiaria;
use App\Models\Campana;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActividadDiariaFactory extends Factory
{
    protected $model = ActividadDiaria::class;

    public function definition(): array
    {
        $actividades = [
            'Se realizó riego por aspersión en toda la parcela.',
            'Aplicación de fertilizante NPK en zona norte.',
            'Control de plagas con insecticida biológico.',
            'Siembra de semillas de segunda época.',
            'Cosecha parcial de tomate maduro.',
            'Fumigación preventiva contra mildiu.',
            'Laboreo de suelo para preparar nueva siembra.',
            'Corte y empaque de cebolla en campo.',
            'Trasplante de plántulas de ají.',
            'Monitoreo de plagas - sin hallazgos significativos.',
        ];

        return [
            'campana_id' => Campana::factory(),
            'descripcion' => fake()->randomElement($actividades),
            'fecha' => fake()->dateTimeBetween('-3 months', 'now'),
            'is_synced' => fake()->boolean(70),
        ];
    }
}
