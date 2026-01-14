<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Report;

class ReportsApiTest extends ApiTestCase
{
    // ==========================================
    // Список звітів
    // ==========================================

    public function test_can_get_reports_list(): void
    {
        Report::factory()->count(5)->create([
            'status' => 'published',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/reports');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'reports' => [
                        '*' => [
                            'id',
                            'title',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    }

    public function test_unpublished_reports_not_shown(): void
    {
        Report::factory()->create(['status' => 'draft']);
        Report::factory()->create(['status' => 'published']);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/reports');

        $response->assertOk()
            ->assertJsonCount(1, 'data.reports');
    }

    public function test_reports_are_paginated(): void
    {
        Report::factory()->count(20)->create([
            'status' => 'published',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/reports?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data.reports');
    }

    // ==========================================
    // Деталі звіту
    // ==========================================

    public function test_can_get_report_details(): void
    {
        $report = Report::factory()->create([
            'status' => 'published',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/reports/{$report->id}");

        $response->assertOk()
            ->assertJsonPath('data.report.id', $report->id);
    }

    public function test_cannot_get_unpublished_report(): void
    {
        $report = Report::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/reports/{$report->id}");

        $response->assertNotFound();
    }

    public function test_returns_404_for_nonexistent_report(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/reports/99999');

        $response->assertNotFound();
    }

    // ==========================================
    // Звіти по проекту
    // ==========================================

    public function test_can_get_reports_by_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        Report::factory()->count(3)->create([
            'project_id' => $project->id,
            'status' => 'published',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}/reports");

        $response->assertOk()
            ->assertJsonCount(3, 'data.reports');
    }

    public function test_project_reports_only_shows_published(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        Report::factory()->create([
            'project_id' => $project->id,
            'status' => 'published',
        ]);
        Report::factory()->create([
            'project_id' => $project->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson("/api/v1/projects/{$project->slug}/reports");

        $response->assertOk()
            ->assertJsonCount(1, 'data.reports');
    }

    public function test_returns_404_for_nonexistent_project_reports(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/projects/nonexistent-slug/reports');

        $response->assertNotFound();
    }
}
