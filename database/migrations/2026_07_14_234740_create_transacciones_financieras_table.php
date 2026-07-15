<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones_financieras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained()->onDelete('cascade');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('categoria', 60)->nullable();
            $table->decimal('monto', 12, 2);
            $table->string('descripcion', 255)->nullable();
            $table->date('fecha');
            $table->enum('estado_pago', ['pagado', 'por_cobrar']);
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones_financieras');
    }
};