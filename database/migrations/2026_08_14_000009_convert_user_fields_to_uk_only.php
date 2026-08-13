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
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name_uk')->nullable()->after('full_name');
            $table->string('profession_uk')->nullable()->after('profession');
            $table->string('tags_uk')->nullable()->after('tags');
            $table->string('country_uk')->nullable()->after('country');
            $table->string('region_uk')->nullable()->after('region');
            $table->string('city_uk')->nullable()->after('city');
            $table->text('description_uk')->nullable()->after('description');
        });

        DB::statement("UPDATE users SET full_name_uk = JSON_UNQUOTE(JSON_EXTRACT(full_name, '$.uk')) WHERE full_name IS NOT NULL");
        DB::statement("UPDATE users SET profession_uk = JSON_UNQUOTE(JSON_EXTRACT(profession, '$.uk')) WHERE profession IS NOT NULL");
        DB::statement("UPDATE users SET tags_uk = JSON_UNQUOTE(JSON_EXTRACT(tags, '$.uk')) WHERE tags IS NOT NULL");
        DB::statement("UPDATE users SET country_uk = JSON_UNQUOTE(JSON_EXTRACT(country, '$.uk')) WHERE country IS NOT NULL");
        DB::statement("UPDATE users SET region_uk = JSON_UNQUOTE(JSON_EXTRACT(region, '$.uk')) WHERE region IS NOT NULL");
        DB::statement("UPDATE users SET city_uk = JSON_UNQUOTE(JSON_EXTRACT(city, '$.uk')) WHERE city IS NOT NULL");
        DB::statement("UPDATE users SET description_uk = JSON_UNQUOTE(JSON_EXTRACT(description, '$.uk')) WHERE description IS NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'profession', 'tags', 'country', 'region', 'city', 'description']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('full_name_uk', 'full_name');
            $table->renameColumn('profession_uk', 'profession');
            $table->renameColumn('tags_uk', 'tags');
            $table->renameColumn('country_uk', 'country');
            $table->renameColumn('region_uk', 'region');
            $table->renameColumn('city_uk', 'city');
            $table->renameColumn('description_uk', 'description');
        });

        Schema::table('profile_legals', function (Blueprint $table) {
            $table->string('name_uk')->nullable()->after('name');
            $table->string('authorized_person_uk')->nullable()->after('authorized_person');
            $table->string('address_uk')->nullable()->after('address');
        });

        DB::statement("UPDATE profile_legals SET name_uk = JSON_UNQUOTE(JSON_EXTRACT(name, '$.uk')) WHERE name IS NOT NULL");
        DB::statement("UPDATE profile_legals SET authorized_person_uk = JSON_UNQUOTE(JSON_EXTRACT(authorized_person, '$.uk')) WHERE authorized_person IS NOT NULL");
        DB::statement("UPDATE profile_legals SET address_uk = JSON_UNQUOTE(JSON_EXTRACT(address, '$.uk')) WHERE address IS NOT NULL");

        Schema::table('profile_legals', function (Blueprint $table) {
            $table->dropColumn(['name', 'authorized_person', 'address']);
        });

        Schema::table('profile_legals', function (Blueprint $table) {
            $table->renameColumn('name_uk', 'name');
            $table->renameColumn('authorized_person_uk', 'authorized_person');
            $table->renameColumn('address_uk', 'address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('full_name_json')->nullable()->after('full_name');
            $table->json('profession_json')->nullable()->after('profession');
            $table->json('tags_json')->nullable()->after('tags');
            $table->json('country_json')->nullable()->after('country');
            $table->json('region_json')->nullable()->after('region');
            $table->json('city_json')->nullable()->after('city');
            $table->json('description_json')->nullable()->after('description');
        });

        DB::statement("UPDATE users SET full_name_json = JSON_OBJECT('uk', full_name, 'en', full_name) WHERE full_name IS NOT NULL");
        DB::statement("UPDATE users SET profession_json = JSON_OBJECT('uk', profession, 'en', profession) WHERE profession IS NOT NULL");
        DB::statement("UPDATE users SET tags_json = JSON_OBJECT('uk', tags, 'en', tags) WHERE tags IS NOT NULL");
        DB::statement("UPDATE users SET country_json = JSON_OBJECT('uk', country, 'en', country) WHERE country IS NOT NULL");
        DB::statement("UPDATE users SET region_json = JSON_OBJECT('uk', region, 'en', region) WHERE region IS NOT NULL");
        DB::statement("UPDATE users SET city_json = JSON_OBJECT('uk', city, 'en', city) WHERE city IS NOT NULL");
        DB::statement("UPDATE users SET description_json = JSON_OBJECT('uk', description, 'en', description) WHERE description IS NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'profession', 'tags', 'country', 'region', 'city', 'description']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('full_name_json', 'full_name');
            $table->renameColumn('profession_json', 'profession');
            $table->renameColumn('tags_json', 'tags');
            $table->renameColumn('country_json', 'country');
            $table->renameColumn('region_json', 'region');
            $table->renameColumn('city_json', 'city');
            $table->renameColumn('description_json', 'description');
        });

        Schema::table('profile_legals', function (Blueprint $table) {
            $table->json('name_json')->nullable()->after('name');
            $table->json('authorized_person_json')->nullable()->after('authorized_person');
            $table->json('address_json')->nullable()->after('address');
        });

        DB::statement("UPDATE profile_legals SET name_json = JSON_OBJECT('uk', name, 'en', name) WHERE name IS NOT NULL");
        DB::statement("UPDATE profile_legals SET authorized_person_json = JSON_OBJECT('uk', authorized_person, 'en', authorized_person) WHERE authorized_person IS NOT NULL");
        DB::statement("UPDATE profile_legals SET address_json = JSON_OBJECT('uk', address, 'en', address) WHERE address IS NOT NULL");

        Schema::table('profile_legals', function (Blueprint $table) {
            $table->dropColumn(['name', 'authorized_person', 'address']);
        });

        Schema::table('profile_legals', function (Blueprint $table) {
            $table->renameColumn('name_json', 'name');
            $table->renameColumn('authorized_person_json', 'authorized_person');
            $table->renameColumn('address_json', 'address');
        });
    }
};
