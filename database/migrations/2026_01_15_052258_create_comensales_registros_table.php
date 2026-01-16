<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comensales_registros', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('unidad_operativa_id');
            $table->date('fecha');
            $table->unsignedInteger('cantidad')->default(0);

            // opcional: quién registró
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            // Un registro por unidad y día
            $table->unique(['unidad_operativa_id', 'fecha']);

            $table->foreign('unidad_operativa_id')
                ->references('id')->on('unidades_operativas')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comensales_registros');
    }
};
