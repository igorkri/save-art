<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileProjectParametersTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->artist()->create());
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    /**
     * Характеристики тепер рендеряться через Repeater::table() (компактні
     * рядки замість карток) — це суто верстка, statePath полів лишився
     * пласким, тому значення мають зберігатись так само, як і раніше.
     */
    public function test_artist_can_select_parameter_value_in_table_repeater(): void
    {
        $category = ArtCategory::factory()->create();
        $parameter = Parameter::factory()->for($category)->create();
        $value = ParameterValue::factory()->for($parameter)->create();

        Livewire::test(CreateProject::class)
            ->set('data.art_category_id', $category->id)
            ->set('data.title', 'Проєкт з характеристиками')
            ->set('data.currency', 'UAH')
            ->set('data.budget_goal', 500)
            ->set('data.project_parameter_values', [
                [
                    'parameter_id' => $parameter->id,
                    'parameter_type' => $parameter->type->value,
                    'parameter_value_id' => $value->id,
                    'custom_value' => null,
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $project = Project::where('title', 'Проєкт з характеристиками')->firstOrFail();

        $this->assertDatabaseHas('project_parameters', [
            'project_id' => $project->id,
            'parameter_id' => $parameter->id,
            'parameter_value_id' => $value->id,
        ]);
    }
}
