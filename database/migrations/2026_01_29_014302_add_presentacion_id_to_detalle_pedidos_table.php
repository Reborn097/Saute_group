<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('detalle_pedidos', 'presentacion_id')) {
                $table->unsignedBigInteger('presentacion_id')->nullable()->after('producto_proveedor_id');
                $table->foreign('presentacion_id')
                    ->references('id')
                    ->on('producto_presentaciones')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_pedidos', 'presentacion_id')) {
                $table->dropForeign(['presentacion_id']);
                $table->dropColumn('presentacion_id');
            }
        });
    }
};
