<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_precio', function (Blueprint $table) {
            // Índice recomendado para búsquedas de precio vigente y rangos
            $table->index(
                ['producto_proveedor_id', 'fecha_vigencia_inicio', 'fecha_vigencia_final'],
                'idx_historial_pp_vigencia'
            );
        });

        Schema::table('historial_precio', function (Blueprint $table) {
            $table->dateTime('fecha_vigencia_inicio')->change();
            $table->dateTime('fecha_vigencia_final')->nullable()->change();
        });
        
    }

    public function down(): void
    {
        Schema::table('historial_precio', function (Blueprint $table) {
            $table->dropIndex('idx_historial_pp_vigencia');
        });

        Schema::table('historial_precio', function (Blueprint $table) {
            $table->date('fecha_vigencia_inicio')->change();
            $table->date('fecha_vigencia_final')->nullable()->change();
        });
    }
};
