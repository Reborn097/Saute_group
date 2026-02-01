<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // inventarios: unique producto_id+almacen_id -> presentacion_id+almacen_id
        if (Schema::hasTable('inventarios')) {
            // Asegurar índice simple sobre producto_id para no romper FK al quitar el UNIQUE
            $idxProd = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventarios'
                  AND INDEX_NAME = 'idx_inventarios_producto_id'
                LIMIT 1
            ");
            if (!$idxProd) {
                DB::statement('ALTER TABLE inventarios ADD INDEX idx_inventarios_producto_id (producto_id)');
            }

            $idx = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventarios'
                  AND INDEX_NAME = 'inv_producto_almacen_unique'
                LIMIT 1
            ");
            if ($idx) {
                DB::statement('ALTER TABLE inventarios DROP INDEX inv_producto_almacen_unique');
            }

            $idxNew = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventarios'
                  AND INDEX_NAME = 'inv_presentacion_almacen_unique'
                LIMIT 1
            ");
            if (!$idxNew) {
                DB::statement('ALTER TABLE inventarios ADD UNIQUE inv_presentacion_almacen_unique (presentacion_id, almacen_id)');
            }
        }

        // inventario_caducidades: idx_fifo -> presentacion_id+almacen_id+caducidad
        if (Schema::hasTable('inventario_caducidades')) {
            // Asegurar índice simple sobre producto_id para no romper FK al quitar idx_fifo
            $idxProd = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_caducidades'
                  AND INDEX_NAME = 'idx_inv_cad_producto_id'
                LIMIT 1
            ");
            if (!$idxProd) {
                DB::statement('ALTER TABLE inventario_caducidades ADD INDEX idx_inv_cad_producto_id (producto_id)');
            }

            $idx = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_caducidades'
                  AND INDEX_NAME = 'idx_fifo'
                LIMIT 1
            ");
            if ($idx) {
                DB::statement('ALTER TABLE inventario_caducidades DROP INDEX idx_fifo');
            }

            $idxNew = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_caducidades'
                  AND INDEX_NAME = 'idx_fifo_presentacion'
                LIMIT 1
            ");
            if (!$idxNew) {
                DB::statement('ALTER TABLE inventario_caducidades ADD INDEX idx_fifo_presentacion (presentacion_id, almacen_id, caducidad)');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventarios')) {
            $idxNew = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventarios'
                  AND INDEX_NAME = 'inv_presentacion_almacen_unique'
                LIMIT 1
            ");
            if ($idxNew) {
                DB::statement('ALTER TABLE inventarios DROP INDEX inv_presentacion_almacen_unique');
            }
            $idx = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventarios'
                  AND INDEX_NAME = 'inv_producto_almacen_unique'
                LIMIT 1
            ");
            if (!$idx) {
                DB::statement('ALTER TABLE inventarios ADD UNIQUE inv_producto_almacen_unique (producto_id, almacen_id)');
            }

            $idxProd = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventarios'
                  AND INDEX_NAME = 'idx_inventarios_producto_id'
                LIMIT 1
            ");
            if ($idxProd) {
                DB::statement('ALTER TABLE inventarios DROP INDEX idx_inventarios_producto_id');
            }
        }

        if (Schema::hasTable('inventario_caducidades')) {
            $idxNew = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_caducidades'
                  AND INDEX_NAME = 'idx_fifo_presentacion'
                LIMIT 1
            ");
            if ($idxNew) {
                DB::statement('ALTER TABLE inventario_caducidades DROP INDEX idx_fifo_presentacion');
            }
            $idx = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_caducidades'
                  AND INDEX_NAME = 'idx_fifo'
                LIMIT 1
            ");
            if (!$idx) {
                DB::statement('ALTER TABLE inventario_caducidades ADD INDEX idx_fifo (producto_id, almacen_id, caducidad)');
            }

            $idxProd = DB::selectOne("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'inventario_caducidades'
                  AND INDEX_NAME = 'idx_inv_cad_producto_id'
                LIMIT 1
            ");
            if ($idxProd) {
                DB::statement('ALTER TABLE inventario_caducidades DROP INDEX idx_inv_cad_producto_id');
            }
        }
    }
};
