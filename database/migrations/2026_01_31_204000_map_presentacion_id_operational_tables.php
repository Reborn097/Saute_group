<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapSql = "
            SELECT p.id AS producto_id,
                   (
                       SELECT ppres2.id
                       FROM producto_presentaciones ppres2
                       WHERE ppres2.producto_id = p.id
                       ORDER BY (ppres2.descripcion = 'Default') DESC, ppres2.id ASC
                       LIMIT 1
                   ) AS presentacion_id
            FROM productos p
        ";

        $tablas = [
            'inventarios',
            'inventario_caducidades',
            'kardex',
            'movimientos_inventario',
            'pedido_diario_detalles',
            'incidencias',
        ];

        foreach ($tablas as $tabla) {
            DB::statement("
                UPDATE {$tabla} t
                JOIN ({$mapSql}) map ON map.producto_id = t.producto_id
                SET t.presentacion_id = COALESCE(t.presentacion_id, map.presentacion_id)
                WHERE t.presentacion_id IS NULL
                  AND map.presentacion_id IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        // No revertimos el mapeo automáticamente para evitar pérdida de datos.
    }
};
