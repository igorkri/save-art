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
     * Отримати колір статусу для бейджа/кнопки
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::InProgress => 'warning',
            self::Completed => 'success',
        };
    }

    /**
     * Порядковий номер статусу для перевірки послідовності переходів
     * (статус можна змінювати лише вперед, назад — заборонено)
     */
    public function order(): int
    {
        return match ($this) {
            self::Planned => 0,
            self::InProgress => 1,
            self::Completed => 2,
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
