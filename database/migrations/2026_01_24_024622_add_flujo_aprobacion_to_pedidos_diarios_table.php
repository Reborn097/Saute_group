<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_diarios', function (Blueprint $table) {
            $table->unsignedBigInteger('preaprobado_por')
                  ->nullable()
                  ->after('unidad_operativa_id');

            $table->text('observaciones_ceo')
                  ->nullable()
                  ->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_diarios', function (Blueprint $table) {
            $table->dropColumn([
                'preaprobado_por',
                'observaciones_ceo',
            ]);
        });
    }
};
