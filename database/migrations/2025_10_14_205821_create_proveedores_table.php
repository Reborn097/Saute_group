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
    Schema::create('proveedores', function (Blueprint $table) {
        $table->id();
        $table->string('nombre', 150);
        $table->string('telefono', 20)->nullable();
        $table->string('calle', 100)->nullable();
        $table->string('colonia', 100)->nullable();
        $table->string('codigo_postal', 10)->nullable();
        $table->string('num_direccion', 50)->nullable();
        $table->string('rfc', 20)->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
