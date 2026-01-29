<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insertar una presentación "Default" por cada producto
        // que aún no tenga ninguna presentación
        DB::statement("
            INSERT INTO producto_presentaciones
                (producto_id, descripcion, contenido, unidad_contenido, unidad_base, estado, created_at, updated_at)
            SELECT
                p.id,
                'Default',
                NULL,
                NULL,
                NULL,
                1,
                NOW(),
                NOW()
            FROM productos p
            WHERE NOT EXISTS (
                SELECT 1
                FROM producto_presentaciones pp
                WHERE pp.producto_id = p.id
            )
        ");
    }

    public function down(): void
    {
        // Solo elimina las presentaciones creadas por esta migración
        // (descripcion = 'Default')
        DB::table('producto_presentaciones')
            ->where('descripcion', 'Default')
            ->delete();
    }
};
