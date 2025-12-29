<?php

namespace App\Enums;

enum StageStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Отримати назву статусу українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Planned => 'Запланований',
            self::InProgress => 'В процесі',
            self::Completed => 'Завершений',
        };
    }

    /**
     * Отримати всі статуси з перекладами
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
