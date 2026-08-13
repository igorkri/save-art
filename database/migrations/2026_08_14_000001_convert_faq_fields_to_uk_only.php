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
        Schema::table('faq_categories', function (Blueprint $table) {
            $table->string('name_uk')->nullable()->after('name');
        });

        DB::statement("UPDATE faq_categories SET name_uk = JSON_UNQUOTE(JSON_EXTRACT(name, '$.uk')) WHERE name IS NOT NULL");

        Schema::table('faq_categories', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('faq_categories', function (Blueprint $table) {
            $table->renameColumn('name_uk', 'name');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question_uk')->nullable()->after('question');
            $table->text('answer_uk')->nullable()->after('answer');
        });

        DB::statement("UPDATE faqs SET question_uk = JSON_UNQUOTE(JSON_EXTRACT(question, '$.uk')) WHERE question IS NOT NULL");
        DB::statement("UPDATE faqs SET answer_uk = JSON_UNQUOTE(JSON_EXTRACT(answer, '$.uk')) WHERE answer IS NOT NULL");

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['question', 'answer']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->renameColumn('question_uk', 'question');
            $table->renameColumn('answer_uk', 'answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faq_categories', function (Blueprint $table) {
            $table->json('name_json')->nullable()->after('name');
        });

        DB::statement("UPDATE faq_categories SET name_json = JSON_OBJECT('uk', name, 'en', name) WHERE name IS NOT NULL");

        Schema::table('faq_categories', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('faq_categories', function (Blueprint $table) {
            $table->renameColumn('name_json', 'name');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->json('question_json')->nullable()->after('question');
            $table->json('answer_json')->nullable()->after('answer');
        });

        DB::statement("UPDATE faqs SET question_json = JSON_OBJECT('uk', question, 'en', question) WHERE question IS NOT NULL");
        DB::statement("UPDATE faqs SET answer_json = JSON_OBJECT('uk', answer, 'en', answer) WHERE answer IS NOT NULL");

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['question', 'answer']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->renameColumn('question_json', 'question');
            $table->renameColumn('answer_json', 'answer');
        });
    }
};
