<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedores', 'estado')) {
                $table->boolean('estado')->default(1)->after('rfc');
                $table->index('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores', 'estado')) {
                $table->dropIndex(['estado']);
                $table->dropColumn('estado');
            }
        });
    }
};
