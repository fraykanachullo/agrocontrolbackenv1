<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'nombre_completo',
        'email',
        'password',
        'tenant_id',
        'telefono',
        'plan_suscripcion',
        'fecha_inicio_suscripcion',
        'fecha_fin_suscripcion',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fecha_inicio_suscripcion' => 'datetime',
            'fecha_fin_suscripcion' => 'datetime',
        ];
    }

    public function campanas()
    {
        return $this->hasMany(Campana::class);
    }
}
