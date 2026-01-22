<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_operativa_id')
                ->nullable()
                ->after('user_id');

            $table->index('unidad_operativa_id');

            $table->foreign('unidad_operativa_id')
                ->references('id')
                ->on('unidades_operativas')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['unidad_operativa_id']);
            $table->dropIndex(['unidad_operativa_id']);
            $table->dropColumn('unidad_operativa_id');
        });
    }
};
