<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

use App\Enums\ModerationStatus;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

class ProjectControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_final_save_is_completed_and_approved(): void
    {
        $data = [
            'status' => 'moderation',
            'user_type' => 'personal',
            'title' => ['uk' => 'Мій проект', 'en' => 'My project'],
            'art_category' => 'music',
            'final_result' => [
                ['type' => 'link', 'url' => 'https://example.com/work'],
            ],
            'content_blocks' => [
                ['type' => 'paragraph', 'paragraph_text' => ['uk' => 'Текст', 'en' => 'Text']],
            ],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/art-ua-info/projects', $data);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'source' => ProjectSource::ArtUaInfo->value,
            'status' => ProjectStatus::Completed->value,
            'status_moderation' => ModerationStatus::Approved->value,
        ]);
    }

    public function test_draft_save_is_not_completed(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/art-ua-info/projects', [
                'status' => 'draft',
                'title' => ['uk' => 'Чернетка', 'en' => 'Draft'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'status' => ProjectStatus::Draft->value,
            'status_moderation' => ModerationStatus::Pending->value,
        ]);
    }

    public function test_new_save_is_not_completed(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/art-ua-info/projects', [
                'title' => ['uk' => 'Новий', 'en' => 'New'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'new');

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'status' => ProjectStatus::New->value,
            'status_moderation' => ModerationStatus::Pending->value,
        ]);
    }

    public function test_owner_can_update_completed_project_without_losing_status(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Completed,
            'status_moderation' => ModerationStatus::Approved,
            'title' => ['uk' => 'Стара назва', 'en' => 'Old title'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/art-ua-info/projects/{$project->slug}", [
                'user_type' => 'personal',
                'title' => ['uk' => 'Нова назва', 'en' => 'New title'],
                'art_category' => 'music',
                'final_result' => [
                    ['type' => 'link', 'url' => 'https://example.com/work'],
                ],
                'content_blocks' => [
                    ['type' => 'paragraph', 'paragraph_text' => ['uk' => 'Текст', 'en' => 'Text']],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title.uk', 'Нова назва')
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Completed->value,
            'status_moderation' => ModerationStatus::Approved->value,
        ]);
    }

    public function test_owner_can_revert_completed_project_to_draft(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Completed,
            'status_moderation' => ModerationStatus::Approved,
            'title' => ['uk' => 'Стара назва', 'en' => 'Old title'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/art-ua-info/projects/{$project->slug}", [
                'status' => 'draft',
                'user_type' => 'personal',
                'title' => ['uk' => 'Стара назва', 'en' => 'Old title'],
                'art_category' => 'music',
                'final_result' => [
                    ['type' => 'link', 'url' => 'https://example.com/work'],
                ],
                'content_blocks' => [
                    ['type' => 'paragraph', 'paragraph_text' => ['uk' => 'Текст', 'en' => 'Text']],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Draft->value,
        ]);
    }

    public function test_owner_can_republish_draft_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Draft,
            'status_moderation' => ModerationStatus::Pending,
            'title' => ['uk' => 'Чернетка', 'en' => 'Draft'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/art-ua-info/projects/{$project->slug}", [
                'status' => 'moderation',
                'user_type' => 'personal',
                'title' => ['uk' => 'Чернетка', 'en' => 'Draft'],
                'art_category' => 'music',
                'final_result' => [
                    ['type' => 'link', 'url' => 'https://example.com/work'],
                ],
                'content_blocks' => [
                    ['type' => 'paragraph', 'paragraph_text' => ['uk' => 'Текст', 'en' => 'Text']],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Completed->value,
            'status_moderation' => ModerationStatus::Approved->value,
        ]);
    }

    public function test_invalid_status_value_is_rejected_on_update(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Completed,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/art-ua-info/projects/{$project->slug}", [
                'status' => 'completed',
                'user_type' => 'personal',
                'title' => ['uk' => 'Назва', 'en' => 'Title'],
                'art_category' => 'music',
                'content_blocks' => [
                    ['type' => 'paragraph', 'paragraph_text' => ['uk' => 'Текст', 'en' => 'Text']],
                ],
            ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_update_someone_elses_project(): void
    {
        $project = Project::factory()->create([
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Completed,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/art-ua-info/projects/{$project->slug}", [
                'user_type' => 'personal',
                'title' => ['uk' => 'Назва', 'en' => 'Title'],
                'art_category' => 'music',
                'content_blocks' => [
                    ['type' => 'paragraph', 'paragraph_text' => ['uk' => 'Текст', 'en' => 'Text']],
                ],
            ]);

        $response->assertForbidden();
    }
}
