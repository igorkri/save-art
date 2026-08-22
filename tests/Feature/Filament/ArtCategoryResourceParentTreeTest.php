<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ArtCategories\Pages\CreateArtCategory;
use App\Filament\Resources\ArtCategories\Pages\EditArtCategory;
use App\Models\ArtCategory;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ArtCategoryResourceParentTreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);
    }

    public function test_can_create_category_with_parent_selected_from_tree(): void
    {
        $parent = ArtCategory::factory()->create(['name' => ['uk' => 'Живопис', 'en' => 'Painting']]);

        Livewire::test(CreateArtCategory::class)
            ->set('data.parent_id', (string) $parent->id)
            ->set('data.name.uk', 'Акварель')
            ->set('data.name.en', 'Watercolor')
            ->call('create')
            ->assertHasNoErrors();

        $created = ArtCategory::where('parent_id', $parent->id)->first();
        $this->assertNotNull($created);
        $this->assertSame('Акварель', $created->getLabel('uk'));
    }

    public function test_editing_a_category_excludes_itself_from_parent_options(): void
    {
        $category = ArtCategory::factory()->create(['name' => ['uk' => 'Скульптура', 'en' => 'Sculpture']]);
        $other = ArtCategory::factory()->create(['name' => ['uk' => 'Графіка', 'en' => 'Graphics']]);

        $html = $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(EditArtCategory::getUrl(['record' => $category->id]))
            ->assertOk()
            ->getContent();

        // Дерево передається у Blade як JSON.parse('...') з \uXXXX-екрануванням —
        // спершу декодуємо як JS-рядок, потім парсимо отриманий JSON.
        preg_match("/options: JSON\.parse\('(.+?)'\)/", $html, $matches);
        $jsonText = json_decode('"'.($matches[1] ?? '').'"');
        $options = json_decode($jsonText, true);
        $values = collect($options)
            ->flatMap(fn ($node) => array_merge([$node['value']], array_column($node['children'] ?? [], 'value')))
            ->all();

        $this->assertNotContains((string) $category->id, $values);
        $this->assertContains((string) $other->id, $values);
    }
}
