<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@agrocontrol.com'],
            [
                'name' => 'Admin AgroControl',
                'nombre_completo' => 'Admin AgroControl',
                'email' => 'admin@agrocontrol.com',
                'password' => Hash::make('password123'),
                'plan_suscripcion' => 'premium',
                'rol' => 'propietario',
                'telefono' => '+51 999 888 777',
            ]
        );

        $admin = User::where('email', 'admin@agrocontrol.com')->first();
        $admin->update(['tenant_id' => $admin->id]);

        User::firstOrCreate(
            ['email' => 'agricultor@test.com'],
            [
                'name' => 'Carlos Mendoza',
                'nombre_completo' => 'Carlos Mendoza',
                'email' => 'agricultor@test.com',
                'password' => Hash::make('password123'),
                'plan_suscripcion' => 'premium',
                'rol' => 'propietario',
                'telefono' => '+51 987 654 321',
            ]
        );

        $agricultor = User::where('email', 'agricultor@test.com')->first();
        $agricultor->update(['tenant_id' => $agricultor->id]);

        User::firstOrCreate(
            ['email' => 'gratuito@test.com'],
            [
                'name' => 'Juan Gratis',
                'nombre_completo' => 'Juan Gratis',
                'email' => 'gratuito@test.com',
                'password' => Hash::make('password123'),
                'plan_suscripcion' => 'gratuito',
                'rol' => 'propietario',
                'telefono' => '+51 911 222 333',
            ]
        );

        $gratuito = User::where('email', 'gratuito@test.com')->first();
        $gratuito->update(['tenant_id' => $gratuito->id]);
    }
}
