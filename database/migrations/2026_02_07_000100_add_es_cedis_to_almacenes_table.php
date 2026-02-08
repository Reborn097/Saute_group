<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->boolean('es_cedis')->default(false)->after('unidad_id');
        });

        DB::table('almacenes')
            ->whereRaw("LOWER(COALESCE(tipo, '')) = 'cedis'")
            ->update(['es_cedis' => true]);
    }

    public function down(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->dropColumn('es_cedis');
        });
    }
};
