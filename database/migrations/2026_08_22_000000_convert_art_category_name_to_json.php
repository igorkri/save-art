<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('art_categories', function (Blueprint $table): void {
            $table->json('name_json')->nullable();
        });

        foreach (DB::table('art_categories')->select(['id', 'name'])->cursor() as $category) {
            DB::table('art_categories')
                ->where('id', $category->id)
                ->update(['name_json' => json_encode(['uk' => $category->name, 'en' => $category->name])]);
        }

        Schema::table('art_categories', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('art_categories', function (Blueprint $table): void {
            $table->renameColumn('name_json', 'name');
        });
    }

    public function down(): void
    {
        Schema::table('art_categories', function (Blueprint $table): void {
            $table->string('name_varchar')->nullable();
        });

        foreach (DB::table('art_categories')->select(['id', 'name'])->cursor() as $category) {
            $decoded = json_decode((string) $category->name, true);
            $name = is_array($decoded) ? ($decoded['uk'] ?? '') : (string) $category->name;

            DB::table('art_categories')
                ->where('id', $category->id)
                ->update(['name_varchar' => $name]);
        }

        Schema::table('art_categories', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('art_categories', function (Blueprint $table): void {
            $table->renameColumn('name_varchar', 'name');
        });
    }
};
