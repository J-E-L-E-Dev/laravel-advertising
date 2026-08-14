<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create(config('advertising.tables.resources'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained(config('advertising.tables.categories'))->nullOnDelete();
            $table->string('path');
            $table->string('disk')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('alt', 150)->nullable();
            $table->unsignedInteger('duration')->default(5000);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('eyebrow', 80)->nullable();
            $table->string('title', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('button_label', 60)->nullable();
            $table->string('button_url')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists(config('advertising.tables.resources')); }
};
