<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Agrega descripción en instalaciones que ya ejecutaron la migración inicial. */
    public function up(): void
    {
        $tableName = config('advertising.tables.categories');
        if (!Schema::hasColumn($tableName, 'description')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('description', 255)->nullable()->after('slug');
            });
        }
    }

    /** Revierte la columna agregada si existe. */
    public function down(): void
    {
        $tableName = config('advertising.tables.categories');
        if (Schema::hasColumn($tableName, 'description')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
