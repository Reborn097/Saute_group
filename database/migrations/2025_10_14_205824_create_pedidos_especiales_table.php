<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('pedidos_especiales', function (Blueprint $table) {
        $table->string('id_pedido_especial', 20)->primary();
        $table->string('solicitud')->nullable();
        $table->string('cotizacion')->nullable();
        $table->string('autorizacion')->nullable();
        $table->string('codigo_pedido', 20);
        $table->foreign('codigo_pedido')->references('codigo_pedido')->on('pedidos')->onDelete('cascade');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_especiales');
    }
};
