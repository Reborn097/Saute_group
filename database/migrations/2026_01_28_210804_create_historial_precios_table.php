<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_precios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('presentacion_proveedor_id')
                ->constrained('presentacion_proveedor')
                ->cascadeOnDelete();

            $table->decimal('precio', 12, 2);

            // Recomendado DATETIME para permitir múltiples cambios el mismo día
            $table->dateTime('vigencia_inicio');
            $table->dateTime('vigencia_fin')->nullable();

            $table->timestamps();

            // Para consultas tipo: "precio vigente" (vigencia_fin IS NULL)
            $table->index(
                ['presentacion_proveedor_id', 'vigencia_inicio', 'vigencia_fin'],
                'idx_hist_pp_vigencias'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_precios');
    }
};
