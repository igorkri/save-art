<?php

namespace Tests\Feature\Api\V1;

use App\Models\Project;
use App\Models\User;

class ProjectSlugRegenerationTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_placeholder_slug_is_regenerated_from_title_on_update(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'new',
            'slug' => 'novii-proekt-21072026-1836-FwS5',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Артбук "Сни України"'],
            ]);

        $response->assertOk();
        $newSlug = $response->json('data.slug');

        $this->assertNotSame('novii-proekt-21072026-1836-FwS5', $newSlug);
        $this->assertStringStartsWith('artbuk-sni-ukrayini', $newSlug);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'slug' => $newSlug,
        ]);
    }

    public function test_real_slug_is_not_regenerated_again_on_subsequent_update(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'slug' => 'artbuk-sni-ukraini-abc123',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Нова назва проєкту'],
            ]);

        $response->assertOk();
        $this->assertSame('artbuk-sni-ukraini-abc123', $response->json('data.slug'));
    }

    public function test_placeholder_slug_unaffected_when_title_not_changed(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'new',
            'slug' => 'chernetka-21072026-1836-FwS5',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'budget_goal' => 5000,
            ]);

        $response->assertOk();
        $this->assertSame('chernetka-21072026-1836-FwS5', $response->json('data.slug'));
    }
}
