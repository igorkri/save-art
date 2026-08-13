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
        Schema::table('services', function (Blueprint $table) {
            $table->string('title_uk')->nullable()->after('title');
            $table->text('description_uk')->nullable()->after('description');
            $table->string('location_uk')->nullable()->after('location');
        });

        DB::statement("UPDATE services SET title_uk = JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk')) WHERE title IS NOT NULL");
        DB::statement("UPDATE services SET description_uk = JSON_UNQUOTE(JSON_EXTRACT(description, '$.uk')) WHERE description IS NOT NULL");
        DB::statement("UPDATE services SET location_uk = JSON_UNQUOTE(JSON_EXTRACT(location, '$.uk')) WHERE location IS NOT NULL");

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'location']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('title_uk', 'title');
            $table->renameColumn('description_uk', 'description');
            $table->renameColumn('location_uk', 'location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('title_json')->nullable()->after('title');
            $table->json('description_json')->nullable()->after('description');
            $table->json('location_json')->nullable()->after('location');
        });

        DB::statement("UPDATE services SET title_json = JSON_OBJECT('uk', title, 'en', title) WHERE title IS NOT NULL");
        DB::statement("UPDATE services SET description_json = JSON_OBJECT('uk', description, 'en', description) WHERE description IS NOT NULL");
        DB::statement("UPDATE services SET location_json = JSON_OBJECT('uk', location, 'en', location) WHERE location IS NOT NULL");

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'location']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('title_json', 'title');
            $table->renameColumn('description_json', 'description');
            $table->renameColumn('location_json', 'location');
        });
    }
};
