<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\ArtCatalog;
use App\Models\Project;
use App\Models\ProjectLike;
use App\Models\User;

class LikesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_like_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->id}/like");

        $response->assertOk();

        $this->assertDatabaseHas('project_likes', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_cannot_like_project_twice(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        ProjectLike::create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->id}/like");

        $response->assertUnprocessable();
    }

    public function test_can_unlike_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        ProjectLike::create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/projects/{$project->id}/like");

        $response->assertOk();

        $this->assertDatabaseMissing('project_likes', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_cannot_unlike_not_liked_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/projects/{$project->id}/like");

        $response->assertUnprocessable();
    }

    public function test_unauthenticated_user_cannot_like(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson("/api/v1/projects/{$project->id}/like");

        $response->assertUnauthorized();
    }

    public function test_can_like_and_unlike_catalog(): void
    {
        $catalog = ArtCatalog::factory()->create(['likes_count' => 0]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/catalogs/{$catalog->id}/like")
            ->assertOk()
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('art_catalog_likes', [
            'user_id' => $this->user->id,
            'art_catalog_id' => $catalog->id,
        ]);

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/catalogs/{$catalog->id}/like")
            ->assertOk()
            ->assertJsonPath('likes_count', 0);

        $this->assertDatabaseMissing('art_catalog_likes', [
            'user_id' => $this->user->id,
            'art_catalog_id' => $catalog->id,
        ]);
    }

    public function test_cannot_unlike_not_liked_catalog(): void
    {
        $catalog = ArtCatalog::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/catalogs/{$catalog->id}/like");

        $response->assertUnprocessable();
    }
}
