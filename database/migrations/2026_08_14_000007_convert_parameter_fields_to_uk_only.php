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
        Schema::table('parameters', function (Blueprint $table) {
            $table->string('name_uk')->nullable()->after('name');
        });

        DB::statement("UPDATE parameters SET name_uk = JSON_UNQUOTE(JSON_EXTRACT(name, '$.uk')) WHERE name IS NOT NULL");

        Schema::table('parameters', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('parameters', function (Blueprint $table) {
            $table->renameColumn('name_uk', 'name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->json('name_json')->nullable()->after('name');
        });

        DB::statement("UPDATE parameters SET name_json = JSON_OBJECT('uk', name, 'en', name) WHERE name IS NOT NULL");

        Schema::table('parameters', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('parameters', function (Blueprint $table) {
            $table->renameColumn('name_json', 'name');
        });
    }
};
