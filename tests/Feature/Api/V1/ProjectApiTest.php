<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Models\Project;
use App\Models\User;

class ProjectApiTest extends ApiTestCase
{
    // ==========================================
    // Публічні endpoints
    // ==========================================

    public function test_can_get_public_projects_list(): void
    {
        // Створюємо оголошені проєкти
        Project::factory()->count(3)->announced()->create();

        // Створюємо чернетку (не повинна бути в списку)
        Project::factory()->create(['status' => ProjectStatus::Draft]);

        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'title',
                        'status',
                        'author',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_filter_projects_by_category(): void
    {
        Project::factory()->announced()->create(['art_category' => ArtCategory::FineArt]);
        Project::factory()->announced()->create(['art_category' => ArtCategory::Music]);

        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/projects?art_category=fine_art');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_single_project(): void
    {
        $project = Project::factory()->announced()->create();

        $response = $this->withHeaders($this->apiHeaders())->getJson("/api/v1/projects/{$project->slug}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'slug',
                    'code',
                    'status',
                    'title',
                    'art_category',
                    'budget_goal',
                    'author',
                    'stages',
                    'bonuses',
                ],
            ])
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_cannot_get_draft_project(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Draft]);

        $response = $this->withHeaders($this->apiHeaders())->getJson("/api/v1/projects/{$project->slug}");

        $response->assertNotFound();
    }

    // ==========================================
    // Мої проєкти (авторизовані)
    // ==========================================

    public function test_unauthorized_cannot_access_my_projects(): void
    {
        $response = $this->withHeaders($this->apiHeaders())->getJson('/api/v1/my/projects');

        $response->assertUnauthorized();
    }

    public function test_can_get_my_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(2)->create(['user_id' => $user->id]);

        // Проєкт іншого користувача
        Project::factory()->create();

        $response = $this->withHeaders($this->authHeaders($user))->getJson('/api/v1/my/projects');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_project(): void
    {
        $user = User::factory()->create();

        $data = [
            'user_type' => UserType::Personal->value,
            'title' => [
                'uk' => 'Тестовий проєкт',
                'en' => 'Test project',
            ],
            'art_category' => ArtCategory::FineArt->value,
            'currency' => Currency::UAH->value,
            'budget_goal' => 10000,
        ];

        $response = $this->withHeaders($this->authHeaders($user))->postJson('/api/v1/my/projects', $data);

        $response->assertCreated()
            ->assertJsonPath('data.title.uk', 'Тестовий проєкт')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft->value,
        ]);
    }

    public function test_can_update_my_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->putJson("/api/v1/my/projects/{$project->id}", [
            'title' => ['uk' => 'Оновлена назва'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title.uk', 'Оновлена назва');
    }

    public function test_cannot_update_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->putJson("/api/v1/my/projects/{$project->id}", [
            'title' => ['uk' => 'Оновлена назва'],
        ]);

        $response->assertForbidden();
    }

    public function test_can_submit_project_for_moderation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
            'title' => ['uk' => 'Назва'],
            'art_category' => ArtCategory::FineArt,
            'budget_goal' => 10000,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->postJson("/api/v1/my/projects/{$project->id}/submit");

        $response->assertOk();

        $project->refresh();
        $this->assertEquals(ProjectStatus::Moderation, $project->status);
        $this->assertEquals(ModerationStatus::Pending, $project->status_moderation);
    }

    public function test_can_delete_draft_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->deleteJson("/api/v1/my/projects/{$project->id}");

        $response->assertOk();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_cannot_delete_announced_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->announced()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($user))->deleteJson("/api/v1/my/projects/{$project->id}");

        $response->assertUnprocessable();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    // ==========================================
    // Лайки
    // ==========================================

    public function test_can_like_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->announced()->create(['likes_count' => 0]);

        $response = $this->withHeaders($this->authHeaders($user))->postJson("/api/v1/projects/{$project->id}/like");

        $response->assertOk()
            ->assertJsonPath('is_liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('project_likes', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_can_unlike_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->announced()->create(['likes_count' => 1]);

        // Спочатку лайкаємо
        $project->likes()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($user))->deleteJson("/api/v1/projects/{$project->id}/like");

        $response->assertOk()
            ->assertJsonPath('is_liked', false)
            ->assertJsonPath('likes_count', 0);

        $this->assertDatabaseMissing('project_likes', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_cannot_like_twice(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->announced()->create(['likes_count' => 1]);
        $project->likes()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeaders($user))->postJson("/api/v1/projects/{$project->id}/like");

        $response->assertUnprocessable();
    }
}
