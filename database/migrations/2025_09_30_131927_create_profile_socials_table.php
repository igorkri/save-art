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
        Schema::create('profile_socials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ID користувача');
            // посилання на вебсайт
            $table->string('website')->nullable()->comment('Посилання на вебсайт користувача');
            // посилання на facebook
            $table->string('facebook')->nullable()->comment('Посилання на Facebook користувача');
            // посилання на twitter
            $table->string('twitter')->nullable()->comment('Посилання на Twitter користувача');
            // посилання на instagram
            $table->string('instagram')->nullable()->comment('Посилання на Instagram користувача');
            // посилання на linkedin
            $table->string('linkedin')->nullable()->comment('Посилання на LinkedIn користувача');
            // посилання на youtube
            $table->string('youtube')->nullable()->comment('Посилання на YouTube користувача');
            // посилання на pinterest
            $table->string('pinterest')->nullable()->comment('Посилання на Pinterest користувача');
            // посилання на github
            $table->string('github')->nullable()->comment('Посилання на GitHub користувача');
            // посилання на telegram
            $table->string('telegram')->nullable()->comment('Посилання на Telegram користувача');
            // посилання на tiktok
            $table->string('tiktok')->nullable()->comment('Посилання на TikTok користувача');
            // youtube канал
            $table->string('youtube_channel')->nullable()->comment('Посилання на YouTube канал користувача');
            // whatsapp
            $table->string('whatsapp')->nullable()->comment('Посилання на WhatsApp користувача');
            // Deviantart
            $table->string('deviantart')->nullable()->comment('Посилання на DeviantArt користувача');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_socials');
    }
};
