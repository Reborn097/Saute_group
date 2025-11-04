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
        Schema::create('pedidos', function (Blueprint $table) {
            // Código del pedido (único, tipo 'ene02250001')
            $table->string('codigo', 20)->unique();

            // Fechas
            $table->date('fecha_solicitud')->default(now());
            $table->date('fecha_entrega')->nullable();

            // Estado (Pendiente, Aprobado, etc.)
            $table->string('estado', 20)->default('Pendiente');

            // Relación con usuario
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Total del pedido (para cálculo de comensales)
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
