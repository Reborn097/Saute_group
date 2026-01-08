<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('producto_proveedor', function (Blueprint $table) {
            $table->unique(['producto_id', 'proveedor_id'], 'pp_producto_proveedor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('producto_proveedor', function (Blueprint $table) {
            $table->dropUnique('pp_producto_proveedor_unique');
        });
    }
};

