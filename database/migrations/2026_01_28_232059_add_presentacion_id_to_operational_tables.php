<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lista de tablas que hoy usan producto_id (según tu captura)
        $tablas = [
            'incidencias',
            'inventario_caducidades',
            'inventarios',
            'kardex',
            'movimientos_inventario',
            'pedido_diario_detalles',
        ];

        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {

                // Evita errores si ya existe
                if (!Schema::hasColumn($tabla, 'presentacion_id')) {
                    $table->foreignId('presentacion_id')
                        ->nullable()
                        ->after('producto_id')
                        ->constrained('producto_presentaciones')
                        ->nullOnDelete();

                    $table->index(['presentacion_id'], "idx_{$tabla}_presentacion_id");
                }
            });
        }
    }

    public function down(): void
    {
        $tablas = [
            'incidencias',
            'inventario_caducidades',
            'inventarios',
            'kardex',
            'movimientos_inventario',
            'pedido_diario_detalles',
        ];

        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {

                if (Schema::hasColumn($tabla, 'presentacion_id')) {
                    // Primero elimina FK y luego columna
                    try {
                        $table->dropForeign([$table->getTable().'_presentacion_id_foreign']);
                    } catch (\Throwable $e) {
                        // fallback: intenta por convención Laravel
                        try { $table->dropForeign(['presentacion_id']); } catch (\Throwable $e2) {}
                    }

                    try { $table->dropIndex("idx_{$tabla}_presentacion_id"); } catch (\Throwable $e) {}

                    $table->dropColumn('presentacion_id');
                }
            });
        }
    }
};
