<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            //nmms
            // ✅ Fase 1: agregar sin romper lo existente
            if (!Schema::hasColumn('detalle_pedidos', 'presentacion_id')) {
                $table->unsignedBigInteger('presentacion_id')->nullable()->after('producto_proveedor_id');

                $table->index('presentacion_id', 'idx_detalle_pedidos_presentacion_id');

                $table->foreign('presentacion_id', 'fk_detalle_pedidos_presentacion_id')
                    ->references('id')
                    ->on('producto_presentaciones')
                    ->onDelete('restrict'); // o cascade si lo prefieres
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {

            if (Schema::hasColumn('detalle_pedidos', 'presentacion_id')) {
                // drop FK primero
                $table->dropForeign('fk_detalle_pedidos_presentacion_id');
                $table->dropIndex('idx_detalle_pedidos_presentacion_id');

                $table->dropColumn('presentacion_id');
            }
        });
    }
};
