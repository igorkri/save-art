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
        $this->registerSqliteJsonUnquote();

        Schema::table('documents', function (Blueprint $table) {
            $table->string('name_uk')->nullable()->after('name');
            $table->text('description_uk')->nullable()->after('description');
        });

        // Переносимо українську версію з JSON у звичайні колонки, втрачаючи en безповоротно
        DB::statement("UPDATE documents SET name_uk = JSON_UNQUOTE(JSON_EXTRACT(name, '$.uk')) WHERE name IS NOT NULL");
        DB::statement("UPDATE documents SET description_uk = JSON_UNQUOTE(JSON_EXTRACT(description, '$.uk')) WHERE description IS NOT NULL");

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('name_uk', 'name');
            $table->renameColumn('description_uk', 'description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->registerSqliteJsonUnquote();

        Schema::table('documents', function (Blueprint $table) {
            $table->json('name_json')->nullable()->after('name');
            $table->json('description_json')->nullable()->after('description');
        });

        // Дані en втрачені безповоротно, тому дублюємо uk в обидва ключі
        DB::statement("UPDATE documents SET name_json = JSON_OBJECT('uk', name, 'en', name) WHERE name IS NOT NULL");
        DB::statement("UPDATE documents SET description_json = JSON_OBJECT('uk', description, 'en', description) WHERE description IS NOT NULL");

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('name_json', 'name');
            $table->renameColumn('description_json', 'description');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->json('description')->nullable()->change();
        });
    }

    private function registerSqliteJsonUnquote(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::connection()->getPdo()->sqliteCreateFunction(
            'JSON_UNQUOTE',
            static fn (mixed $value): mixed => $value,
        );
    }
};
