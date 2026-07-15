<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('cultivo', 60);
            $table->decimal('extension', 10, 2);
            $table->enum('tipo_extension', ['topo', 'hectarea']);
            $table->decimal('latitud', 9, 6)->nullable();
            $table->decimal('longitud', 9, 6)->nullable();
            $table->enum('estado', ['activa', 'cerrada'])->default('activa');
            $table->date('fecha_inicio');
            $table->date('fecha_cierre')->nullable();
            $table->decimal('balance_neto_final', 12, 2)->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanas');
    }
};