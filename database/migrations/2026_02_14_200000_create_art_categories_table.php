<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('art_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Батьківська категорія (null = корінь)');
            $table->string('slug', 64)->comment('slug (унікальний в межах батька)');
            $table->json('name')->comment('Назва (uk, en) — ключі через Filament');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['parent_id', 'slug']);
        });

        Schema::table('art_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('art_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('art_categories');
    }
};
