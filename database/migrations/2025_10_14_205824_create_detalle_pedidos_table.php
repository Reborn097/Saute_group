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

            // Relación con pedidos (por el campo 'codigo')
            $table->string('codigo', 20);
            $table->foreign('codigo')
                  ->references('codigo')
                  ->on('pedidos')
                  ->onDelete('cascade');

            // Relación con producto_proveedor
            $table->foreignId('producto_proveedor_id')
                  ->constrained('producto_proveedor')
                  ->onDelete('cascade');

            // Datos del detalle
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
