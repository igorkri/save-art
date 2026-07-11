<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Конвертуємо існуючі дані в JSON формат
        DB::statement("UPDATE documents SET name = JSON_OBJECT('uk', name, 'en', name) WHERE name IS NOT NULL");

        Schema::table('documents', function (Blueprint $table) {
            $table->json('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Беремо uk версію як fallback
        DB::statement("UPDATE documents SET name = JSON_UNQUOTE(JSON_EXTRACT(name, '$.uk')) WHERE name IS NOT NULL");

        Schema::table('documents', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
