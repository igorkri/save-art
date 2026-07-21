<?php

namespace App\Support;

use App\Enums\ParameterType;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use App\Models\ProjectParameter;

final class ProjectCategoryParameterValues
{
    /**
     * @var list<string>
     */
    public const LOCALES = ['uk', 'en'];

    /**
     * @return list<array{parameter_id: int, parameter_label: string, parameter_type: string, parameter_value_id: int|null, custom_value: array<string, string>}>
     */
    public static function rowsForProject(Project $project): array
    {
        return self::rowsForCategoryId($project->art_category_id, $project);
    }

    /**
     * @return list<array{parameter_id: int, parameter_label: string, parameter_type: string, parameter_value_id: int|null, custom_value: array<string, string>}>
     */
    public static function rowsForCategoryId(?int $categoryId, ?Project $projectForExistingValues = null): array
    {
        if (! filled($categoryId)) {
            return [];
        }

        $parameters = Parameter::query()
            ->where('art_category_id', $categoryId)
            ->orderBy('sort_order')
            ->get();

        $existingByParameterId = [];
        if ($projectForExistingValues !== null) {
            $projectForExistingValues->loadMissing('projectParameters');
            foreach ($projectForExistingValues->projectParameters as $projectParameter) {
                $existingByParameterId[$projectParameter->parameter_id] = $projectParameter;
            }
        }

        $rows = [];

        foreach ($parameters as $parameter) {
            $existing = $existingByParameterId[$parameter->getKey()] ?? null;

            $rows[] = [
                'parameter_id' => (int) $parameter->getKey(),
                'parameter_label' => $parameter->getLabel('uk'),
                'parameter_type' => $parameter->type->value,
                'parameter_value_id' => $existing?->parameter_value_id,
                'custom_value' => self::normalizeValueForLocales(is_array($existing?->custom_value) ? $existing->custom_value : []),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, string>
     */
    public static function normalizeValueForLocales(array $value): array
    {
        $out = [];
        foreach (self::LOCALES as $code) {
            $v = $value[$code] ?? '';
            $out[$code] = is_string($v) ? trim($v) : '';
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     */
    public static function syncForProject(Project $project, ?array $rows): void
    {
        if ($rows === null) {
            return;
        }

        if ($rows === []) {
            ProjectParameter::query()->where('project_id', $project->getKey())->delete();

            return;
        }

        $parameterIds = [];
        foreach ($rows as $row) {
            $parameterId = $row['parameter_id'] ?? null;
            if (filled($parameterId)) {
                $parameterIds[] = (int) $parameterId;
            }
        }
        $parameterIds = array_values(array_unique($parameterIds));

        $parametersById = $parameterIds === []
            ? collect()
            : Parameter::query()->whereIn('id', $parameterIds)->get()->keyBy(
                fn (Parameter $parameter): int => (int) $parameter->getKey(),
            );

        foreach ($rows as $row) {
            $parameterId = $row['parameter_id'] ?? null;
            if (! filled($parameterId)) {
                continue;
            }

            $parameterId = (int) $parameterId;
            $parameter = $parametersById->get($parameterId);
            if (! $parameter instanceof Parameter) {
                continue;
            }

            if ($parameter->type === ParameterType::List) {
                $valueId = $row['parameter_value_id'] ?? null;

                if (! filled($valueId) || ! ParameterValue::query()->where('id', $valueId)->where('parameter_id', $parameterId)->exists()) {
                    ProjectParameter::query()
                        ->where('project_id', $project->getKey())
                        ->where('parameter_id', $parameterId)
                        ->delete();

                    continue;
                }

                ProjectParameter::query()->updateOrCreate(
                    ['project_id' => $project->getKey(), 'parameter_id' => $parameterId],
                    ['parameter_value_id' => (int) $valueId, 'custom_value' => null],
                );

                continue;
            }

            $customValue = self::normalizeValueForLocales(is_array($row['custom_value'] ?? null) ? $row['custom_value'] : []);

            if (($customValue['uk'] ?? '') === '') {
                ProjectParameter::query()
                    ->where('project_id', $project->getKey())
                    ->where('parameter_id', $parameterId)
                    ->delete();

                continue;
            }

            ProjectParameter::query()->updateOrCreate(
                ['project_id' => $project->getKey(), 'parameter_id' => $parameterId],
                ['parameter_value_id' => null, 'custom_value' => $customValue],
            );
        }

        ProjectParameter::query()
            ->where('project_id', $project->getKey())
            ->whereNotIn('parameter_id', $parameterIds)
            ->delete();
    }
}
