<?php

namespace App\Enums;

enum ProfileType: string
{
    case Artist = 'artist';
    case Patron = 'patron';

    /**
     * Отримати назву типу українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Artist => 'Митець',
            self::Patron => 'Меценат',
        };
    }

    /**
     * Отримати всі типи з перекладами
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
