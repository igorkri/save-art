<?php

namespace App\Enums;

enum ArtCategory: string
{
    case Scenic = 'scenic';
    case Visual = 'visual';
    case FineArt = 'fine_art';
    case Literature = 'literature';
    case Music = 'music';
    case Other = 'other';

    /**
     * Отримати назву категорії (підтримує мультимовність)
     */
    public function getLabel(?string $language = 'uk'): string
    {
        $labels = [
            'uk' => match ($this) {
                self::Scenic => 'Сценічне мистецтво',
                self::Visual => 'Візуальне мистецтво',
                self::FineArt => 'Образотворче мистецтво',
                self::Literature => 'Література',
                self::Music => 'Музичне мистецтво',
                self::Other => 'Інше',
            },
            'en' => match ($this) {
                self::Scenic => 'Performing Arts',
                self::Visual => 'Visual Arts',
                self::FineArt => 'Fine Arts',
                self::Literature => 'Literature',
                self::Music => 'Music',
                self::Other => 'Other',
            },
        ];

        return $labels[$language] ?? $labels['uk'];
    }

    /**
     * Отримати всі категорії з перекладами
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * Отримати підкатегорії для цієї категорії (без мови - тільки українська)
     *
     * @return array<string, string>
     */
    public function getSubcategories(): array
    {
        return match ($this) {
            self::Scenic => [
                'directing' => 'Режисура',
                'acting' => 'Акторське мистецтво',
                'choreography' => 'Хореографічне мистецтво',
                'original_genre' => 'Оригінальний жанр',
            ],
            self::Visual => [
                'photography' => 'Художня фотографія',
                'video' => 'Відеозйомка та монтаж',
                'cinema' => 'Повнометражний кінематограф',
                'ar' => 'Доповнена реальність',
            ],
            self::FineArt => [
                'painting' => 'Живопис',
                'sculpture' => 'Скульптура',
                'digital' => 'Діджитал',
            ],
            self::Literature => [
                'poetry' => 'Поезія',
                'prose' => 'Проза',
            ],
            self::Music => [],
            self::Other => [],
        };
    }

    /**
     * Отримати підкатегорії з перекладами для всіх мов
     *
     * @return array<string, array<string, string>>
     */
    public function getSubcategoriesWithTranslations(): array
    {
        return match ($this) {
            self::Scenic => [
                'directing' => ['uk' => 'Режисура', 'en' => 'Directing'],
                'acting' => ['uk' => 'Акторське мистецтво', 'en' => 'Acting'],
                'choreography' => ['uk' => 'Хореографічне мистецтво', 'en' => 'Choreography'],
                'original_genre' => ['uk' => 'Оригінальний жанр', 'en' => 'Original Genre'],
            ],
            self::Visual => [
                'photography' => ['uk' => 'Художня фотографія', 'en' => 'Art Photography'],
                'video' => ['uk' => 'Відеозйомка та монтаж', 'en' => 'Video Production'],
                'cinema' => ['uk' => 'Повнометражний кінематограф', 'en' => 'Feature Film'],
                'ar' => ['uk' => 'Доповнена реальність', 'en' => 'Augmented Reality'],
            ],
            self::FineArt => [
                'painting' => ['uk' => 'Живопис', 'en' => 'Painting'],
                'sculpture' => ['uk' => 'Скульптура', 'en' => 'Sculpture'],
                'digital' => ['uk' => 'Діджитал', 'en' => 'Digital'],
            ],
            self::Literature => [
                'poetry' => ['uk' => 'Поезія', 'en' => 'Poetry'],
                'prose' => ['uk' => 'Проза', 'en' => 'Prose'],
            ],
            self::Music => [],
            self::Other => [],
        };
    }

    /**
     * Отримати назву підкатегорії за slug
     */
    public function getSubcategoryLabel(string $slug, ?string $language = 'uk'): ?string
    {
        $subcategories = $this->getSubcategoriesWithTranslations();

        if (! isset($subcategories[$slug])) {
            return null;
        }

        $translations = $subcategories[$slug];

        return $translations[$language] ?? $translations['uk'] ?? null;
    }

    /**
     * Отримати всі підкатегорії всіх категорій
     *
     * @return array<string, array<string, string>>
     */
    public static function getAllSubcategories(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->getSubcategories();
        }

        return $result;
    }
}
