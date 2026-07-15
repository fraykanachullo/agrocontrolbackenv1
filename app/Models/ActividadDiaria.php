<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadDiaria extends Model
{
    use HasFactory;

    protected $table = 'actividades_diarias';

    protected $fillable = [
        'campana_id',
        'descripcion',
        'fecha',
        'uuid_local',
        'is_synced'
    ];

    protected $casts = [
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