<?php

namespace Tests\Unit\Support;

use App\Enums\ParameterType;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\ProjectParameter;
use App\Support\ProjectCategoryParameterValues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCategoryParameterValuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_rows_for_all_category_parameters(): void
    {
        $category = ArtCategory::factory()->create();
        Parameter::factory()->for($category)->create(['name' => 'Матеріал', 'sort_order' => 1]);
        Parameter::factory()->for($category)->create(['name' => 'Розмір', 'sort_order' => 2]);

        $rows = ProjectCategoryParameterValues::rowsForCategoryId((int) $category->getKey());

        $this->assertCount(2, $rows);
        $this->assertSame('Матеріал', $rows[0]['parameter_label']);
        $this->assertSame('Розмір', $rows[1]['parameter_label']);
    }

    public function test_rows_include_existing_project_values(): void
    {
        $category = ArtCategory::factory()->create();
        $listParameter = Parameter::factory()->for($category)->create(['type' => ParameterType::List]);
        $value = ParameterValue::factory()->for($listParameter)->create();
        $customParameter = Parameter::factory()->for($category)->create(['type' => ParameterType::Custom]);

        $project = Project::factory()->create(['art_category_id' => $category->getKey()]);
        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $listParameter->getKey(),
            'parameter_value_id' => $value->getKey(),
        ]);
        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $customParameter->getKey(),
            'custom_value' => ['uk' => '50 см', 'en' => '50 cm'],
        ]);

        $rows = ProjectCategoryParameterValues::rowsForProject($project);
        $byParameterId = collect($rows)->keyBy('parameter_id');

        $this->assertSame($value->getKey(), $byParameterId[$listParameter->getKey()]['parameter_value_id']);
        $this->assertSame('50 см', $byParameterId[$customParameter->getKey()]['custom_value']);
    }

    public function test_sync_creates_updates_and_prunes_rows(): void
    {
        $category = ArtCategory::factory()->create();
        $listParameter = Parameter::factory()->for($category)->create(['type' => ParameterType::List]);
        $valueOne = ParameterValue::factory()->for($listParameter)->create();
        $valueTwo = ParameterValue::factory()->for($listParameter)->create();
        $customParameter = Parameter::factory()->for($category)->create(['type' => ParameterType::Custom]);

        $project = Project::factory()->create(['art_category_id' => $category->getKey()]);

        ProjectCategoryParameterValues::syncForProject($project, [
            ['parameter_id' => $listParameter->getKey(), 'parameter_value_id' => $valueOne->getKey()],
            ['parameter_id' => $customParameter->getKey(), 'custom_value' => 'Значення'],
        ]);

        $this->assertDatabaseHas('project_parameters', [
            'project_id' => $project->getKey(),
            'parameter_id' => $listParameter->getKey(),
            'parameter_value_id' => $valueOne->getKey(),
        ]);
        $this->assertCount(2, $project->projectParameters()->get());

        // Update value + remove empty custom value.
        ProjectCategoryParameterValues::syncForProject($project, [
            ['parameter_id' => $listParameter->getKey(), 'parameter_value_id' => $valueTwo->getKey()],
            ['parameter_id' => $customParameter->getKey(), 'custom_value' => ''],
        ]);

        $project->refresh();
        $remaining = $project->projectParameters()->get();
        $this->assertCount(1, $remaining);
        $this->assertSame($valueTwo->getKey(), $remaining->first()->parameter_value_id);
    }

    public function test_sync_with_empty_rows_deletes_all_values(): void
    {
        $category = ArtCategory::factory()->create();
        $parameter = Parameter::factory()->for($category)->create(['type' => ParameterType::Custom]);
        $project = Project::factory()->create(['art_category_id' => $category->getKey()]);

        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $parameter->getKey(),
            'custom_value' => ['uk' => 'Значення', 'en' => 'Value'],
        ]);

        ProjectCategoryParameterValues::syncForProject($project, []);

        $this->assertCount(0, $project->projectParameters()->get());
    }

    public function test_sync_with_null_rows_is_noop(): void
    {
        $category = ArtCategory::factory()->create();
        $parameter = Parameter::factory()->for($category)->create(['type' => ParameterType::Custom]);
        $project = Project::factory()->create(['art_category_id' => $category->getKey()]);

        ProjectParameter::create([
            'project_id' => $project->getKey(),
            'parameter_id' => $parameter->getKey(),
            'custom_value' => ['uk' => 'Значення', 'en' => 'Value'],
        ]);

        ProjectCategoryParameterValues::syncForProject($project, null);

        $this->assertCount(1, $project->projectParameters()->get());
    }
}
