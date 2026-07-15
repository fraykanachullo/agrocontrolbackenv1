<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosechas_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained()->onDelete('cascade');
            $table->enum('tipo_venta', ['parcela', 'mercado']);
            $table->decimal('cantidad_unidades', 10, 2);
            $table->string('conductor', 100)->nullable();
            $table->string('mercado_destino', 100)->nullable();
            $table->enum('estado', ['pendiente_de_liquidacion', 'liquidado']);
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->decimal('descuentos_logistica', 10, 2)->nullable();
            $table->decimal('monto_neto_final', 12, 2)->nullable();
            $table->date('fecha_envio');
            $table->date('fecha_liquidacion')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosechas_envios');
    }
};