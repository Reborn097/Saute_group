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
            $table->foreignId('producto_proveedor_id')
                ->constrained('producto_proveedor')
                ->onDelete('cascade');
            $table->decimal('precio', 10, 2);
            $table->date('fecha_vigencia_inicio');
            $table->date('fecha_vigencia_final')->nullable();
            $table->timestamps();
        });
    }

};
