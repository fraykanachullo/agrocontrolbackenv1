<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CosechaEnvio extends Model
{
    use HasFactory;

    protected $table = 'cosechas_envios';

    protected $fillable = [
        'campana_id',
        'tipo_venta',
        'cantidad_unidades',
        'conductor',
        'mercado_destino',
        'estado',
        'precio_unitario',
        'descuentos_logistica',
        'monto_neto_final',
        'fecha_envio',
        'fecha_liquidacion',
        'uuid_local',
        'is_synced'
    ];

    protected $casts = [
        'cantidad_unidades' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'descuentos_logistica' => 'decimal:2',
        'monto_neto_final' => 'decimal:2',
        'fecha_envio' => 'date',
        'fecha_liquidacion' => 'date',
        'is_synced' => 'boolean'
    ];

    public function campana()
    {
        return $this->belongsTo(Campana::class);
    }
}