<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('corte_caja', function (Blueprint $table) {
        $table->decimal('total', 10, 2)->default(0);
        $table->integer('semana')->nullable();
        $table->integer('mes')->nullable();
        $table->integer('anio')->nullable();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('corte_caja', function (Blueprint $table) {
        $table->dropColumn(['total', 'semana', 'mes', 'anio']);
    });
}

};
