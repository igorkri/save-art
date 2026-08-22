<?php

namespace Tests\Feature\Filament;

use App\Models\ArtCategory;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ArtCategoryTreePageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_art_categories_tree_page_renders_without_error(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        ArtCategory::factory()->create(['name' => ['uk' => 'Тест', 'en' => 'Test']]);

        $response = $this->actingAs($admin)->get('/admin/art-categories');

        $response->assertOk();
    }
}
