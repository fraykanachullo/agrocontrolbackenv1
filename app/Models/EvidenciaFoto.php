<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenciaFoto extends Model
{
    use HasFactory;

    protected $table = 'evidencias_fotos';

    protected $fillable = [
        'relacion_id',
        'relacion_tipo',
        'ruta_archivo',
        'uuid_local',
        'is_synced'
    ];

    protected $casts = [
        'is_synced' => 'boolean'
    ];

    // Relación polimórfica
    public function relacion()
    {
        return $this->morphTo();
    }
}