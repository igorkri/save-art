<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

use App\Enums\ParameterType;
use App\Enums\ProfileType;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\ProjectParameter;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

class AuthorsApiTest extends ApiTestCase
{
    public function test_can_get_artists_list(): void
    {
        $artist = User::factory()->create(['profile_type' => ProfileType::Artist]);
        Project::factory()->create([
            'user_id' => $artist->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/artists');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'slug']],
                'filters' => ['categories', 'sort_options'],
            ]);
    }

    public function test_can_get_organizations_list(): void
    {
        $organization = User::factory()->create(['profile_type' => ProfileType::Organization]);
        Project::factory()->create([
            'user_id' => $organization->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);
        // Митець не повинен потрапляти у список організацій.
        $artist = User::factory()->create(['profile_type' => ProfileType::Artist]);
        Project::factory()->create([
            'user_id' => $artist->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/organizations');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $organization->slug);
    }

    public function test_art_category_filter_excludes_authors_without_matching_projects(): void
    {
        $root = ArtCategory::whereNull('parent_id')->firstOrFail();
        $otherRoot = ArtCategory::whereNull('parent_id')->where('id', '!=', $root->id)->firstOrFail();

        $matching = User::factory()->create(['profile_type' => ProfileType::Artist]);
        Project::factory()->create([
            'user_id' => $matching->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
            'art_category_id' => $root->id,
        ]);

        $nonMatching = User::factory()->create(['profile_type' => ProfileType::Artist]);
        Project::factory()->create([
            'user_id' => $nonMatching->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
            'art_category_id' => $otherRoot->id,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/artists?art_category={$root->slug}");

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $matching->slug);
    }

    public function test_category_filters_report_authors_count(): void
    {
        $root = ArtCategory::whereNull('parent_id')->firstOrFail();

        $artist = User::factory()->create(['profile_type' => ProfileType::Artist]);
        Project::factory()->create([
            'user_id' => $artist->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
            'art_category_id' => $root->id,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson('/api/v1/art-ua-info/artists');

        $response->assertOk();

        $categories = collect($response->json('filters.categories'));
        $rootFilter = $categories->firstWhere('slug', $root->slug);

        $this->assertNotNull($rootFilter);
        $this->assertGreaterThanOrEqual(1, $rootFilter['authors_count']);
    }

    public function test_filters_report_parameters_for_selected_category_with_authors_count(): void
    {
        $root = ArtCategory::whereNull('parent_id')->firstOrFail();
        $parameter = Parameter::factory()->create(['art_category_id' => $root->id, 'type' => ParameterType::List]);
        $value = ParameterValue::factory()->create(['parameter_id' => $parameter->id]);

        $artist = User::factory()->create(['profile_type' => ProfileType::Artist]);
        $project = Project::factory()->create([
            'user_id' => $artist->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
            'art_category_id' => $root->id,
        ]);
        ProjectParameter::factory()->create([
            'project_id' => $project->id,
            'parameter_id' => $parameter->id,
            'parameter_value_id' => $value->id,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/artists?art_category={$root->slug}");

        $response->assertOk();

        $parameters = collect($response->json('filters.parameters'));
        $parameterFilter = $parameters->firstWhere('id', $parameter->id);
        $this->assertNotNull($parameterFilter);
        $valueFilter = collect($parameterFilter['values'])->firstWhere('id', $value->id);
        $this->assertNotNull($valueFilter);
        $this->assertSame(1, $valueFilter['authors_count']);
    }

    public function test_parameter_value_filter_narrows_artists_list(): void
    {
        $root = ArtCategory::whereNull('parent_id')->firstOrFail();
        $parameter = Parameter::factory()->create(['art_category_id' => $root->id, 'type' => ParameterType::List]);
        $value = ParameterValue::factory()->create(['parameter_id' => $parameter->id]);

        $matching = User::factory()->create(['profile_type' => ProfileType::Artist]);
        $matchingProject = Project::factory()->create([
            'user_id' => $matching->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
            'art_category_id' => $root->id,
        ]);
        ProjectParameter::factory()->create([
            'project_id' => $matchingProject->id,
            'parameter_id' => $parameter->id,
            'parameter_value_id' => $value->id,
        ]);

        $nonMatching = User::factory()->create(['profile_type' => ProfileType::Artist]);
        Project::factory()->create([
            'user_id' => $nonMatching->id,
            'source' => ProjectSource::ArtUaInfo,
            'status' => ProjectStatus::Announced,
            'art_category_id' => $root->id,
        ]);

        $response = $this->withHeaders($this->apiHeaders())
            ->getJson("/api/v1/art-ua-info/artists?parameter_value_id={$value->id}");

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $matching->slug);
    }
}
