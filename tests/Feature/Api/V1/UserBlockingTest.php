<?php

namespace Tests\Feature\Api\V1;

use App\Enums\NotificationType;
use App\Models\Project;
use App\Models\User;

class UserBlockingTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_blocking_user_sends_notification(): void
    {
        $this->user->update(['is_blocked' => true, 'blocked_until' => now()->addDays(30)]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->user->id,
            'type' => NotificationType::Blocked->value,
        ]);
    }

    public function test_unblocking_user_sends_notification(): void
    {
        $this->user->update(['is_blocked' => true]);
        $this->user->update(['is_blocked' => false, 'blocked_until' => null]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->user->id,
            'type' => NotificationType::Unblocked->value,
        ]);
    }

    public function test_blocked_user_cannot_create_project(): void
    {
        $this->user->update(['is_blocked' => true]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/projects', [
                'user_type' => 'personal',
                'title' => ['uk' => 'Мій проект', 'en' => 'My project'],
                'short_description' => ['uk' => 'Короткий опис', 'en' => 'Short description'],
                'art_category' => 'music',
                'budget_goal' => 10000,
                'currency' => 'UAH',
                'estimated_days' => 30,
            ]);

        $response->assertForbidden();
    }

    public function test_blocked_user_cannot_update_project(): void
    {
        $this->user->update(['is_blocked' => true]);
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Нова назва', 'en' => 'New title'],
            ])
            ->assertForbidden();
    }

    public function test_blocked_user_cannot_delete_project(): void
    {
        $this->user->update(['is_blocked' => true]);
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$project->slug}")
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_blocked_user_can_still_view_projects(): void
    {
        $this->user->update(['is_blocked' => true]);
        Project::factory()->create(['user_id' => $this->user->id]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/projects')
            ->assertOk();
    }

    public function test_blocked_user_cannot_donate(): void
    {
        $this->user->update(['is_blocked' => true]);
        $project = Project::factory()->announced()->create();

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'is_anonymous' => false,
            ])
            ->assertForbidden();
    }

    public function test_cannot_donate_to_project_of_blocked_owner(): void
    {
        $owner = User::factory()->create(['is_blocked' => true]);
        $project = Project::factory()->announced()->create(['user_id' => $owner->id]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'is_anonymous' => false,
            ])
            ->assertUnprocessable();
    }
}
