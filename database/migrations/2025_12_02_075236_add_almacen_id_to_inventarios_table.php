<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->unsignedBigInteger('almacen_id')->after('producto_id');

            $table->foreign('almacen_id')
                ->references('id')
                ->on('almacenes')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropColumn('almacen_id');
        });
    }
};

