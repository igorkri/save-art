<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the Ukrainian value while changing the storage format from JSON to VARCHAR.
        Schema::table('art_catalogs', function (Blueprint $table): void {
            $table->string('title_varchar')->nullable();
        });

        foreach (DB::table('art_catalogs')->select(['id', 'title'])->cursor() as $catalog) {
            $decoded = json_decode((string) $catalog->title, true);
            $title = is_array($decoded) ? ($decoded['uk'] ?? '') : (string) $catalog->title;

            DB::table('art_catalogs')
                ->where('id', $catalog->id)
                ->update(['title_varchar' => $title]);
        }

        Schema::table('art_catalogs', function (Blueprint $table): void {
            $table->dropColumn('title');
        });

        Schema::table('art_catalogs', function (Blueprint $table): void {
            $table->renameColumn('title_varchar', 'title');
        });
    }

    public function down(): void
    {
        Schema::table('art_catalogs', function (Blueprint $table): void {
            $table->json('title_json')->nullable();
        });

        foreach (DB::table('art_catalogs')->select(['id', 'title'])->cursor() as $catalog) {
            DB::table('art_catalogs')
                ->where('id', $catalog->id)
                ->update(['title_json' => json_encode(['uk' => $catalog->title, 'en' => $catalog->title])]);
        }

        Schema::table('art_catalogs', function (Blueprint $table): void {
            $table->dropColumn('title');
        });

        Schema::table('art_catalogs', function (Blueprint $table): void {
            $table->renameColumn('title_json', 'title');
        });
    }
};
