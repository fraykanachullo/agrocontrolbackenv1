<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campana extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'user_id',
        'tenant_id',
        'nombre',
        'cultivo',
        'extension',
        'tipo_extension',
        'latitud',
        'longitud',
        'estado',
        'fecha_inicio',
        'fecha_cierre',
        'balance_neto_final',
        'uuid_local',
        'is_synced'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_cierre' => 'date',
        'extension' => 'decimal:2',
        'latitud' => 'decimal:6',
        'longitud' => 'decimal:6',
        'balance_neto_final' => 'decimal:2',
        'is_synced' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actividades()
    {
        return $this->hasMany(ActividadDiaria::class);
    }

    public function transaccionesFinancieras()
    {
        return $this->hasMany(TransaccionFinanciera::class, 'campana_id');
    }

    public function cosechasEnvios()
    {
        return $this->hasMany(CosechaEnvio::class, 'campana_id');
    }
}
