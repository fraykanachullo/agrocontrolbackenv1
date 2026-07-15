<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            $table->uuid('uuid_local')->nullable()->unique()->after('id');
        });

        Schema::table('actividades_diarias', function (Blueprint $table) {
            $table->uuid('uuid_local')->nullable()->unique()->after('id');
        });

        Schema::table('transacciones_financieras', function (Blueprint $table) {
            $table->uuid('uuid_local')->nullable()->unique()->after('id');
        });

        Schema::table('cosechas_envios', function (Blueprint $table) {
            $table->uuid('uuid_local')->nullable()->unique()->after('id');
        });

        Schema::table('evidencias_fotos', function (Blueprint $table) {
            $table->uuid('uuid_local')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            $table->dropColumn('uuid_local');
        });

        Schema::table('actividades_diarias', function (Blueprint $table) {
            $table->dropColumn('uuid_local');
        });

        Schema::table('transacciones_financieras', function (Blueprint $table) {
            $table->dropColumn('uuid_local');
        });

        Schema::table('cosechas_envios', function (Blueprint $table) {
            $table->dropColumn('uuid_local');
        });

        Schema::table('evidencias_fotos', function (Blueprint $table) {
            $table->dropColumn('uuid_local');
        });
    }
};
