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
        Schema::table('terms_sections', function (Blueprint $table) {
            $table->string('heading_uk', 500)->nullable()->after('heading');
        });

        DB::statement("UPDATE terms_sections SET heading_uk = JSON_UNQUOTE(JSON_EXTRACT(heading, '$.uk')) WHERE heading IS NOT NULL");

        Schema::table('terms_sections', function (Blueprint $table) {
            $table->dropColumn('heading');
        });

        Schema::table('terms_sections', function (Blueprint $table) {
            $table->renameColumn('heading_uk', 'heading');
        });

        Schema::table('terms_blocks', function (Blueprint $table) {
            $table->string('heading_uk', 500)->nullable()->after('heading');
            $table->text('paragraphs_uk')->nullable()->after('paragraphs');
            $table->text('list_items_uk')->nullable()->after('list_items');
        });

        DB::statement("UPDATE terms_blocks SET heading_uk = JSON_UNQUOTE(JSON_EXTRACT(heading, '$.uk')) WHERE heading IS NOT NULL");
        DB::statement("UPDATE terms_blocks SET paragraphs_uk = JSON_UNQUOTE(JSON_EXTRACT(paragraphs, '$.uk')) WHERE paragraphs IS NOT NULL");
        DB::statement("UPDATE terms_blocks SET list_items_uk = JSON_UNQUOTE(JSON_EXTRACT(list_items, '$.uk')) WHERE list_items IS NOT NULL");

        Schema::table('terms_blocks', function (Blueprint $table) {
            $table->dropColumn(['heading', 'paragraphs', 'list_items']);
        });

        Schema::table('terms_blocks', function (Blueprint $table) {
            $table->renameColumn('heading_uk', 'heading');
            $table->renameColumn('paragraphs_uk', 'paragraphs');
            $table->renameColumn('list_items_uk', 'list_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terms_sections', function (Blueprint $table) {
            $table->json('heading_json')->nullable()->after('heading');
        });

        DB::statement("UPDATE terms_sections SET heading_json = JSON_OBJECT('uk', heading, 'en', heading) WHERE heading IS NOT NULL");

        Schema::table('terms_sections', function (Blueprint $table) {
            $table->dropColumn('heading');
        });

        Schema::table('terms_sections', function (Blueprint $table) {
            $table->renameColumn('heading_json', 'heading');
        });

        Schema::table('terms_blocks', function (Blueprint $table) {
            $table->json('heading_json')->nullable()->after('heading');
            $table->json('paragraphs_json')->nullable()->after('paragraphs');
            $table->json('list_items_json')->nullable()->after('list_items');
        });

        DB::statement("UPDATE terms_blocks SET heading_json = JSON_OBJECT('uk', heading, 'en', heading) WHERE heading IS NOT NULL");
        DB::statement("UPDATE terms_blocks SET paragraphs_json = JSON_OBJECT('uk', paragraphs, 'en', paragraphs) WHERE paragraphs IS NOT NULL");
        DB::statement("UPDATE terms_blocks SET list_items_json = JSON_OBJECT('uk', list_items, 'en', list_items) WHERE list_items IS NOT NULL");

        Schema::table('terms_blocks', function (Blueprint $table) {
            $table->dropColumn(['heading', 'paragraphs', 'list_items']);
        });

        Schema::table('terms_blocks', function (Blueprint $table) {
            $table->renameColumn('heading_json', 'heading');
            $table->renameColumn('paragraphs_json', 'paragraphs');
            $table->renameColumn('list_items_json', 'list_items');
        });
    }
};
