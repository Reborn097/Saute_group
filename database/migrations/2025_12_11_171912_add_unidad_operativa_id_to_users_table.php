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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_operativa_id')->nullable()->after('role');

            $table->foreign('unidad_operativa_id')
                ->references('id')
                ->on('unidades_operativas')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unidad_operativa_id']);
            $table->dropColumn('unidad_operativa_id');
        });
    }

};
