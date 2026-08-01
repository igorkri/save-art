<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('terms_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terms_section_id')->constrained()->cascadeOnDelete();
            $table->json('heading')->nullable();
            $table->json('paragraphs')->nullable();
            $table->string('list_type')->nullable();
            $table->json('list_items')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_blocks');
    }
};
