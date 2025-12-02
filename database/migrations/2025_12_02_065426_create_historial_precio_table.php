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
        Schema::create('historial_precio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_proveedor_id');
            $table->decimal('precio', 10, 2);
            $table->date('fecha_vigencia_inicio');
            $table->date('fecha_vigencia_final')->nullable();
            $table->timestamps();

            $table->foreign('producto_proveedor_id')
                ->references('id')
                ->on('producto_proveedor')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_precio');
    }
};
