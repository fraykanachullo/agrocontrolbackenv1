<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cosechas_envios', function (Blueprint $table) {
            $table->enum('modo_cosecha', ['matricial_filas', 'por_categorias', 'peso_directo'])
                ->nullable()
                ->after('tipo_venta');
            $table->enum('tipo_calculo_venta', ['por_kilo', 'por_tonelada', 'por_unidad'])
                ->nullable()
                ->after('modo_cosecha');
            $table->string('unidad_empaque', 50)->nullable()->after('tipo_calculo_venta');
            $table->unsignedInteger('total_bultos')->nullable()->after('unidad_empaque');
            $table->decimal('peso_total_kg', 12, 2)->nullable()->after('total_bultos');
        });

        Schema::create('cosecha_envio_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cosecha_envio_id')
                ->constrained('cosechas_envios')
                ->cascadeOnDelete();
            $table->string('categoria', 60)->nullable();
            $table->string('simbolo', 20)->nullable();
            $table->unsignedInteger('posicion_fila')->nullable();
            $table->unsignedInteger('cantidad_bultos')->nullable();
            $table->decimal('peso_promedio_bulto_kg', 10, 2)->nullable();
            $table->decimal('peso_kg', 12, 2)->nullable();
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->decimal('monto_parcial', 14, 2)->nullable();
            $table->string('uuid_local', 36)->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamps();

            $table->index('cosecha_envio_id');
            $table->index('uuid_local');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosecha_envio_detalles');

        Schema::table('cosechas_envios', function (Blueprint $table) {
            $table->dropColumn([
                'modo_cosecha',
                'tipo_calculo_venta',
                'unidad_empaque',
                'total_bultos',
                'peso_total_kg',
            ]);
        });
    }
};