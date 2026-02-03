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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            // Логотип
            $table->string('site_logo')->nullable();

            // Header
            $table->json('header_brand_name')->nullable(); // мультимовне: save-art.in.ua
            $table->json('header_dropdown_sites')->nullable(); // [{name, url, is_active}]
            $table->json('header_menu')->nullable(); // [{label, url}]
            $table->json('header_socials')->nullable(); // {instagram, facebook, youtube}
            $table->string('header_support_button_url')->nullable();
            $table->json('header_support_button_text')->nullable(); // мультимовне
            $table->json('header_login_button_text')->nullable(); // мультимовне

            // Footer Top
            $table->json('footer_brand_name')->nullable(); // мультимовне: save-art.in.ua
            $table->json('footer_slogan')->nullable(); // мультимовне: Мистецтво допомоги...
            $table->json('footer_collaboration_title')->nullable(); // мультимовне: Запрошуємо експертів...
            $table->json('footer_collaboration_text')->nullable(); // мультимовне: Благодійний фонд...
            $table->json('footer_collaboration_items')->nullable(); // [{image, text}]
            $table->json('footer_collaboration_button_text')->nullable(); // мультимовне: Відправити заявку

            // Footer Middle - меню сайтів
            $table->json('footer_sites_menu')->nullable(); // [{site_name, site_url, links: [{label, url}]}]

            // Footer Bottom - контактна інформація
            $table->json('footer_company_name')->nullable(); // мультимовне
            $table->json('footer_address')->nullable(); // мультимовне
            $table->string('footer_email')->nullable();
            $table->string('footer_phone')->nullable();
            $table->json('footer_social_links')->nullable(); // [{type, url, label}]
            $table->string('footer_copyright_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
