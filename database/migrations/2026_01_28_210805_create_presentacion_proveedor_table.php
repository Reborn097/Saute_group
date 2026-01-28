<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentacion_proveedor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('presentacion_id')
                ->constrained('producto_presentaciones')
                ->cascadeOnDelete();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->cascadeOnDelete();

            // Opcional: cache del precio vigente para no consultar historial en listados
            $table->decimal('precio_vigente', 12, 2)->nullable();

            $table->boolean('estado')->default(true);

            $table->timestamps();

            // Un proveedor no debe repetir la misma presentación
            $table->unique(['presentacion_id', 'proveedor_id'], 'uq_presentacion_proveedor');

            $table->index(['proveedor_id', 'estado'], 'idx_pp_proveedor_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentacion_proveedor');
    }
};
