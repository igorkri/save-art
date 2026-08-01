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
        Schema::table('home_pages', function (Blueprint $table) {
            // Секція "Про платформу" (art-ua-info)
            $table->json('platform_description_tagline')->nullable();
            $table->json('platform_description_title')->nullable();
            $table->json('platform_description_subtitle')->nullable();
            $table->json('platform_description_paragraphs')->nullable(); // Масив абзаців

            // Секція переваг платформи (art-ua-info)
            $table->json('platform_features')->nullable(); // Масив {title, description}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_pages', function (Blueprint $table) {
            $table->dropColumn([
                'platform_description_tagline',
                'platform_description_title',
                'platform_description_subtitle',
                'platform_description_paragraphs',
                'platform_features',
            ]);
        });
    }
};
