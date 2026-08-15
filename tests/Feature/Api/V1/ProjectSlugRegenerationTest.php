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

    public function test_placeholder_slug_is_regenerated_when_submitted_for_moderation(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'new',
            'slug' => 'novii-proekt-21072026-1836-FwS5',
            'title' => ['uk' => 'Артбук "Сни України"', 'en' => 'Art book'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->slug}/submit");

        $response->assertOk();
        $newSlug = $response->json('data.slug');

        $this->assertNotSame('novii-proekt-21072026-1836-FwS5', $newSlug);
        $this->assertStringStartsWith('artbuk-sni-ukrayini', $newSlug);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'slug' => $newSlug,
            'status' => 'moderation',
        ]);
    }

    public function test_placeholder_slug_is_regenerated_when_saved_as_draft(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'new',
            'slug' => 'chernetka-21072026-1836-FwS5',
            'title' => ['uk' => 'Артбук "Сни України"', 'en' => 'Art book'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'status' => 'draft',
            ]);

        $response->assertOk();
        $newSlug = $response->json('data.slug');

        $this->assertNotSame('chernetka-21072026-1836-FwS5', $newSlug);
        $this->assertStringStartsWith('artbuk-sni-ukrayini', $newSlug);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'slug' => $newSlug,
            'status' => 'draft',
        ]);
    }

    public function test_placeholder_slug_unaffected_by_title_update_while_status_stays_new(): void
    {
        // Автозбереження (без явного status) не повинно перегенеровувати slug — це має
        // статись рівно один раз, саме при виході зі статусу "new" (submit/збереження чернетки).
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'new',
            'slug' => 'chernetka-21072026-1836-FwS5',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'title' => ['uk' => 'Артбук "Сни України"'],
            ]);

        $response->assertOk();
        $this->assertSame('chernetka-21072026-1836-FwS5', $response->json('data.slug'));
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

    public function test_new_project_gets_suffixed_slug_when_it_collides_with_a_soft_deleted_project(): void
    {
        // Unique-індекс slug у БД не знає про soft delete: якщо перевірка унікальності
        // враховує лише неvидалені записи, вставка падає з UniqueConstraintViolationException,
        // хоча "живих" проєктів з таким slug немає.
        $deleted = Project::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'tini-starogo-mista',
        ]);
        $deleted->delete();

        $slug = Project::generateSlugFromTitle('Тіні старого міста');

        $this->assertSame('tini-starogo-mista-1', $slug);
    }

    public function test_regenerated_slug_skips_collision_with_a_soft_deleted_project(): void
    {
        $deleted = Project::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'artbuk-sni-ukrayini',
        ]);
        $deleted->delete();

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'new',
            'slug' => 'chernetka-21072026-1836-FwS5',
            'title' => ['uk' => 'Артбук "Сни України"'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$project->slug}/submit");

        $response->assertOk();
        $this->assertSame('artbuk-sni-ukrayini-1', $response->json('data.slug'));
    }
}
