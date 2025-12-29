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
     * Отримати назву категорії українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Scenic => 'Сценічне мистецтво',
            self::Visual => 'Візуальне мистецтво',
            self::FineArt => 'Образотворче мистецтво',
            self::Literature => 'Література',
            self::Music => 'Музичне мистецтво',
            self::Other => 'Інше',
        };
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
     * Отримати підкатегорії для цієї категорії
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
