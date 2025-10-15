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
    Schema::create('detalle_pedidos', function (Blueprint $table) {
        $table->id();
        $table->string('codigo_pedido', 20);
        $table->foreign('codigo_pedido')->references('codigo_pedido')->on('pedidos')->onDelete('cascade');
        $table->foreignId('producto_proveedor_id')->constrained('producto_proveedor')->onDelete('cascade');
        $table->decimal('precio_unitario', 10, 2);
        $table->decimal('cantidad_solicitada', 10, 2);
        $table->decimal('cantidad_aprobada', 10, 2)->nullable();
        $table->string('motivos_cambios', 100)->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};
