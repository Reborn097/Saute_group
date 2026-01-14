<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kardex', function (Blueprint $table) {

            $table->enum('tipo_movimiento', [
                'entrada',
                'salida',
                'ajuste',
                'merma',
                'transferencia'
            ])->change();

            if (!Schema::hasColumn('kardex', 'referencia')) {
                $table->string('referencia')->nullable()->after('motivo');
            }
        });
    }

    public function down()
    {
        Schema::table('kardex', function (Blueprint $table) {
            $table->dropColumn('referencia');
        });
    }

};
