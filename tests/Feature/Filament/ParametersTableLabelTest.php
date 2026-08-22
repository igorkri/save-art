<?php

namespace Tests\Feature\Filament;

use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ParametersTableLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_and_category_columns_render_the_label_once(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = ArtCategory::factory()->create(['name' => ['uk' => 'Живопис', 'en' => 'Painting']]);
        Parameter::factory()->create([
            'art_category_id' => $category->id,
            'name' => ['uk' => 'Розмір полотна', 'en' => 'Canvas size'],
        ]);

        $html = $this->actingAs($admin)->get('/admin/parameters')->assertOk()->getContent();

        // Раніше formatStateUsing застосовувався до кожного елемента масиву
        // ['uk' => ..., 'en' => ...] окремо, і Filament склеював їх через ", ",
        // тому назва/категорія відображались продубльованими через кому.
        $this->assertStringNotContainsString('Розмір полотна, Розмір полотна', $html);
        $this->assertStringNotContainsString('Живопис, Живопис', $html);
        $this->assertStringContainsString('Розмір полотна', $html);
        $this->assertStringContainsString('Живопис', $html);
    }
}
