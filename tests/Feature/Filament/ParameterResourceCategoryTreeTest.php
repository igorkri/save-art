<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Parameters\Pages\CreateParameter;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ParameterResourceCategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_parameter_with_category_selected_from_tree(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = ArtCategory::factory()->create(['name' => ['uk' => 'Живопис', 'en' => 'Painting']]);

        Livewire::actingAs($admin)
            ->test(CreateParameter::class)
            ->set('data.art_category_id', (string) $category->id)
            ->set('data.name.uk', 'Розмір')
            ->set('data.name.en', 'Size')
            ->call('create')
            ->assertHasNoErrors();

        $created = Parameter::where('art_category_id', $category->id)->first();
        $this->assertNotNull($created);
        $this->assertSame('Розмір', $created->getLabel('uk'));
    }
}
