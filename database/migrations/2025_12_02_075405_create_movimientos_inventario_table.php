<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('almacen_id');
            
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'transferencia']);
            $table->decimal('cantidad', 10, 2);

            $table->string('motivo')->nullable();      // compra, pedido, merma, ajuste
            $table->string('referencia')->nullable();  // id de compra/pedido/usuario/etc.

            $table->date('fecha');
            $table->unsignedBigInteger('usuario_id')->nullable();

            // Opcional para FEFO
            $table->date('caducidad')->nullable();

            $table->timestamps();

            // Relaciones
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('almacen_id')->references('id')->on('almacenes');
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};

