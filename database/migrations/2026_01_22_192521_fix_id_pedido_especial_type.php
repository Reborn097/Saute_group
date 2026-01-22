<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pedidos_especiales', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('pedidos_especiales', function (Blueprint $table) {
            $table->dropColumn('id_pedido_especial');
        });

        Schema::table('pedidos_especiales', function (Blueprint $table) {
            $table->id('id_pedido_especial')->first();
        });
    }

    public function down()
    {
        Schema::table('pedidos_especiales', function (Blueprint $table) {
            $table->dropColumn('id_pedido_especial');
            $table->string('id_pedido_especial')->primary();
        });
    }
};

