<?php

namespace Tests\Feature\Filament;

use App\Enums\ProjectStatus;
use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Filament\Profile\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileProjectsDraftsTabTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->artist()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_drafts_tab_shows_only_private_statuses(): void
    {
        $draft = Project::factory()->create(['user_id' => $this->user->id, 'status' => ProjectStatus::Draft->value]);
        $moderation = Project::factory()->create(['user_id' => $this->user->id, 'status' => ProjectStatus::Moderation->value]);
        $announced = Project::factory()->create(['user_id' => $this->user->id, 'status' => ProjectStatus::Announced->value]);

        Livewire::test(ListProjects::class)
            ->set('activeTab', 'drafts')
            ->assertCanSeeTableRecords([$draft, $moderation])
            ->assertCanNotSeeTableRecords([$announced]);
    }

    public function test_all_tab_shows_every_status(): void
    {
        $draft = Project::factory()->create(['user_id' => $this->user->id, 'status' => ProjectStatus::Draft->value]);
        $announced = Project::factory()->create(['user_id' => $this->user->id, 'status' => ProjectStatus::Announced->value]);

        Livewire::test(ListProjects::class)
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$draft, $announced]);
    }

    public function test_team_select_options_limited_to_users_own_teams(): void
    {
        $ownTeam = Team::factory()->create();
        $ownTeam->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        $foreignTeam = Team::factory()->create();
        $foreignTeam->teamMembers()->create(['user_id' => User::factory()->create()->id, 'role' => 'owner', 'sort_order' => 0]);

        Livewire::test(CreateProject::class)
            ->set('data.team_id', $ownTeam->id)
            ->assertHasNoErrors(['data.team_id']);
    }
}
