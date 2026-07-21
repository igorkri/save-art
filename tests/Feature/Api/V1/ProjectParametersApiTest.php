<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\ProjectParameter;
use App\Models\User;

class ProjectParametersApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // GET /v1/parameters
    // ==========================================

    public function test_can_get_parameters_for_category(): void
    {
        $category = ArtCategory::factory()->create();
        $listParameter = Parameter::factory()->for($category)->create([
            'name' => ['uk' => 'Жанр', 'en' => 'Genre'],
            'type' => ParameterType::List,
            'sort_order' => 0,
        ]);
        ParameterValue::factory()->for($listParameter)->create(['value' => ['uk' => 'Роман', 'en' => 'Novel']]);
        Parameter::factory()->for($category)->create([
            'name' => ['uk' => 'Матеріал', 'en' => 'Material'],
            'type' => ParameterType::Custom,
            'sort_order' => 1,
        ]);

        // Параметр іншої категорії не повинен потрапити у відповідь
        Parameter::factory()->create();

        $response = $this->apiGet("/api/v1/parameters?art_category={$category->slug}&language=uk");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Жанр')
            ->assertJsonPath('data.0.type', 'list')
            ->assertJsonPath('data.0.values.0.value', 'Роман')
            ->assertJsonPath('data.1.name', 'Матеріал')
            ->assertJsonPath('data.1.type', 'custom');
    }

    public function test_can_get_parameters_with_language(): void
    {
        $category = ArtCategory::factory()->create();
        Parameter::factory()->for($category)->create(['name' => ['uk' => 'Жанр', 'en' => 'Genre']]);

        $response = $this->apiGet("/api/v1/parameters?art_category={$category->slug}&language=en");

        $response->assertOk()->assertJsonPath('data.0.name', 'Genre');
    }

    public function test_parameters_return_all_languages_when_language_not_passed(): void
    {
        $category = ArtCategory::factory()->create();
        $parameter = Parameter::factory()->for($category)->create(['name' => ['uk' => 'Жанр', 'en' => 'Genre']]);
        ParameterValue::factory()->for($parameter)->create(['value' => ['uk' => 'Роман', 'en' => 'Novel']]);

        $response = $this->apiGet("/api/v1/parameters?art_category={$category->slug}");

        $response->assertOk()
            ->assertJsonPath('data.0.name.uk', 'Жанр')
            ->assertJsonPath('data.0.name.en', 'Genre')
            ->assertJsonPath('data.0.values.0.value.uk', 'Роман')
            ->assertJsonPath('data.0.values.0.value.en', 'Novel');
    }

    public function test_parameters_endpoint_returns_empty_list_for_unknown_category(): void
    {
        $response = $this->apiGet('/api/v1/parameters?art_category=does-not-exist');

        $response->assertOk()->assertExactJson(['data' => []]);
    }

    // ==========================================
    // Проєкт містить parameters у відповіді
    // ==========================================

    public function test_project_detail_includes_parameters(): void
    {
        $category = ArtCategory::factory()->create();
        $listParameter = Parameter::factory()->for($category)->create(['name' => ['uk' => 'Жанр'], 'type' => ParameterType::List]);
        $value = ParameterValue::factory()->for($listParameter)->create(['value' => ['uk' => 'Роман']]);
        $customParameter = Parameter::factory()->for($category)->create(['name' => ['uk' => 'Матеріал'], 'type' => ParameterType::Custom]);

        $project = Project::factory()->create([
            'art_category_id' => $category->getKey(),
            'status' => ProjectStatus::InProgress,
        ]);
        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $listParameter->getKey(),
            'parameter_value_id' => $value->getKey(),
        ]);
        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $customParameter->getKey(),
            'custom_value' => ['uk' => 'Полотно, олія'],
        ]);

        $response = $this->apiGet("/api/v1/projects/{$project->slug}?language=uk");

        $response->assertOk()->assertJsonCount(2, 'data.parameters');

        $parameters = collect($response->json('data.parameters'))->keyBy('parameter_id');
        $this->assertSame('Роман', $parameters[$listParameter->getKey()]['value']);
        $this->assertSame('list', $parameters[$listParameter->getKey()]['type']);
        $this->assertSame('Полотно, олія', $parameters[$customParameter->getKey()]['value']);
        $this->assertSame('custom', $parameters[$customParameter->getKey()]['type']);
    }

    public function test_project_detail_parameters_return_all_languages_when_language_not_passed(): void
    {
        $category = ArtCategory::factory()->create();
        $listParameter = Parameter::factory()->for($category)->create(['name' => ['uk' => 'Жанр', 'en' => 'Genre'], 'type' => ParameterType::List]);
        $value = ParameterValue::factory()->for($listParameter)->create(['value' => ['uk' => 'Роман', 'en' => 'Novel']]);
        $customParameter = Parameter::factory()->for($category)->create(['name' => ['uk' => 'Матеріал', 'en' => 'Material'], 'type' => ParameterType::Custom]);

        $project = Project::factory()->create([
            'art_category_id' => $category->getKey(),
            'status' => ProjectStatus::InProgress,
        ]);
        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $listParameter->getKey(),
            'parameter_value_id' => $value->getKey(),
        ]);
        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $customParameter->getKey(),
            'custom_value' => ['uk' => 'Полотно, олія', 'en' => 'Canvas, oil'],
        ]);

        $response = $this->apiGet("/api/v1/projects/{$project->slug}");

        $response->assertOk();
        $parameters = collect($response->json('data.parameters'))->keyBy('parameter_id');

        $this->assertSame('Жанр', $parameters[$listParameter->getKey()]['parameter']['uk']);
        $this->assertSame('Genre', $parameters[$listParameter->getKey()]['parameter']['en']);
        $this->assertSame('Роман', $parameters[$listParameter->getKey()]['value']['uk']);
        $this->assertSame('Novel', $parameters[$listParameter->getKey()]['value']['en']);
        $this->assertSame('Полотно, олія', $parameters[$customParameter->getKey()]['value']['uk']);
        $this->assertSame('Canvas, oil', $parameters[$customParameter->getKey()]['value']['en']);
    }

    // ==========================================
    // Фільтрація по parameter_value_id
    // ==========================================

    public function test_can_filter_projects_by_parameter_value(): void
    {
        $category = ArtCategory::factory()->create();
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

        $response = $this->apiGet("/api/v1/projects?parameter_value_id={$matchingValue->getKey()}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProject->id)
            ->assertJsonPath('filters_applied.parameter_value_id.0', (string) $matchingValue->getKey());
    }

    // ==========================================
    // Запис parameters через store()/update()
    // ==========================================

    public function test_can_create_draft_project_with_parameters(): void
    {
        $category = ArtCategory::factory()->create();
        $parameter = Parameter::factory()->for($category)->create(['type' => ParameterType::List]);
        $value = ParameterValue::factory()->for($parameter)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/projects', [
                'status' => 'draft',
                'art_category' => $category->slug,
                'parameters' => [
                    ['parameter_id' => $parameter->getKey(), 'parameter_value_id' => $value->getKey()],
                ],
            ]);

        $response->assertCreated();

        $projectId = $response->json('data.id');
        $this->assertDatabaseHas('project_parameters', [
            'project_id' => $projectId,
            'parameter_id' => $parameter->getKey(),
            'parameter_value_id' => $value->getKey(),
        ]);
        $this->assertCount(1, $response->json('data.parameters'));
    }

    public function test_can_update_project_parameters(): void
    {
        $category = ArtCategory::factory()->create();
        $parameter = Parameter::factory()->for($category)->create(['type' => ParameterType::List]);
        $valueOne = ParameterValue::factory()->for($parameter)->create();
        $valueTwo = ParameterValue::factory()->for($parameter)->create();

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'art_category_id' => $category->getKey(),
            'status' => ProjectStatus::Draft,
        ]);
        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $parameter->getKey(),
            'parameter_value_id' => $valueOne->getKey(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/my/projects/{$project->slug}", [
                'parameters' => [
                    ['parameter_id' => $parameter->getKey(), 'parameter_value_id' => $valueTwo->getKey()],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_parameters', [
            'project_id' => $project->getKey(),
            'parameter_id' => $parameter->getKey(),
            'parameter_value_id' => $valueTwo->getKey(),
        ]);
        $this->assertDatabaseCount('project_parameters', 1);
    }

    public function test_rejects_unknown_parameter_id(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/projects', [
                'status' => 'draft',
                'parameters' => [
                    ['parameter_id' => 999999],
                ],
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['parameters.0.parameter_id']);
    }
}
