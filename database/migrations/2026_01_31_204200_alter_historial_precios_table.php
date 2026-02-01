<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('historial_precios')) {
            Schema::create('historial_precios', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('historial_precios', function (Blueprint $table) {
            if (!Schema::hasColumn('historial_precios', 'presentacion_proveedor_id')) {
                $table->foreignId('presentacion_proveedor_id')
                    ->nullable()
                    ->constrained('presentacion_proveedor')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('historial_precios', 'precio')) {
                $table->decimal('precio', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('historial_precios', 'vigencia_inicio')) {
                $table->date('vigencia_inicio')->nullable();
            }
            if (!Schema::hasColumn('historial_precios', 'vigencia_fin')) {
                $table->date('vigencia_fin')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('historial_precios', function (Blueprint $table) {
            if (Schema::hasColumn('historial_precios', 'presentacion_proveedor_id')) {
                $table->dropForeign(['presentacion_proveedor_id']);
                $table->dropColumn('presentacion_proveedor_id');
            }
            if (Schema::hasColumn('historial_precios', 'precio')) {
                $table->dropColumn('precio');
            }
            if (Schema::hasColumn('historial_precios', 'vigencia_inicio')) {
                $table->dropColumn('vigencia_inicio');
            }
            if (Schema::hasColumn('historial_precios', 'vigencia_fin')) {
                $table->dropColumn('vigencia_fin');
            }
        });
    }
};
