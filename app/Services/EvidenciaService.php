<?php

namespace App\Services;

use App\Models\ActividadDiaria;
use App\Models\Campana;
use App\Models\TransaccionFinanciera;
use App\Repositories\Contracts\EvidenciaRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;

class EvidenciaService
{
    public function __construct(
        protected EvidenciaRepositoryInterface $repo,
    ) {}

    /**
     * La foto solo se puede subir o ver si el registro es de una campaña del usuario
     * (el scope de tenant de Campana descarta las ajenas).
     */
    public static function asegurarRelacionDelUsuario(int $relacionId, string $relacionTipo): void
    {
        $relacion = match ($relacionTipo) {
            'transaccion' => TransaccionFinanciera::find($relacionId),
            'actividad' => ActividadDiaria::find($relacionId),
            default => null,
        };

        if ($relacion === null || Campana::find($relacion->campana_id) === null) {
            throw new AuthorizationException('Registro no encontrado.');
        }
    }

    public function subir($archivo, int $relacionId, string $relacionTipo)
    {
        self::asegurarRelacionDelUsuario($relacionId, $relacionTipo);
        $fechaDir = now()->format('Y/m/d');
        $path = $archivo->store("evidencias/{$fechaDir}", 'public');

        $evidencia = $this->repo->create([
            'relacion_id' => $relacionId,
            'relacion_tipo' => $relacionTipo,
            'ruta_archivo' => $path,
        ]);

        return [
            'id' => $evidencia->id,
            'ruta' => $path,
            'url' => Storage::url($path),
        ];
    }

    public function listar(int $relacionId, string $relacionTipo)
    {
        self::asegurarRelacionDelUsuario($relacionId, $relacionTipo);
        return $this->repo->getByRelacion($relacionId, $relacionTipo);
    }
}
