<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePublishedProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_owner_can_update_title_of_published_project(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Announced,
            'title' => ['uk' => 'Стара назва', 'en' => 'Old title'],
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/my/projects/{$project->id}", [
                'title' => ['uk' => 'Нова назва', 'en' => 'New title'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.title.uk', 'Нова назва');
        $response->assertJsonPath('data.title.en', 'New title');
    }

    public function test_owner_can_update_description_of_published_project(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/my/projects/{$project->id}", [
                'short_description' => ['uk' => 'Новий опис', 'en' => 'New description'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.short_description.uk', 'Новий опис');
    }

    public function test_owner_can_update_tags_of_published_project(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Paused,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/my/projects/{$project->id}", [
                'tags' => ['uk' => 'живопис, арт', 'en' => 'painting, art'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.tags.uk', 'живопис, арт');
    }

    public function test_cannot_update_budget_of_published_project(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Announced,
            'budget_goal' => 50000,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/my/projects/{$project->id}", [
                'budget_goal' => 100000,
            ]);

        // Запит успішний, але budget_goal не змінюється (не в дозволених полях)
        $response->assertOk();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'budget_goal' => 50000, // залишається старе значення
        ]);
    }

    public function test_cannot_update_category_of_published_project(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::InProgress,
            'art_category' => 'visual',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/my/projects/{$project->id}", [
                'art_category' => 'music',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'art_category' => 'visual', // залишається старе значення
        ]);
    }

    public function test_cannot_update_completed_project(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Completed,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/my/projects/{$project->id}", [
                'title' => ['uk' => 'Нова назва', 'en' => 'New title'],
            ]);

        $response->assertForbidden();
    }

    public function test_other_user_cannot_update_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->actingAs($otherUser)
            ->patchJson("/api/v1/my/projects/{$project->id}", [
                'title' => ['uk' => 'Хак', 'en' => 'Hack'],
            ]);

        $response->assertForbidden();
    }

    public function test_full_update_still_works_for_drafts(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Draft,
            'budget_goal' => 50000,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/my/projects/{$project->id}", [
                'budget_goal' => 100000,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'budget_goal' => 100000,
        ]);
    }

    public function test_full_update_forbidden_for_published(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/my/projects/{$project->id}", [
                'budget_goal' => 100000,
            ]);

        $response->assertForbidden();
    }
}
