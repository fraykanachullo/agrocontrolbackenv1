<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - etapa: etapa del cultivo del gasto (Preparación, Siembra, Mantenimiento,
 *   Cosecha…), para el reporte "gasto por etapa".
 * - estado_pago pasa de enum a texto para admitir "por_pagar" (gastos al fiado).
 */
return new class extends Migration
{
    public function up(): void
    {
        // En PostgreSQL el enum de Laravel es un CHECK que change() no elimina.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transacciones_financieras DROP CONSTRAINT IF EXISTS transacciones_financieras_estado_pago_check');
        }

        Schema::table('transacciones_financieras', function (Blueprint $table) {
            $table->string('etapa', 30)->nullable()->after('categoria');
            $table->string('estado_pago', 20)->default('pagado')->change();
        });
    }

    public function down(): void
    {
        Schema::table('transacciones_financieras', function (Blueprint $table) {
            $table->dropColumn('etapa');
            $table->enum('estado_pago', ['pagado', 'por_cobrar'])->default('pagado')->change();
        });
    }
};
