<?php

namespace Tests\Feature\Filament;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Filament\Profile\Resources\Projects\Pages\EditProject;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileProjectEditActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $artist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->artist()->create();

        $this->actingAs($this->artist);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_can_submit_draft_project_for_moderation(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->artist->id,
            'status' => ProjectStatus::Draft,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->callAction('submitForModeration')
            ->assertNotified();

        $project->refresh();
        $this->assertSame(ProjectStatus::Moderation, $project->status);
        $this->assertSame(ModerationStatus::Pending, $project->status_moderation);
    }

    public function test_submit_for_moderation_action_is_hidden_for_announced_project(): void
    {
        $project = Project::factory()->announced()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertActionHidden('submitForModeration');
    }

    public function test_artist_can_complete_in_progress_project_with_final_result(): void
    {
        $project = Project::factory()->inProgress()->create([
            'user_id' => $this->artist->id,
            'final_result' => ['description' => 'Готово'],
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->callAction('complete')
            ->assertNotified();

        $this->assertSame(ProjectStatus::Completed, $project->fresh()->status);
    }

    public function test_complete_action_fails_without_final_result(): void
    {
        $project = Project::factory()->inProgress()->create([
            'user_id' => $this->artist->id,
            'final_result' => null,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->callAction('complete')
            ->assertNotified();

        $this->assertSame(ProjectStatus::InProgress, $project->fresh()->status);
    }

    public function test_complete_action_is_hidden_for_draft_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->artist->id,
            'status' => ProjectStatus::Draft,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertActionHidden('complete');
    }

    public function test_artist_can_mark_completed_project_as_sold(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->artist->id,
            'status' => ProjectStatus::Completed,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->callAction('markAsSold')
            ->assertNotified();

        $this->assertSame(ProjectStatus::Sold, $project->fresh()->status);
    }

    public function test_mark_as_sold_action_is_hidden_for_in_progress_project(): void
    {
        $project = Project::factory()->inProgress()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertActionHidden('markAsSold');
    }

    public function test_artist_can_pause_in_progress_project(): void
    {
        $project = Project::factory()->inProgress()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->callAction('pause')
            ->assertNotified();

        $this->assertSame(ProjectStatus::Paused, $project->fresh()->status);
    }

    public function test_pause_action_is_hidden_for_draft_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->artist->id,
            'status' => ProjectStatus::Draft,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertActionHidden('pause');
    }

    public function test_artist_can_resume_paused_project(): void
    {
        $project = Project::factory()->inProgress()->create([
            'user_id' => $this->artist->id,
            'status' => ProjectStatus::Paused,
            'budget_goal' => 1000,
            'budget_collected' => 1000,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->callAction('resume')
            ->assertNotified();

        $this->assertSame(ProjectStatus::InProgress, $project->fresh()->status);
    }

    public function test_resume_action_is_hidden_for_in_progress_project(): void
    {
        $project = Project::factory()->inProgress()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertActionHidden('resume');
    }
}
