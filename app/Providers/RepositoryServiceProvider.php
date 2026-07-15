<?php

namespace App\Providers;

use App\Models\ActividadDiaria;
use App\Models\Campana;
use App\Models\CosechaEnvio;
use App\Models\EvidenciaFoto;
use App\Models\TransaccionFinanciera;
use App\Models\User;
use App\Repositories\Contracts\ActividadRepositoryInterface;
use App\Repositories\Contracts\CampanaRepositoryInterface;
use App\Repositories\Contracts\CosechaEnvioRepositoryInterface;
use App\Repositories\Contracts\EvidenciaRepositoryInterface;
use App\Repositories\Contracts\TransaccionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentActividadRepository;
use App\Repositories\Eloquent\EloquentCampanaRepository;
use App\Repositories\Eloquent\EloquentCosechaEnvioRepository;
use App\Repositories\Eloquent\EloquentEvidenciaRepository;
use App\Repositories\Eloquent\EloquentTransaccionRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, function () {
            return new EloquentUserRepository(new User());
        });

        $this->app->bind(CampanaRepositoryInterface::class, function () {
            return new EloquentCampanaRepository(new Campana());
        });

        $this->app->bind(ActividadRepositoryInterface::class, function () {
            return new EloquentActividadRepository(new ActividadDiaria());
        });

        $this->app->bind(TransaccionRepositoryInterface::class, function () {
            return new EloquentTransaccionRepository(new TransaccionFinanciera());
        });

        $this->app->bind(CosechaEnvioRepositoryInterface::class, function () {
            return new EloquentCosechaEnvioRepository(new CosechaEnvio());
        });

        $this->app->bind(EvidenciaRepositoryInterface::class, function () {
            return new EloquentEvidenciaRepository(new EvidenciaFoto());
        });
    }

    public function boot(): void
    {
    }
}
