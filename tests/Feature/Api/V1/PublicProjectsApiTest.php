<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;

class PublicProjectsApiTest extends ApiTestCase
{
    // ==========================================
    // Список проектів
    // ==========================================

    public function test_can_get_projects_list(): void
    {
        Project::factory()->count(3)->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'title',
                        'short_description',
                        'status',
                        'art_category',
                        'budget_goal',
                        'budget_collected',
                        'author',
                    ],
                ],
                'meta',
                'links',
                'filters' => [
                    'categories',
                    'statuses',
                    'budget_range',
                    'currencies',
                    'sort_options',
                    'total_projects',
                ],
                'filters_applied',
            ]);
    }

    public function test_projects_list_includes_filters_with_language(): void
    {
        Project::factory()->count(2)->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects?language=uk');

        $response->assertOk()
            ->assertJsonPath('language', 'uk');

        // Перевіряємо що назви фільтрів - це строки, а не об'єкти
        $categories = $response->json('filters.categories');
        $this->assertIsString($categories[0]['name']);
    }

    public function test_projects_list_filters_without_language_returns_objects(): void
    {
        Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects');

        $response->assertOk();

        // Перевіряємо що назви фільтрів - це об'єкти з uk і en
        $categories = $response->json('filters.categories');
        $this->assertIsArray($categories[0]['name']);
        $this->assertArrayHasKey('uk', $categories[0]['name']);
        $this->assertArrayHasKey('en', $categories[0]['name']);
    }

    public function test_can_filter_projects_by_status(): void
    {
        Project::factory()->create(['status' => ProjectStatus::InProgress]);
        Project::factory()->create(['status' => ProjectStatus::Completed]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects?status=in_progress');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_projects_by_category(): void
    {
        Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'art_category' => ArtCategory::Music,
        ]);
        Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'art_category' => ArtCategory::FineArt,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects?art_category=music');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_search_projects(): void
    {
        Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'title' => ['uk' => 'Унікальний проект', 'en' => 'Unique project'],
        ]);
        Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'title' => ['uk' => 'Інший проект', 'en' => 'Other project'],
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects?search=Унікальний');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_projects_are_paginated(): void
    {
        Project::factory()->count(20)->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects?per_page=5');

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(20, $response->json('meta.total'));
    }

    // ==========================================
    // Деталі проекту
    // ==========================================

    public function test_can_get_project_details(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'slug',
                    'title',
                    'short_description',
                    'status',
                    'art_category',
                    'budget_goal',
                    'budget_collected',
                    'progress_percentage',
                    'author',
                ],
            ]);
    }

    public function test_cannot_get_draft_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}");

        $response->assertNotFound();
    }

    public function test_returns_404_for_nonexistent_project(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects/nonexistent-slug');

        $response->assertNotFound();
    }

    // ==========================================
    // Донори проекту
    // ==========================================

    public function test_can_get_project_donors(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        Donation::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}/donors");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'amount',
                        'created_at',
                    ],
                ],
            ]);
    }

    // ==========================================
    // Категорії та регіони
    // ==========================================

    public function test_can_get_art_categories(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'value',
                        'label',
                        'subcategories',
                    ],
                ],
            ]);
    }

    public function test_can_get_regions(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/regions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'value',
                        'label',
                    ],
                ],
            ]);
    }

    // ==========================================
    // API Key захист
    // ==========================================

    public function test_cannot_access_without_api_key(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertForbidden()
            ->assertJson(['message' => 'Invalid or missing API key.']);
    }

    public function test_cannot_access_with_invalid_api_key(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => 'invalid-key'])
            ->getJson('/api/v1/projects');

        $response->assertForbidden();
    }
}
