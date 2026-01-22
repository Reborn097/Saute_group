<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Solo si users tiene la columna
        if (Schema::hasColumn('users', 'unidad_operativa_id')) {
            DB::statement("
                UPDATE pedidos p
                JOIN users u ON u.id = p.user_id
                SET p.unidad_operativa_id = u.unidad_operativa_id
                WHERE p.unidad_operativa_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        // No revertimos datos históricos
    }
};
