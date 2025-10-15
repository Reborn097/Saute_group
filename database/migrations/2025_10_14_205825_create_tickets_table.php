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
    Schema::create('tickets', function (Blueprint $table) {
        $table->string('folio', 20)->primary();
        $table->date('fecha');
        $table->string('forma_pago', 50);
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
        Schema::dropIfExists('tickets');
    }
};
