<?php

namespace App\Http\Resources\Api\V1\Concerns;

use Illuminate\Http\Request;

trait LocalizesFields
{
    /**
     * Отримати мову з параметра запиту
     */
    protected function getLanguage(Request $request): ?string
    {
        $language = $request->query('language');
        $supported = ['uk', 'en'];

        return ($language && in_array($language, $supported)) ? $language : null;
    }

    /**
     * Локалізація поля - повертає значення для конкретної мови або весь об'єкт
     */
    protected function localizeField($field, ?string $language): mixed
    {
        if ($language === null) {
            return $field;
        }

        if (! is_array($field)) {
            return $field;
        }

        // Перевіряємо чи це об'єкт з мовами (має ключі uk/en)
        $hasLanguageKeys = isset($field['uk']) || isset($field['en']);

        if ($hasLanguageKeys) {
            return $field[$language] ?? $field['uk'] ?? reset($field);
        }

        return $field;
    }

    /**
     * Локалізація масиву об'єктів з вкладеними мовними полями
     */
    protected function localizeArrayField($field, ?string $language): mixed
    {
        if ($language === null || ! is_array($field)) {
            return $field;
        }

        return array_map(function ($item) use ($language) {
            if (! is_array($item)) {
                return $item;
            }

            $localized = [];
            foreach ($item as $key => $value) {
                $localized[$key] = $this->localizeField($value, $language);
            }

            return $localized;
        }, $field);
    }
}
