<?php

namespace App\Enums;

enum Region: string
{
    case Europe = 'europe';
    case MiddleEast = 'middle_east';
    case Asia = 'asia';
    case Africa = 'africa';
    case NorthAmerica = 'north_america';
    case SouthAmerica = 'south_america';
    case Oceania = 'oceania';

    /**
     * Get human-readable label.
     *
     * @return array{uk: string, en: string}
     */
    public function getLabel(): array
    {
        return match ($this) {
            self::Europe => ['uk' => 'Європа', 'en' => 'Europe'],
            self::MiddleEast => ['uk' => 'Близький Схід', 'en' => 'Middle East'],
            self::Asia => ['uk' => 'Азія', 'en' => 'Asia'],
            self::Africa => ['uk' => 'Африка', 'en' => 'Africa'],
            self::NorthAmerica => ['uk' => 'Північна Америка', 'en' => 'North America'],
            self::SouthAmerica => ['uk' => 'Південна Америка', 'en' => 'South America'],
            self::Oceania => ['uk' => 'Океанія', 'en' => 'Oceania'],
        };
    }
}
