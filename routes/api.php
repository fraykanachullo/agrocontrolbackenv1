<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampanaController;
use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\TransaccionController;
use App\Http\Controllers\Api\CosechaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvidenciaController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\SyncController;

Route::prefix('v1')->group(function () {

    // =============================================
    // ===== RUTAS PÚBLICAS (sin autenticación) =====
    // =============================================
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // =============================================
    // ===== RUTAS PROTEGIDAS (requieren token) =====
    // =============================================
    Route::middleware(['auth:sanctum', 'auth.tenant'])->group(function () {

        // ---------- AUTH ----------
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'user']);

        // ---------- CAMPAÑAS ----------
        Route::prefix('campanas')->group(function () {
            Route::get('/', [CampanaController::class, 'index']);
            Route::post('/', [CampanaController::class, 'store']);
            Route::get('/{id}', [CampanaController::class, 'show']);
            Route::put('/{id}', [CampanaController::class, 'update']);
            Route::post('/{id}/cerrar', [CampanaController::class, 'cerrar']);
            Route::get('/{id}/balance', [CampanaController::class, 'balance']);

            // ---- Actividades (subrecurso) ----
            Route::get('/{campanaId}/actividades', [ActividadController::class, 'index']);
            Route::post('/{campanaId}/actividades', [ActividadController::class, 'store']);

            // ---- Transacciones (subrecurso) ----
            Route::get('/{campanaId}/transacciones', [TransaccionController::class, 'index']);
            Route::post('/{campanaId}/transacciones', [TransaccionController::class, 'store']);

            // ---- Cosechas/Envíos (subrecurso) ----
            Route::get('/{campanaId}/cosechas-envios', [CosechaController::class, 'index']);
            Route::post('/{campanaId}/cosechas-envios', [CosechaController::class, 'store']);

            // ---- Dashboard (subrecurso) ----
            Route::get('/{campanaId}/dashboard', [DashboardController::class, 'show']);
            Route::get('/{campanaId}/historial', [DashboardController::class, 'historial']);
        });

        // ---------- TRANSACCIONES (CRUD individual) ----------
        Route::prefix('transacciones')->group(function () {
            Route::put('/{id}', [TransaccionController::class, 'update']);
            Route::delete('/{id}', [TransaccionController::class, 'destroy']);
        });

        // ---------- COSECHAS/ENVÍOS (individual) ----------
        Route::prefix('cosechas-envios')->group(function () {
            Route::get('/{id}', [CosechaController::class, 'show']);
            Route::patch('/{id}/liquidar', [CosechaController::class, 'liquidar']);
        });

        // ---------- EVIDENCIAS ----------
        Route::post('/evidencias', [EvidenciaController::class, 'upload']);
        Route::get('/evidencias', [EvidenciaController::class, 'index']);

        // ---------- REPORTES (Premium) ----------
        Route::middleware('subscription.plan')->prefix('reportes')->group(function () {
            Route::get('/{campanaId}/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
            Route::get('/{campanaId}/excel', [ReporteController::class, 'excel'])->name('reportes.excel');
        });

        // ---------- SINCRONIZACIÓN (Offline-First) ----------
        Route::prefix('sync')->group(function () {
            Route::post('/campanas', [SyncController::class, 'campanas']);
            Route::post('/actividades', [SyncController::class, 'actividades']);
            Route::post('/transacciones', [SyncController::class, 'transacciones']);
            Route::post('/cosechas-envios', [SyncController::class, 'cosechas']);
            Route::post('/cosechas-envios/liquidar', [SyncController::class, 'liquidarCosechas']);
            Route::post('/evidencias/upload', [SyncController::class, 'evidencias']);
            Route::get('/pull', [SyncController::class, 'pull']);
        });
    });
});