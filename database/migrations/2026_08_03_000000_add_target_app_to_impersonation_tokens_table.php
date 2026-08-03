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
        Schema::table('impersonation_tokens', function (Blueprint $table) {
            // 'save_art' (React SPA, /profile/private/...) або 'art_ua_info'
            // (Next.js, /profile/{slug}/...) — визначає формат redirect_path
            // і фронтенд, на який веде посилання з адмінки.
            $table->string('target_app')->default('save_art')->after('project_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('impersonation_tokens', function (Blueprint $table) {
            $table->dropColumn('target_app');
        });
    }
};
