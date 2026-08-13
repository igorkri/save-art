<?php

namespace Tests\Unit\Models;

use App\Enums\ParameterType;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\ProjectParameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParameterModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_parameter_belongs_to_art_category(): void
    {
        $category = ArtCategory::factory()->create();
        $parameter = Parameter::factory()->for($category)->create();

        $this->assertInstanceOf(ArtCategory::class, $parameter->artCategory);
        $this->assertEquals($category->id, $parameter->artCategory->id);
    }

    public function test_parameter_has_many_values_ordered_by_sort_order(): void
    {
        $parameter = Parameter::factory()->create();
        $second = ParameterValue::factory()->for($parameter)->create(['sort_order' => 2]);
        $first = ParameterValue::factory()->for($parameter)->create(['sort_order' => 1]);

        $this->assertEquals([$first->id, $second->id], $parameter->values->pluck('id')->all());
    }

    public function test_parameter_label_returns_plain_name(): void
    {
        $parameter = Parameter::factory()->create([
            'name' => 'Матеріал',
        ]);
        $value = ParameterValue::factory()->for($parameter)->create(['value' => 'Полотно']);

        $this->assertSame('Матеріал', $parameter->getLabel('uk'));
        $this->assertSame('Матеріал', $parameter->getLabel('en'));
        $this->assertSame('Полотно', $value->getLabel('uk'));
        $this->assertSame('Полотно', $value->getLabel('en'));
    }

    public function test_project_can_have_a_list_type_parameter_value(): void
    {
        $project = Project::factory()->create();
        $parameter = Parameter::factory()->create(['type' => ParameterType::List]);
        $value = ParameterValue::factory()->for($parameter)->create();

        $link = ProjectParameter::create([
            'project_id' => $project->id,
            'parameter_id' => $parameter->id,
            'parameter_value_id' => $value->id,
        ]);

        $this->assertCount(1, $project->refresh()->projectParameters);
        $this->assertEquals($value->id, $link->parameterValue->id);
        $this->assertNull($link->custom_value);
    }

    public function test_project_can_have_a_custom_type_parameter_value(): void
    {
        $project = Project::factory()->create();
        $parameter = Parameter::factory()->create(['type' => ParameterType::Custom]);

        $link = ProjectParameter::create([
            'project_id' => $project->id,
            'parameter_id' => $parameter->id,
            'custom_value' => ['uk' => '50 см', 'en' => '50 cm'],
        ]);

        $this->assertNull($link->parameter_value_id);
        $this->assertSame('50 см', $link->custom_value);
    }
}
