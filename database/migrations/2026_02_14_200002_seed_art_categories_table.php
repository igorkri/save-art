<?php

use App\Enums\ArtCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $order = 0;
        foreach (ArtCategory::cases() as $case) {
            DB::table('art_categories')->insert([
                'parent_id' => null,
                'slug' => $case->value,
                'name' => json_encode(['uk' => $case->getLabel('uk'), 'en' => $case->getLabel('en')]),
                'sort_order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $parentIds = DB::table('art_categories')->whereNull('parent_id')->pluck('id', 'slug');
        $subOrder = 0;
        foreach (ArtCategory::cases() as $case) {
            $parentId = $parentIds[$case->value] ?? null;
            if (! $parentId) {
                continue;
            }
            foreach ($case->getSubcategoriesWithTranslations() as $subSlug => $translations) {
                DB::table('art_categories')->insert([
                    'parent_id' => $parentId,
                    'slug' => $subSlug,
                    'name' => json_encode(['uk' => $translations['uk'] ?? '', 'en' => $translations['en'] ?? $translations['uk'] ?? '']),
                    'sort_order' => $subOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('art_categories')->truncate();
    }
};
