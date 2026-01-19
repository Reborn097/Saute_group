<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_diarios', function (Blueprint $table) {
            $table->id();

            // Opcional: folio/código
            $table->string('codigo', 50)->nullable();

            // Unidad operativa a la que pertenece el pedido diario
            $table->unsignedBigInteger('unidad_operativa_id');

            // Para forzar 2 pedidos separados
            $table->enum('tipo', ['PAN', 'TORTILLA']);

            // Semana lunes-domingo
            $table->date('semana_inicio'); // lunes
            $table->date('semana_fin');    // domingo

            // Flujo simple (ajústalo a tu sistema real)
            $table->string('estado', 30)->default('BORRADOR');

            $table->text('observaciones')->nullable();

            // Usuario que creó el pedido (si aplica)
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            // Evita duplicar un pedido del mismo tipo en la misma semana para la misma unidad operativa
            $table->unique(['unidad_operativa_id', 'semana_inicio', 'tipo'], 'uq_pd_uo_semana_tipo');

            // Índices útiles
            $table->index(['semana_inicio', 'tipo'], 'idx_pd_semana_tipo');
            $table->index(['unidad_operativa_id'], 'idx_pd_unidad');

            // FKs
            $table->foreign('unidad_operativa_id')
                ->references('id')
                ->on('unidades_operativas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_diarios');
    }
};

