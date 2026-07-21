<?php

namespace App\Filament\Resources\Projects\Concerns;

use App\Models\Project;
use App\Support\ProjectCategoryParameterValues;

trait HandlesProjectParameterValuesInForm
{
    /**
     * @var list<array<string, mixed>>|null
     */
    protected ?array $pendingProjectParameterValues = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillProjectParameterValues(array $data, Project $record): array
    {
        $data['project_parameter_values'] = ProjectCategoryParameterValues::rowsForProject($record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractProjectParameterValuesFromData(array $data): array
    {
        $this->pendingProjectParameterValues = $data['project_parameter_values'] ?? null;
        unset($data['project_parameter_values']);

        return $data;
    }

    protected function syncPendingProjectParameterValues(Project $project): void
    {
        ProjectCategoryParameterValues::syncForProject($project, $this->pendingProjectParameterValues);
        $this->pendingProjectParameterValues = null;
    }
}
