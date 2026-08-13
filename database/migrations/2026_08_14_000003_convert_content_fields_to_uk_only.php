<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->string('page_title_uk')->nullable()->after('page_title');
            $table->string('title_uk')->nullable()->after('title');
            $table->text('content_uk')->nullable()->after('content');
            $table->string('meta_title_uk')->nullable()->after('meta_title');
            $table->string('meta_description_uk')->nullable()->after('meta_description');
            $table->string('meta_keywords_uk')->nullable()->after('meta_keywords');
        });

        DB::statement("UPDATE contents SET page_title_uk = JSON_UNQUOTE(JSON_EXTRACT(page_title, '$.uk')) WHERE page_title IS NOT NULL");
        DB::statement("UPDATE contents SET title_uk = JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk')) WHERE title IS NOT NULL");
        DB::statement("UPDATE contents SET content_uk = JSON_UNQUOTE(JSON_EXTRACT(content, '$.uk')) WHERE content IS NOT NULL");
        DB::statement("UPDATE contents SET meta_title_uk = JSON_UNQUOTE(JSON_EXTRACT(meta_title, '$.uk')) WHERE meta_title IS NOT NULL");
        DB::statement("UPDATE contents SET meta_description_uk = JSON_UNQUOTE(JSON_EXTRACT(meta_description, '$.uk')) WHERE meta_description IS NOT NULL");
        DB::statement("UPDATE contents SET meta_keywords_uk = JSON_UNQUOTE(JSON_EXTRACT(meta_keywords, '$.uk')) WHERE meta_keywords IS NOT NULL");

        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn(['page_title', 'title', 'content', 'meta_title', 'meta_description', 'meta_keywords']);
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->renameColumn('page_title_uk', 'page_title');
            $table->renameColumn('title_uk', 'title');
            $table->renameColumn('content_uk', 'content');
            $table->renameColumn('meta_title_uk', 'meta_title');
            $table->renameColumn('meta_description_uk', 'meta_description');
            $table->renameColumn('meta_keywords_uk', 'meta_keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->json('page_title_json')->nullable()->after('page_title');
            $table->json('title_json')->nullable()->after('title');
            $table->json('content_json')->nullable()->after('content');
            $table->json('meta_title_json')->nullable()->after('meta_title');
            $table->json('meta_description_json')->nullable()->after('meta_description');
            $table->json('meta_keywords_json')->nullable()->after('meta_keywords');
        });

        DB::statement("UPDATE contents SET page_title_json = JSON_OBJECT('uk', page_title, 'en', page_title) WHERE page_title IS NOT NULL");
        DB::statement("UPDATE contents SET title_json = JSON_OBJECT('uk', title, 'en', title) WHERE title IS NOT NULL");
        DB::statement("UPDATE contents SET content_json = JSON_OBJECT('uk', content, 'en', content) WHERE content IS NOT NULL");
        DB::statement("UPDATE contents SET meta_title_json = JSON_OBJECT('uk', meta_title, 'en', meta_title) WHERE meta_title IS NOT NULL");
        DB::statement("UPDATE contents SET meta_description_json = JSON_OBJECT('uk', meta_description, 'en', meta_description) WHERE meta_description IS NOT NULL");
        DB::statement("UPDATE contents SET meta_keywords_json = JSON_OBJECT('uk', meta_keywords, 'en', meta_keywords) WHERE meta_keywords IS NOT NULL");

        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn(['page_title', 'title', 'content', 'meta_title', 'meta_description', 'meta_keywords']);
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->renameColumn('page_title_json', 'page_title');
            $table->renameColumn('title_json', 'title');
            $table->renameColumn('content_json', 'content');
            $table->renameColumn('meta_title_json', 'meta_title');
            $table->renameColumn('meta_description_json', 'meta_description');
            $table->renameColumn('meta_keywords_json', 'meta_keywords');
        });
    }
};
