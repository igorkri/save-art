<?php

namespace App\Traits;

/**
 * Trait для локалізації атрибутів моделі
 *
 * Дозволяє отримати локалізовані значення JSON полів
 * на основі обраної мови.
 *
 * Приклад використання:
 * $user->getLocalized('full_name', 'uk') // Повертає full_name['uk']
 * $user->getLocalized('full_name') // Повертає full_name на мові за замовчуванням (uk)
 */
trait LocalizesAttributes
{
    /**
     * Отримати локалізоване значення атрибута
     *
     * @param  string  $attribute  Назва атрибута (наприклад, 'full_name')
     * @param  string|null  $language  Мова (uk, en). За замовчуванням 'uk'
     * @param  string|null  $fallback  Fallback значення, якщо не знайдено
     */
    public function getLocalized(string $attribute, ?string $language = null, ?string $fallback = null): ?string
    {
        $language = $language ?? $this->getDefaultLanguage();
        $value = $this->{$attribute};

        if (! $value) {
            return $fallback;
        }

        if (is_array($value)) {
            return $value[$language] ?? $value['uk'] ?? $value['en'] ?? $fallback;
        }

        return $value ?? $fallback;
    }

    /**
     * Отримати локалізоване значення з fallback на будь-яку доступну мову
     *
     * @param  string  $attribute  Назва атрибута
     * @param  string|null  $language  Мова за замовчуванням
     */
    public function getLocalizedOrAny(string $attribute, ?string $language = null): ?string
    {
        $language = $language ?? $this->getDefaultLanguage();
        $value = $this->{$attribute};

        if (! $value || ! is_array($value)) {
            return $value;
        }

        // Повертаємо обрану мову, потім uk, потім en, потім перше доступне
        return $value[$language] ?? $value['uk'] ?? $value['en'] ?? collect($value)->first();
    }

    /**
     * Отримати всі локалізовані варіанти атрибута
     *
     * @param  string  $attribute  Назва атрибута
     */
    public function getLocalizedAll(string $attribute): array
    {
        $value = $this->{$attribute};

        if (! is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * Отримати мову за замовчуванням для застосунку
     */
    protected function getDefaultLanguage(): string
    {
        return config('app.locale', 'uk') === 'uk' ? 'uk' : 'en';
    }
}
