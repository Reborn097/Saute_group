<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proveedor_tarjetas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->onDelete('cascade');

            $table->enum('tipo', ['empresa', 'contacto'])->default('empresa');
            $table->string('alias', 80)->nullable();
            $table->string('banco', 80)->nullable();
            $table->string('titular', 120)->nullable();

            $table->string('clabe', 18)->nullable();
            $table->string('cuenta', 20)->nullable();
            $table->string('tarjeta', 20)->nullable();

            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['proveedor_id', 'clabe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_tarjetas');
    }
};
