<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidad_operativa_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('unidad_operativa_id');
            $table->timestamps();

            $table->unique(['user_id', 'unidad_operativa_id'], 'uq_uou_user_unidad');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('unidad_operativa_id')
                ->references('id')
                ->on('unidades_operativas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidad_operativa_user');
    }
};

