<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileProjectCreateLiveValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->artist()->create());
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_clearing_required_title_shows_error_immediately_without_submitting(): void
    {
        Livewire::test(CreateProject::class)
            ->set('data.title', 'Тимчасова назва')
            ->set('data.title', '')
            ->assertHasErrors(['data.title' => 'required']);
    }

    public function test_valid_title_has_no_error_after_live_update(): void
    {
        Livewire::test(CreateProject::class)
            ->set('data.title', 'Тіні старого міста')
            ->assertHasNoErrors(['data.title']);
    }

    public function test_non_numeric_budget_goal_shows_error_immediately(): void
    {
        Livewire::test(CreateProject::class)
            ->set('data.budget_goal', 'not-a-number')
            ->assertHasErrors(['data.budget_goal' => 'numeric']);
    }
}
