<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('inventario_caducidades', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('almacen_id');

            $table->integer('cantidad');
            $table->date('caducidad');
            $table->string('lote')->nullable();

            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('almacen_id')->references('id')->on('almacenes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventario_caducidades');
    }
};

