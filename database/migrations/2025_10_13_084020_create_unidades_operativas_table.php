<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('unidades_operativas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                  // Comedor Norte, Cafetería Central
            $table->string('tipo')->nullable();        // comedor, cafeteria, externo
            $table->string('ubicacion')->nullable();   // dirección, planta, nave
            $table->unsignedBigInteger('responsable_id')->nullable(); // encargado
            $table->timestamps();

            $table->foreign('responsable_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('unidades_operativas');
    }
};

