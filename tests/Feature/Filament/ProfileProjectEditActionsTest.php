<?php

namespace Tests\Feature\Filament;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Filament\Profile\Resources\Projects\Pages\EditProject;
use App\Models\ArtCategory;
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

    public function test_artist_can_save_a_draft_and_issue_a_project_preview_grant(): void
    {
        $category = ArtCategory::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->artist->id,
            'art_category_id' => $category->id,
            'user_type' => UserType::Personal,
            'is_legal' => false,
            'status' => ProjectStatus::Draft,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.title', 'Оновлена назва для перегляду')
            ->call('previewProject')
            ->assertHasNoErrors()
            ->assertNotified();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Оновлена назва для перегляду',
        ]);
        $this->assertDatabaseHas('impersonation_tokens', [
            'user_id' => $this->artist->id,
            'project_slug' => $project->slug,
            'target_app' => 'save_art_project_preview',
        ]);
    }

    public function test_submit_for_moderation_action_is_hidden_for_announced_project(): void
    {
        $project = Project::factory()->announced()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertActionHidden('submitForModeration');
    }

    public function test_saving_pending_moderation_project_returns_it_to_draft(): void
    {
        $project = Project::factory()->moderation()->create([
            'user_id' => $this->artist->id,
            'status_moderation' => ModerationStatus::Pending,
            'user_type' => UserType::Personal,
            'is_legal' => false,
            'team_id' => null,
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.title', 'Виправлена назва')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(ProjectStatus::Draft, $project->fresh()->status);
    }

    public function test_fixed_fields_are_not_saved_for_announced_project(): void
    {
        $project = Project::factory()->announced()->create([
            'user_id' => $this->artist->id,
            'title' => 'Зафіксована назва',
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.title', 'Заборонена зміна')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Зафіксована назва', $project->fresh()->title);
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
