<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_presentaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();

            // Ej: "Paquete 25 pzas", "Bote 3.4 kg", "Caja 6 pzas", "Kg"
            $table->string('descripcion', 150);

            // Ej: 25, 3.4, 6, 1
            $table->decimal('contenido', 12, 3)->nullable();

            // Ej: "pieza", "kg", "l", "tableta"
            $table->string('unidad_contenido', 20)->nullable();

            // Unidad "base" para costeo/inventario (normalización)
            // Ej: "pieza", "kg", "l"
            $table->string('unidad_base', 20)->nullable();

            $table->boolean('estado')->default(true);

            $table->timestamps();

            // útil para búsquedas por producto
            $table->index(['producto_id', 'estado'], 'idx_presentaciones_producto_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_presentaciones');
    }
};

