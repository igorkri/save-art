<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
use App\Models\ArtCategory as ArtCategoryModel;
use App\Models\Donation;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\ProjectParameter;
use App\Models\User;

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

    public function test_status_filter_counts_reflect_other_applied_filters(): void
    {
        Project::factory()->count(2)->create([
            'status' => ProjectStatus::InProgress,
            'currency' => 'EUR',
        ]);
        Project::factory()->create([
            'status' => ProjectStatus::Completed,
            'currency' => 'EUR',
        ]);
        Project::factory()->count(2)->create([
            'status' => ProjectStatus::InProgress,
            'currency' => 'USD',
        ]);

        // Без фільтра по валюті - лічильник статусу враховує всі проєкти
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects');
        $statuses = collect($response->json('filters.statuses'))->keyBy('slug');
        $this->assertSame(4, $statuses['in_progress']['projects_count']);
        $this->assertSame(1, $statuses['completed']['projects_count']);

        // З фільтром по валюті EUR - лічильник статусу перераховується під цей фільтр
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects?currency=EUR');
        $statuses = collect($response->json('filters.statuses'))->keyBy('slug');
        $this->assertSame(2, $statuses['in_progress']['projects_count']);
        $this->assertSame(1, $statuses['completed']['projects_count']);
    }

    public function test_filters_parameters_empty_without_selected_category(): void
    {
        Project::factory()->create(['status' => ProjectStatus::InProgress]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects');

        $response->assertOk()->assertJsonPath('filters.parameters', []);
    }

    public function test_filters_parameters_reflect_selected_subcategory(): void
    {
        $root = ArtCategoryModel::factory()->create(['parent_id' => null]);
        $child = ArtCategoryModel::factory()->create(['parent_id' => $root->getKey()]);

        $listParameter = Parameter::factory()->for($child)->create([
            'name' => ['uk' => 'Формат друку', 'en' => 'Print format'],
            'type' => ParameterType::List,
            'sort_order' => 0,
        ]);
        $matchingValue = ParameterValue::factory()->for($listParameter)->create(['value' => ['uk' => 'Глянцевий', 'en' => 'Glossy']]);
        $otherValue = ParameterValue::factory()->for($listParameter)->create(['value' => ['uk' => 'Матовий', 'en' => 'Matte']]);

        // Кастомний параметр (без фіксованих значень) не повинен потрапляти у фільтр
        Parameter::factory()->for($child)->create(['type' => ParameterType::Custom]);

        $matchingProject = Project::factory()->create([
            'art_category_id' => $child->getKey(),
            'status' => ProjectStatus::InProgress,
        ]);
        ProjectParameter::create([
            'project_id' => $matchingProject->getKey(),
            'parameter_id' => $listParameter->getKey(),
            'parameter_value_id' => $matchingValue->getKey(),
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects?art_category={$root->slug}&art_subcategory={$child->slug}&language=uk");

        $response->assertOk()->assertJsonCount(1, 'filters.parameters');

        $parameter = $response->json('filters.parameters.0');
        $this->assertSame('Формат друку', $parameter['name']);

        $values = collect($parameter['values'])->keyBy('id');
        $this->assertSame(1, $values[$matchingValue->getKey()]['projects_count']);
        $this->assertSame(0, $values[$otherValue->getKey()]['projects_count']);
    }

    public function test_can_filter_projects_by_parameter_value_from_filters_list(): void
    {
        $category = ArtCategoryModel::factory()->create();
        $parameter = Parameter::factory()->for($category)->create(['type' => ParameterType::List]);
        $matchingValue = ParameterValue::factory()->for($parameter)->create();
        $otherValue = ParameterValue::factory()->for($parameter)->create();

        $matchingProject = Project::factory()->create([
            'art_category_id' => $category->getKey(),
            'status' => ProjectStatus::InProgress,
        ]);
        ProjectParameter::create([
            'project_id' => $matchingProject->getKey(),
            'parameter_id' => $parameter->getKey(),
            'parameter_value_id' => $matchingValue->getKey(),
        ]);

        $otherProject = Project::factory()->create([
            'art_category_id' => $category->getKey(),
            'status' => ProjectStatus::InProgress,
        ]);
        ProjectParameter::create([
            'project_id' => $otherProject->getKey(),
            'parameter_id' => $parameter->getKey(),
            'parameter_value_id' => $otherValue->getKey(),
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects?parameter_value_id={$matchingValue->getKey()}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProject->id);
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

    public function test_owner_can_get_own_draft_project_details(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->getJson("/api/v1/projects/{$project->slug}");

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.can_edit', true);
    }

    public function test_other_authenticated_user_cannot_get_draft_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders($this->authHeaders($otherUser))
            ->getJson("/api/v1/projects/{$project->slug}");

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
                        'donated_at',
                        'user_slug',
                    ],
                ],
            ]);
    }

    public function test_donor_user_slug_hidden_for_anonymous_donation(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $donor = User::factory()->create(['slug' => 'ivan-donor']);
        Donation::factory()->fromUser($donor)->create([
            'project_id' => $project->id,
            'status' => 'paid',
        ]);
        Donation::factory()->anonymous()->create([
            'project_id' => $project->id,
            'status' => 'paid',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}/donors");

        $response->assertOk();

        $donors = collect($response->json('data'));
        $this->assertTrue($donors->contains('user_slug', 'ivan-donor'));
        $this->assertTrue($donors->where('is_anonymous', true)->every(fn ($d) => $d['user_slug'] === null));
    }

    public function test_donor_hidden_from_list_when_donations_marked_not_public(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $visibleDonor = User::factory()->create(['slug' => 'visible-donor']);
        Donation::factory()->fromUser($visibleDonor)->create([
            'project_id' => $project->id,
            'status' => 'paid',
            'is_public' => true,
        ]);

        $hiddenDonor = User::factory()->create(['slug' => 'hidden-donor']);
        Donation::factory()->fromUser($hiddenDonor)->create([
            'project_id' => $project->id,
            'status' => 'paid',
            'is_public' => false,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}/donors");

        $response->assertOk();

        $donors = collect($response->json('data'));
        $this->assertTrue($donors->contains('user_slug', 'visible-donor'));
        $this->assertFalse($donors->contains('user_slug', 'hidden-donor'));
        $this->assertCount(1, $donors);
    }

    public function test_donors_list_shows_pseudonym_instead_of_real_name_for_anonymous_donor(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $donor = User::factory()->create(['full_name' => ['uk' => 'Real Author Name', 'en' => 'Real Author Name'], 'slug' => 'anon-donor']);
        Donation::factory()->fromUser($donor)->create([
            'project_id' => $project->id,
            'status' => 'paid',
            'is_anonymous' => true,
            'donor_name' => 'CoolCat',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}/donors");

        $response->assertOk();

        $donors = collect($response->json('data'));
        $this->assertTrue($donors->contains('name', 'CoolCat'));
        $this->assertFalse($donors->contains('name', 'Real Author Name'));
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
