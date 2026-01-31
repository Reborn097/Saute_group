<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_pedidos', 'producto_proveedor_id')) {
                $fk = DB::selectOne("
                    SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'detalle_pedidos'
                      AND COLUMN_NAME = 'producto_proveedor_id'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                    LIMIT 1
                ");

                if ($fk && $fk->CONSTRAINT_NAME) {
                    DB::statement('ALTER TABLE detalle_pedidos DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                }

                if (!$fk || $fk->REFERENCED_TABLE_NAME !== 'presentacion_proveedor') {
                    DB::statement("
                        ALTER TABLE detalle_pedidos
                        ADD CONSTRAINT detalle_pedidos_producto_proveedor_id_foreign
                        FOREIGN KEY (producto_proveedor_id)
                        REFERENCES presentacion_proveedor(id)
                        ON DELETE SET NULL
                    ");
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_pedidos', 'producto_proveedor_id')) {
                $fk = DB::selectOne("
                    SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'detalle_pedidos'
                      AND COLUMN_NAME = 'producto_proveedor_id'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                    LIMIT 1
                ");

                if ($fk && $fk->CONSTRAINT_NAME) {
                    DB::statement('ALTER TABLE detalle_pedidos DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                }

                if (!$fk || $fk->REFERENCED_TABLE_NAME !== 'producto_proveedor') {
                    DB::statement("
                        ALTER TABLE detalle_pedidos
                        ADD CONSTRAINT detalle_pedidos_producto_proveedor_id_foreign
                        FOREIGN KEY (producto_proveedor_id)
                        REFERENCES producto_proveedor(id)
                        ON DELETE SET NULL
                    ");
                }
            }
        });
    }
};
