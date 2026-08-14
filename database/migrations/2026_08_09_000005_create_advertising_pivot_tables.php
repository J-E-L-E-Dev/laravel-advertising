<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create(config('advertising.tables.container_resource'), function (Blueprint $table) {
            $table->foreignId('container_id')->constrained(config('advertising.tables.containers'))->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained(config('advertising.tables.resources'))->cascadeOnDelete();
            $table->primary(['container_id', 'resource_id']);
        });
        Schema::create(config('advertising.tables.advertisement_container'), function (Blueprint $table) {
            $table->foreignId('advertisement_id')->constrained(config('advertising.tables.advertisements'))->cascadeOnDelete();
            $table->foreignId('container_id')->constrained(config('advertising.tables.containers'))->cascadeOnDelete();
            $table->primary(['advertisement_id', 'container_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists(config('advertising.tables.advertisement_container'));
        Schema::dropIfExists(config('advertising.tables.container_resource'));
    }
};
