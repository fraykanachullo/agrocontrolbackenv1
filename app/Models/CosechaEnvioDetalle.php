<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CosechaEnvioDetalle extends Model
{
    use HasFactory;

    protected $table = 'cosecha_envio_detalles';

    protected $fillable = [
        'cosecha_envio_id',
        'categoria',
        'simbolo',
        'posicion_fila',
        'cantidad_bultos',
        'peso_promedio_bulto_kg',
        'peso_kg',
        'precio_venta',
        'monto_parcial',
        'uuid_local',
        'is_synced',
    ];

    protected $casts = [
        'posicion_fila' => 'integer',
        'cantidad_bultos' => 'integer',
        'peso_promedio_bulto_kg' => 'decimal:2',
        'peso_kg' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'monto_parcial' => 'decimal:2',
        'is_synced' => 'boolean',
    ];

    /**
     * Peso total de la línea (peso_kg o derivado del promedio por bulto).
     */
    public function getPesoLineaKgAttribute(): float
    {
        if ($this->peso_kg !== null) {
            return (float) $this->peso_kg;
        }
        if ($this->peso_promedio_bulto_kg && $this->cantidad_bultos) {
            return (float) $this->cantidad_bultos * (float) $this->peso_promedio_bulto_kg;
        }
        return 0.0;
    }

    /**
     * Monto de la línea según la unidad de cálculo de venta.
     */
    public function montoUnidad(string $tipoCalculo, float $precio): float
    {
        if ($tipoCalculo === 'por_unidad') {
            return (float) ($this->cantidad_bultos ?? 0) * $precio;
        }
        return $this->peso_linea_kg * $precio;
    }

    public function cosechaEnvio()
    {
        return $this->belongsTo(CosechaEnvio::class, 'cosecha_envio_id');
    }
}