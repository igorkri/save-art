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
            // Удалить hero_video_url
            $table->dropColumn('hero_video_url');
            // Добавить hero_video_poster_mobile
            $table->string('hero_video_poster_m')->nullable()->after('hero_video_poster');
            // hero_image_poster and hero_image_poster_mobile   
            $table->string('hero_image_poster')->nullable()->after('hero_video_poster_m');
            $table->string('hero_image_poster_m')->nullable()->after('hero_image_poster');
            
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_pages', function (Blueprint $table) {
            $table->string('hero_video_url')->nullable()->after('hero_video_poster');
            $table->dropColumn('hero_image_poster');
            $table->dropColumn('hero_image_poster_m');
            $table->dropColumn('hero_video_poster_m');
        });
    }
};
