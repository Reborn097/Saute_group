<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('inventarios', function (Blueprint $table) {
            if (Schema::hasColumn('inventarios', 'caducidad')) {
                $table->dropColumn('caducidad');
            }

            if (Schema::hasColumn('inventarios', 'area_almacen')) {
                $table->dropColumn('area_almacen');
            }

            // evitar duplicados producto + almacén
            $table->unique(['producto_id', 'almacen_id'], 'inv_producto_almacen_unique');
        });
    }

    public function down()
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->date('caducidad')->nullable();
            $table->string('area_almacen')->nullable();
            $table->dropUnique('inv_producto_almacen_unique');
        });
    }

};
