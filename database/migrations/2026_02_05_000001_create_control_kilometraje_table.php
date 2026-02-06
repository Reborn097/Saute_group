<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('control_kilometraje', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_operativa_id');
            $table->date('fecha');

            $table->unsignedInteger('km_inicio')->nullable();
            $table->unsignedInteger('km_final')->nullable();
            $table->unsignedInteger('km_recorridos')->nullable();

            $table->decimal('diesel_inicio_pct', 5, 2)->nullable();
            $table->decimal('diesel_final_pct', 5, 2)->nullable();

            $table->text('lugares_visitados')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->unique(['unidad_operativa_id', 'fecha']);

            $table->foreign('unidad_operativa_id')
                ->references('id')->on('unidades_operativas')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_kilometraje');
    }
};
