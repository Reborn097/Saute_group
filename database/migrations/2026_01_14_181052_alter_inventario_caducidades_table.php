<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('inventario_caducidades', function (Blueprint $table) {

            if (!Schema::hasColumn('inventario_caducidades', 'inventario_id')) {
                $table->foreignId('inventario_id')
                    ->after('almacen_id')
                    ->constrained('inventarios')
                    ->cascadeOnDelete();
            }

            $table->string('lote')->nullable()->change();

            $table->index(['producto_id', 'almacen_id', 'caducidad'], 'idx_fifo');
        });
    }

    public function down()
    {
        Schema::table('inventario_caducidades', function (Blueprint $table) {
            $table->dropForeign(['inventario_id']);
            $table->dropColumn('inventario_id');
            $table->dropIndex('idx_fifo');
        });
    }

};
