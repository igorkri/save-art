<?php

namespace App\Support;

use App\Models\ArtCategory;

/**
 * Резолвінг comma-separated slug'ів art_category/art_subcategory (як приходять у
 * query-параметрах публічних списків) у id категорій — спільна логіка для
 * фільтрації проєктів і авторів (митців/організацій) по галузі мистецтва.
 */
class ArtCategoryFilter
{
    /**
     * @return int[] id кореневих категорій разом з усіма їхніми підкатегоріями
     */
    public static function resolveCategoryIds(?string $commaSeparatedSlugs): array
    {
        if (! $commaSeparatedSlugs) {
            return [];
        }

        $slugs = array_map('trim', explode(',', $commaSeparatedSlugs));

        return ArtCategory::whereIn('slug', $slugs)
            ->get()
            ->flatMap(fn (ArtCategory $c) => $c->parent_id
                ? [$c->id]
                : [$c->id, ...$c->children()->pluck('id')]
            )
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return int[] id підкатегорій (parent_id не null) за slug
     */
    public static function resolveSubcategoryIds(?string $commaSeparatedSlugs): array
    {
        if (! $commaSeparatedSlugs) {
            return [];
        }

        $slugs = array_map('trim', explode(',', $commaSeparatedSlugs));

        return ArtCategory::whereNotNull('parent_id')->whereIn('slug', $slugs)->pluck('id')->all();
    }
}
