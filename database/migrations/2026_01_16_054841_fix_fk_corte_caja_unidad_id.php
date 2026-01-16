<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corte_caja', function (Blueprint $table) {
            // ⚠️ Nombres típicos del FK en Laravel:
            // corte_caja_unidad_id_foreign
            // Si por alguna razón no coincide, te digo abajo cómo verificar.
            $table->dropForeign('corte_caja_unidad_id_foreign');

            // Re-crea FK apuntando a unidades_operativas
            $table->foreign('unidad_id')
                ->references('id')
                ->on('unidades_operativas')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('corte_caja', function (Blueprint $table) {
            $table->dropForeign(['unidad_id']);

            // vuelve a como estaba (a unidades)
            $table->foreign('unidad_id')
                ->references('id')
                ->on('unidades')
                ->onDelete('cascade');
        });
    }
};
