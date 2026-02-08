<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 0) IMPORTANTE:
         * producto_proveedor_id ya no apuntará a producto_proveedor,
         * ahora apuntará a presentacion_proveedor.
         * Entonces primero quitamos la FK vieja para poder hacer el UPDATE.
         */
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            // Si el nombre real de la FK es el default de Laravel, esto funciona:
            // detalle_pedidos_producto_proveedor_id_foreign
            $table->dropForeign(['producto_proveedor_id']);
        });

        // 1) Crear filas en presentacion_proveedor basadas en producto_proveedor (si no existen)
        DB::statement("
            INSERT INTO presentacion_proveedor
                (presentacion_id, proveedor_id, precio_vigente, estado, created_at, updated_at)
            SELECT
                map.presentacion_id,
                pp.proveedor_id,
                pp.precio,
                pp.estado,
                NOW(),
                NOW()
            FROM producto_proveedor pp
            JOIN (
                SELECT p.id AS producto_id,
                       (
                           SELECT ppres2.id
                           FROM producto_presentaciones ppres2
                           WHERE ppres2.producto_id = p.id
                           ORDER BY (ppres2.descripcion = 'Default') DESC, ppres2.id ASC
                           LIMIT 1
                       ) AS presentacion_id
                FROM productos p
            ) map ON map.producto_id = pp.producto_id
            LEFT JOIN presentacion_proveedor pprov
                ON pprov.presentacion_id = map.presentacion_id
               AND pprov.proveedor_id = pp.proveedor_id
            WHERE map.presentacion_id IS NOT NULL
              AND pprov.id IS NULL
        ");

        // 2) Migrar detalle_pedidos.producto_proveedor_id (viejo) -> presentacion_proveedor.id
        DB::statement("
            UPDATE detalle_pedidos dp
            JOIN producto_proveedor pp
              ON dp.producto_proveedor_id = pp.id
            JOIN (
                SELECT p.id AS producto_id,
                       (
                           SELECT ppres2.id
                           FROM producto_presentaciones ppres2
                           WHERE ppres2.producto_id = p.id
                           ORDER BY (ppres2.descripcion = 'Default') DESC, ppres2.id ASC
                           LIMIT 1
                       ) AS presentacion_id
                FROM productos p
            ) map ON map.producto_id = pp.producto_id
            JOIN presentacion_proveedor pprov
              ON pprov.presentacion_id = map.presentacion_id
             AND pprov.proveedor_id = pp.proveedor_id
            SET dp.presentacion_id = COALESCE(dp.presentacion_id, map.presentacion_id),
                dp.producto_proveedor_id = pprov.id
        ");

        // 3) Si ya existen ids nuevos, completar presentacion_id faltante
        DB::statement("
            UPDATE detalle_pedidos dp
            JOIN presentacion_proveedor pprov
              ON dp.producto_proveedor_id = pprov.id
            SET dp.presentacion_id = COALESCE(dp.presentacion_id, pprov.presentacion_id)
            WHERE dp.presentacion_id IS NULL
        ");

        /**
         * 4) Crear la nueva FK (ahora sí, ya con ids válidos)
         */
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->foreign('producto_proveedor_id')
                ->references('id')
                ->on('presentacion_proveedor')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Por seguridad, solo regresamos la FK hacia producto_proveedor si existiera.
        Schema::table('detalle_pedidos', function (Blueprint $table) {
            // intenta borrar FK hacia presentacion_proveedor
            try { $table->dropForeign(['producto_proveedor_id']); } catch (\Throwable $e) {}
        });

        Schema::table('detalle_pedidos', function (Blueprint $table) {
            $table->foreign('producto_proveedor_id')
                ->references('id')
                ->on('producto_proveedor')
                ->onDelete('cascade');
        });
    }
};
