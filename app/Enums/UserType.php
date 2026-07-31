<?php

namespace App\Enums;

enum UserType: string
{
    case Personal = 'personal';
    case Legal = 'legal';
    case Team = 'team';

    /**
     * Отримати назву типу українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Personal => 'Фізична особа',
            self::Legal => 'Юридична особа',
            self::Team => 'Команда',
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
