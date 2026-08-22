<?php

namespace Tests\Unit\Models;

use App\Models\ArtCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtCategorySlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_generated_from_ukrainian_name_when_not_provided(): void
    {
        $category = ArtCategory::create([
            'name' => ['uk' => 'Сценічне мистецтво', 'en' => 'Performing arts'],
            'sort_order' => 0,
        ]);

        $this->assertSame('scenicne-mistectvo', $category->slug);
    }

    public function test_duplicate_names_get_a_unique_incremented_slug(): void
    {
        ArtCategory::create([
            'name' => ['uk' => 'Живопис', 'en' => 'Painting'],
            'sort_order' => 0,
        ]);

        $second = ArtCategory::create([
            'name' => ['uk' => 'Живопис', 'en' => 'Painting'],
            'sort_order' => 1,
        ]);

        $this->assertSame('zivopis-1', $second->slug);
    }

    public function test_explicit_slug_is_not_overwritten(): void
    {
        $category = ArtCategory::create([
            'slug' => 'custom-slug',
            'name' => ['uk' => 'Скульптура', 'en' => 'Sculpture'],
            'sort_order' => 0,
        ]);

        $this->assertSame('custom-slug', $category->slug);
    }
}
