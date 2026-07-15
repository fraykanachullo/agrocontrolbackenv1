<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaccionFinanciera extends Model
{
    use HasFactory;

    protected $table = 'transacciones_financieras';

    protected $fillable = [
        'campana_id',
        'tipo',
        'categoria',
        'monto',
        'descripcion',
        'fecha',
        'estado_pago',
        'uuid_local',
        'is_synced'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
        'is_synced' => 'boolean'
    ];

    public function campana()
    {
        return $this->belongsTo(Campana::class);
    }

    public function evidencias()
    {
        return $this->morphMany(EvidenciaFoto::class, 'relacion');
    }
}