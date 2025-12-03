<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->string('tipo')->nullable();     // seco, refrigeración, congelado, varios…
            $table->string('ubicacion')->nullable();

            $table->unsignedBigInteger('unidad_id'); // relación con la unidad operativa
            $table->foreign('unidad_id')
                ->references('id')
                ->on('unidades_operativas')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('almacenes');
    }
};
