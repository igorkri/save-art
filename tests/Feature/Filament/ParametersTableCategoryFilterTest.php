<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Parameters\Pages\ListParameters;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ParametersTableCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_tree_filter_shows_only_parameters_of_the_selected_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $categoryA = ArtCategory::factory()->create(['name' => ['uk' => 'Живопис', 'en' => 'Painting']]);
        $categoryB = ArtCategory::factory()->create(['name' => ['uk' => 'Графіка', 'en' => 'Graphics']]);

        $parameterA = Parameter::factory()->create(['art_category_id' => $categoryA->id]);
        $parameterB = Parameter::factory()->create(['art_category_id' => $categoryB->id]);

        Livewire::actingAs($admin)
            ->test(ListParameters::class)
            ->assertCanSeeTableRecords([$parameterA, $parameterB])
            ->filterTable('art_category_id', ['value' => (string) $categoryA->id])
            ->assertCanSeeTableRecords([$parameterA])
            ->assertCanNotSeeTableRecords([$parameterB]);
    }

    public function test_header_hint_explains_when_reordering_becomes_available(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = ArtCategory::factory()->create();

        $component = Livewire::actingAs($admin)->test(ListParameters::class);

        $component->assertSee('спершу оберіть одну категорію у фільтрі');

        $component->filterTable('art_category_id', ['value' => (string) $category->id]);

        $component->assertSee('Перетягніть рядки, щоб змінити порядок');
    }

    public function test_reordering_is_blocked_until_a_single_category_is_selected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = ArtCategory::factory()->create();

        $first = Parameter::factory()->create(['art_category_id' => $category->id, 'sort_order' => 0]);
        $second = Parameter::factory()->create(['art_category_id' => $category->id, 'sort_order' => 1]);

        $component = Livewire::actingAs($admin)->test(ListParameters::class);

        // Без активного фільтра за категорією reorderTable — no-op.
        $component->call('reorderTable', [$second->getKey(), $first->getKey()]);
        $this->assertSame(0, $first->refresh()->sort_order);
        $this->assertSame(1, $second->refresh()->sort_order);

        // Після вибору категорії перетягування спрацьовує.
        $component
            ->filterTable('art_category_id', ['value' => (string) $category->id])
            ->call('reorderTable', [$second->getKey(), $first->getKey()]);

        $this->assertSame(2, $first->refresh()->sort_order);
        $this->assertSame(1, $second->refresh()->sort_order);
    }
}
