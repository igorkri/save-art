<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\ArtCategory;
use App\Models\Project;
use App\Models\User;

class UpdatePublishedProjectTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_owner_cannot_update_fixed_fields_after_publication(): void
    {
        $project = Project::factory()->for($this->user)->announced()->create();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", [
                'title' => 'Нова назва',
                'short_description' => 'Новий опис',
                'tags' => ['новий тег'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'short_description', 'tags']);
    }

    public function test_owner_can_update_category_and_parameters_after_publication(): void
    {
        $category = ArtCategory::factory()->create();
        $project = Project::factory()->for($this->user)->inProgress()->create();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", [
                'art_category' => $category->slug,
            ])
            ->assertOk();

        $this->assertSame($category->id, $project->fresh()->art_category_id);
    }

    public function test_announced_budget_can_only_be_increased(): void
    {
        $project = Project::factory()->for($this->user)->announced()->create([
            'budget_goal' => 50000,
        ]);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", ['budget_goal' => 40000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['budget_goal']);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", ['budget_goal' => 60000])
            ->assertOk();

        $this->assertSame(60000.0, (float) $project->fresh()->budget_goal);
    }

    public function test_in_progress_project_can_update_additional_information(): void
    {
        $project = Project::factory()->for($this->user)->inProgress()->create();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", [
                'additional_info' => ['uk' => 'Оновлена інформація'],
            ])
            ->assertOk();

        $this->assertSame('Оновлена інформація', $project->fresh()->additional_info['uk']);
    }

    public function test_completed_project_can_only_update_additional_content(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Completed,
        ]);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", [
                'additional_info' => ['uk' => 'Після завершення'],
            ])
            ->assertOk();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", ['tags' => ['заборонено']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_sold_project_cannot_be_edited(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Sold,
        ]);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/my/projects/{$project->slug}", [
                'additional_info' => ['uk' => 'Спроба'],
            ])
            ->assertForbidden();
    }

    public function test_other_user_cannot_update_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($this->user)->announced()->create();

        $this->withHeaders($this->authHeaders($otherUser))
            ->patchJson("/api/v1/my/projects/{$project->slug}", [
                'additional_info' => ['uk' => 'Спроба'],
            ])
            ->assertForbidden();
    }

    public function test_full_update_still_works_for_drafts(): void
    {
        $project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Draft,
            'budget_goal' => 50000,
        ]);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", ['budget_goal' => 100000])
            ->assertOk();

        $this->assertSame(100000.0, (float) $project->fresh()->budget_goal);
    }

    public function test_full_update_is_forbidden_for_published_project(): void
    {
        $project = Project::factory()->for($this->user)->announced()->create();

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", ['budget_goal' => 100000])
            ->assertForbidden();
    }
}
