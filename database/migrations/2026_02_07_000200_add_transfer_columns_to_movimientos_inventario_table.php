<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos_inventario', 'presentacion_id')) {
                $table->foreignId('presentacion_id')
                    ->nullable()
                    ->after('producto_id')
                    ->constrained('producto_presentaciones')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('movimientos_inventario', 'almacen_destino_id')) {
                $table->unsignedBigInteger('almacen_destino_id')->nullable()->after('almacen_id');
                $table->foreign('almacen_destino_id')
                    ->references('id')
                    ->on('almacenes')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('movimientos_inventario', 'costo_unitario')) {
                $table->decimal('costo_unitario', 12, 2)->nullable()->after('caducidad');
            }

            if (!Schema::hasColumn('movimientos_inventario', 'costo_total')) {
                $table->decimal('costo_total', 14, 2)->nullable()->after('costo_unitario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos_inventario', 'costo_total')) {
                $table->dropColumn('costo_total');
            }
            if (Schema::hasColumn('movimientos_inventario', 'costo_unitario')) {
                $table->dropColumn('costo_unitario');
            }
            if (Schema::hasColumn('movimientos_inventario', 'almacen_destino_id')) {
                $table->dropForeign(['almacen_destino_id']);
                $table->dropColumn('almacen_destino_id');
            }
            if (Schema::hasColumn('movimientos_inventario', 'presentacion_id')) {
                $table->dropForeign(['presentacion_id']);
                $table->dropColumn('presentacion_id');
            }
        });
    }
};
