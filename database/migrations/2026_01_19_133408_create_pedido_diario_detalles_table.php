<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_diario_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pedido_diario_id');

            // Día específico dentro de la semana
            $table->date('fecha');

            // Tipo de pan/tortilla (producto ya existe en tu tabla productos)
            $table->unsignedBigInteger('producto_id');

            // Para tortilla puede haber decimales; pan se valida como entero en el backend
            $table->decimal('cantidad', 10, 2)->default(0);

            // Congelar precio al momento de capturar (recomendado)
            $table->decimal('precio_unitario', 10, 2)->default(0);

            // Subtotal calculado
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->timestamps();

            // Evita duplicar el mismo producto en el mismo día para el mismo pedido
            $table->unique(['pedido_diario_id', 'fecha', 'producto_id'], 'uq_pdd_pedido_fecha_producto');

            // Índices útiles para reportes
            $table->index(['fecha'], 'idx_pdd_fecha');
            $table->index(['producto_id'], 'idx_pdd_producto');

            // FKs
            $table->foreign('pedido_diario_id')
                ->references('id')
                ->on('pedidos_diarios')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('producto_id')
                ->references('id')
                ->on('productos')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_diario_detalles');
    }
};
