<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('tenant_id')->nullable()->after('id');
            $table->string('nombre_completo')->nullable()->after('tenant_id');
            $table->string('telefono', 20)->nullable()->after('email');
            $table->enum('plan_suscripcion', ['gratuito', 'premium'])->default('gratuito')->after('telefono');
            $table->timestamp('fecha_inicio_suscripcion')->nullable()->after('plan_suscripcion');
            $table->timestamp('fecha_fin_suscripcion')->nullable()->after('fecha_inicio_suscripcion');
            $table->enum('rol', ['propietario', 'administrador', 'colaborador'])->default('propietario')->after('fecha_fin_suscripcion');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tenant_id',
                'nombre_completo',
                'telefono',
                'plan_suscripcion',
                'fecha_inicio_suscripcion',
                'fecha_fin_suscripcion',
                'rol'
            ]);
        });
    }
};