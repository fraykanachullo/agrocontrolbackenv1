<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias_fotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('relacion_id');
            $table->string('relacion_tipo', 40);
            $table->string('ruta_archivo', 255);
            $table->boolean('is_synced')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_fotos');
    }
};