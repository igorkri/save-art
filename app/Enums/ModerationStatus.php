<?php

namespace App\Enums;

enum ModerationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Отримати назву статусу українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Очікує',
            self::Approved => 'Схвалено',
            self::Rejected => 'Відхилено',
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
