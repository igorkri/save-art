<?php

namespace App\Enums;

enum ProjectSource: string
{
    case SaveArt = 'save_art';
    case ArtUaInfo = 'art_ua_info';

    /**
     * Отримати назву джерела українською. На save-art такий запис називається
     * "проєкт" (збір коштів), на art-ua-info — "робота" (портфоліо без бюджету).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SaveArt => 'Проєкт (SaveArt)',
            self::ArtUaInfo => 'Робота (Art-UA-Info)',
        };
    }

    /**
     * Отримати колір джерела для відображення в UI (Filament)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::SaveArt => 'warning',
            self::ArtUaInfo => 'info',
        };
    }

    /**
     * Отримати всі джерела з перекладами
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
}
