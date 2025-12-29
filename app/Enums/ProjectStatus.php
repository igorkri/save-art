<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Moderation = 'moderation';
    case Announced = 'announced';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Sold = 'sold';
    case Rejected = 'rejected';

    /**
     * Отримати назву статусу українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Чернетка',
            self::Moderation => 'На модерації',
            self::Announced => 'Оголошений',
            self::InProgress => 'В роботі',
            self::Paused => 'На паузі',
            self::Completed => 'Завершений',
            self::Sold => 'Проданий',
            self::Rejected => 'Відхилений',
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

    /**
     * Статуси, видимі для гостей та неавторизованих користувачів
     *
     * @return array<self>
     */
    public static function publicStatuses(): array
    {
        return [
            self::Announced,
            self::InProgress,
            self::Paused,
            self::Completed,
            self::Sold,
        ];
    }

    /**
     * Статуси, видимі тільки для власника та модераторів
     *
     * @return array<self>
     */
    public static function privateStatuses(): array
    {
        return [
            self::Draft,
            self::Moderation,
            self::Rejected,
        ];
    }

    /**
     * Чи може проєкт приймати донати
     */
    public function canReceiveDonations(): bool
    {
        return in_array($this, [self::Announced, self::InProgress]);
    }

    /**
     * Чи можна редагувати проєкт
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected]);
    }
}
