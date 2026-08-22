<?php

namespace App\Http\Resources\Api\V1\Concerns;

use App\Enums\ParameterType;
use App\Models\Parameter;

trait BuildsProjectParameters
{
    /**
     * Усі характеристики категорії проєкту — включно з незаповненими (value: null),
     * щоб клієнт міг відобразити повний список полів характеристик.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildParameters(?string $language): array
    {
        if (! $this->art_category_id) {
            return [];
        }

        $existingByParameterId = $this->relationLoaded('projectParameters')
            ? $this->projectParameters->keyBy('parameter_id')
            : collect();

        return Parameter::query()
            ->where('art_category_id', $this->art_category_id)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Parameter $parameter) use ($existingByParameterId, $language) {
                $existing = $existingByParameterId->get($parameter->getKey());

                if ($parameter->type === ParameterType::List) {
                    $value = $this->localizeField($existing?->parameterValue?->value, $language);
                } else {
                    $value = $existing?->custom_value;
                }

                return [
                    'parameter_id' => $parameter->getKey(),
                    'parameter' => $this->localizeField($parameter->name, $language),
                    'type' => $parameter->type->value,
                    'value_id' => $existing?->parameter_value_id,
                    'value' => $value,
                ];
            })
            ->values()
            ->all();
    }
}
