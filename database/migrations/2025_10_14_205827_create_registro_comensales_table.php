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
    Schema::create('registro_comensales', function (Blueprint $table) {
        $table->id();
        $table->decimal('cantidad', 10, 2);
        $table->date('fecha');
        $table->foreignId('unidad_id')->constrained('unidades')->onDelete('cascade');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_comensales');
    }
};
