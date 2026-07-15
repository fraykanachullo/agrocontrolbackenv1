<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades_diarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained()->onDelete('cascade');
            $table->text('descripcion');
            $table->date('fecha');
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades_diarias');
    }
};