<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->after('precio_unitario')->nullable();
        });
    }

    public function down()
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->dropColumn('subtotal');
        });
    }
};
